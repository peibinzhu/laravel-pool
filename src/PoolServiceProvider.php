<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool;

use Illuminate\Support\ServiceProvider;
use PeibinLaravel\Pool\SimplePool\PoolFactory;
use PeibinLaravel\ProviderConfig\Contracts\ProviderConfigInterface;

class PoolServiceProvider extends ServiceProvider implements ProviderConfigInterface
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                PoolFactory::class => PoolFactory::class,
            ],
        ];
    }
}
