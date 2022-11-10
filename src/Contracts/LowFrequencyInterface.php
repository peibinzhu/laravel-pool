<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool\Contracts;

use PeibinLaravel\Pool\Pool;

interface LowFrequencyInterface
{
    public function __construct(?Pool $pool = null);

    public function isLowFrequency(): bool;
}
