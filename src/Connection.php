<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool;

use Illuminate\Contracts\Container\Container;
use PeibinLaravel\Contracts\StdoutLoggerInterface;
use PeibinLaravel\Pool\Contracts\ConnectionInterface;
use PeibinLaravel\Pool\Contracts\PoolInterface;
use Throwable;

abstract class Connection implements ConnectionInterface
{
    protected float $lastUseTime = 0.0;

    public function __construct(protected Container $container, protected PoolInterface $pool)
    {
    }

    public function release(): void
    {
        $this->pool->release($this);
    }

    public function getConnection()
    {
        try {
            return $this->getActiveConnection();
        } catch (Throwable $exception) {
            if (
                $this->container->has(StdoutLoggerInterface::class) &&
                $logger = $this->container->get(StdoutLoggerInterface::class)
            ) {
                $logger->warning('Get connection failed, try again. ' . $exception);
            }
            return $this->getActiveConnection();
        }
    }

    public function check(): bool
    {
        $maxIdleTime = $this->pool->getOption()->getMaxIdleTime();
        $now = microtime(true);
        if ($now > $maxIdleTime + $this->lastUseTime) {
            return false;
        }

        $this->lastUseTime = $now;
        return true;
    }

    abstract public function getActiveConnection();
}
