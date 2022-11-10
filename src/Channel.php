<?php

declare(strict_types=1);

namespace PeibinLaravel\Pool;

use PeibinLaravel\Pool\Contracts\ConnectionInterface;
use SplQueue;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel as CoChannel;

class Channel
{
    protected CoChannel $channel;

    protected SplQueue $queue;

    public function __construct(protected int $size)
    {
        $this->channel = new CoChannel($size);
        $this->queue = new SplQueue();
    }

    public function pop(float $timeout): ConnectionInterface | false
    {
        if ($this->isCoroutine()) {
            return $this->channel->pop($timeout);
        }
        return $this->queue->shift();
    }

    public function push(ConnectionInterface $data): bool
    {
        if ($this->isCoroutine()) {
            return $this->channel->push($data);
        }
        $this->queue->push($data);
        return true;
    }

    public function length(): int
    {
        if ($this->isCoroutine()) {
            return $this->channel->length();
        }
        return $this->queue->count();
    }

    protected function isCoroutine(): bool
    {
        return Coroutine::getCid() > 0;
    }
}
