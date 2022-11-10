<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool\SimplePool;

use Illuminate\Contracts\Container\Container;

class PoolFactory
{
    /**
     * @var Pool[]
     */
    protected array $pools = [];

    protected array $configs = [];

    public function __construct(protected Container $container)
    {
    }

    public function addConfig(Config $config): static
    {
        $this->configs[$config->getName()] = $config;
        return $this;
    }

    public function get(string $name, callable $callback, array $option = []): Pool
    {
        if (!$this->hasConfig($name)) {
            $config = new Config($name, $callback, $option);
            $this->addConfig($config);
        }

        $config = $this->getConfig($name);

        if (!isset($this->pools[$name])) {
            $this->pools[$name] = $this->container->make(Pool::class, [
                'callback' => $config->getCallback(),
                'option'   => $config->getOption(),
            ]);
        }

        return $this->pools[$name];
    }

    public function getPoolNames(): array
    {
        return array_keys($this->pools);
    }

    protected function hasConfig(string $name): bool
    {
        return isset($this->configs[$name]);
    }

    protected function getConfig(string $name): Config
    {
        return $this->configs[$name];
    }
}
