<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool\SimplePool;

use Illuminate\Contracts\Container\Container;
use PeibinLaravel\Pool\Contracts\ConnectionInterface;
use PeibinLaravel\Pool\Pool as AbstractPool;

class Pool extends AbstractPool
{
    /**
     * @var callable
     */
    protected $callback;

    public function __construct(Container $container, callable $callback, array $option)
    {
        $this->callback = $callback;

        parent::__construct($container, $option);
    }

    protected function createConnection(): ConnectionInterface
    {
        return $this->container->make(Connection::class, [
            'pool'     => $this,
            'callback' => $this->callback,
        ]);
    }
}
