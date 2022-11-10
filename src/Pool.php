<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool;

use Illuminate\Contracts\Container\Container;
use PeibinLaravel\Contracts\StdoutLoggerInterface;
use PeibinLaravel\Pool\Contracts\ConnectionInterface;
use PeibinLaravel\Pool\Contracts\FrequencyInterface;
use PeibinLaravel\Pool\Contracts\LowFrequencyInterface;
use PeibinLaravel\Pool\Contracts\PoolInterface;
use PeibinLaravel\Pool\Contracts\PoolOptionInterface;
use RuntimeException;
use Throwable;

abstract class Pool implements PoolInterface
{
    protected Channel $channel;

    protected PoolOptionInterface $option;

    protected int $currentConnections = 0;

    protected null | LowFrequencyInterface | FrequencyInterface $frequency = null;

    public function __construct(protected Container $container, array $config = [])
    {
        $this->initOption($config);

        $this->channel = $container->make(Channel::class, ['size' => $this->option->getMaxConnections()]);
    }

    public function get(): ConnectionInterface
    {
        $connection = $this->getConnection();

        try {
            if ($this->frequency instanceof FrequencyInterface) {
                $this->frequency->hit();
            }

            if ($this->frequency instanceof LowFrequencyInterface) {
                if ($this->frequency->isLowFrequency()) {
                    $this->flush();
                }
            }
        } catch (Throwable $exception) {
            if ($this->container->has(StdoutLoggerInterface::class)) {
                $this->container->get(StdoutLoggerInterface::class)->error((string)$exception);
            }
        }

        return $connection;
    }

    public function release(ConnectionInterface $connection): void
    {
        $this->channel->push($connection);
    }

    public function flush(): void
    {
        $num = $this->getConnectionsInChannel();

        if ($num > 0) {
            while (
                $this->currentConnections > $this->option->getMinConnections() &&
                $conn = $this->channel->pop(0.001)
            ) {
                try {
                    $conn->close();
                } catch (Throwable $exception) {
                    if ($this->container->has(StdoutLoggerInterface::class)) {
                        $this->container->get(StdoutLoggerInterface::class)->error((string)$exception);
                    }
                } finally {
                    --$this->currentConnections;
                    --$num;
                }

                if ($num <= 0) {
                    // Ignore connections queued during flushing.
                    break;
                }
            }
        }
    }

    public function flushOne(bool $must = false): void
    {
        $num = $this->getConnectionsInChannel();
        if ($num > 0 && $conn = $this->channel->pop(0.001)) {
            if ($must || !$conn->check()) {
                try {
                    $conn->close();
                } catch (Throwable $exception) {
                    if ($this->container->has(StdoutLoggerInterface::class)) {
                        $this->container->get(StdoutLoggerInterface::class)->error((string)$exception);
                    }
                } finally {
                    --$this->currentConnections;
                }
            } else {
                $this->release($conn);
            }
        }
    }

    public function getCurrentConnections(): int
    {
        return $this->currentConnections;
    }

    public function getOption(): PoolOptionInterface
    {
        return $this->option;
    }

    public function getConnectionsInChannel(): int
    {
        return $this->channel->length();
    }

    protected function initOption(array $options = []): void
    {
        $this->option = $this->container->make(PoolOption::class, [
            'minConnections' => $options['min_connections'] ?? 1,
            'maxConnections' => $options['max_connections'] ?? 10,
            'connectTimeout' => $options['connect_timeout'] ?? 10.0,
            'waitTimeout'    => $options['wait_timeout'] ?? 3.0,
            'heartbeat'      => $options['heartbeat'] ?? -1,
            'maxIdleTime'    => $options['max_idle_time'] ?? 60.0,
        ]);
    }

    abstract protected function createConnection(): ConnectionInterface;

    private function getConnection(): ConnectionInterface
    {
        $num = $this->getConnectionsInChannel();

        try {
            if ($num === 0 && $this->currentConnections < $this->option->getMaxConnections()) {
                ++$this->currentConnections;
                return $this->createConnection();
            }
        } catch (Throwable $throwable) {
            --$this->currentConnections;
            throw $throwable;
        }

        $connection = $this->channel->pop($this->option->getWaitTimeout());
        if (!$connection instanceof ConnectionInterface) {
            throw new RuntimeException(
                'Connection pool exhausted. Cannot establish new connection before wait_timeout.'
            );
        }
        return $connection;
    }
}
