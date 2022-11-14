<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool;

use PeibinLaravel\Coordinator\Timer;
use PeibinLaravel\Pool\Contracts\LowFrequencyInterface;

class ConstantFrequency implements LowFrequencyInterface
{
    protected Timer $timer;

    protected ?int $timerId = null;

    protected int $interval = 10000;

    public function __construct(protected ?Pool $pool = null)
    {
        $this->timer = new Timer();
        if ($pool) {
            $this->timerId = $this->timer->tick(
                $this->interval / 1000,
                fn () => $this->pool->flushOne()
            );
        }
    }

    public function __destruct()
    {
        $this->clear();
    }

    public function clear()
    {
        if ($this->timerId) {
            $this->timer->clear($this->timerId);
        }
        $this->timerId = null;
    }

    public function isLowFrequency(): bool
    {
        return false;
    }
}
