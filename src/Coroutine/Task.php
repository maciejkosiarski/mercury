<?php

declare(strict_types=1);

namespace App\Coroutine;

class Task
{
    protected int $taskId;
    protected \Generator $coroutine;
    protected ?string $sendValue = null;
    protected bool $beforeFirstYield = true;

    public function __construct(int $taskId, \Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setSendValue(string $sendValue): void
    {
        $this->sendValue = $sendValue;
    }

    public function run()
    {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } else {
            $returnValue = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            return $returnValue;
        }
    }

    public function isFinished(): bool
    {
        return !$this->coroutine->valid();
    }
}
