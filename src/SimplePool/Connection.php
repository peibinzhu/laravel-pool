<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool\SimplePool;

use Illuminate\Contracts\Container\Container;
use PeibinLaravel\Pool\Connection as AbstractConnection;
use PeibinLaravel\Pool\Contracts\PoolInterface;

class Connection extends AbstractConnection
{
    /**
     * @var callable
     */
    protected $callback;

    protected mixed $connection = null;

    public function __construct(Container $container, PoolInterface $pool, callable $callback)
    {
        $this->callback = $callback;
        parent::__construct($container, $pool);
    }

    public function getActiveConnection()
    {
        if (!$this->connection || !$this->check()) {
            $this->reconnect();
        }

        return $this->connection;
    }

    public function reconnect(): bool
    {
        $this->connection = ($this->callback)();
        $this->lastUseTime = microtime(true);
        return true;
    }

    public function close(): bool
    {
        $this->connection = null;
        return true;
    }
}
