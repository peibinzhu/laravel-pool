<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool;

use Illuminate\Support\ServiceProvider;
use PeibinLaravel\Pool\SimplePool\PoolFactory;
use PeibinLaravel\Utils\Providers\RegisterProviderConfig;

class PoolServiceProvider extends ServiceProvider
{
    use RegisterProviderConfig;

    public function __invoke(): array
    {
        return [
            'dependencies' => [
                PoolFactory::class => PoolFactory::class,
            ],
        ];
    }
}
