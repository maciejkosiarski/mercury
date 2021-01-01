<?php

declare(strict_types=1);

namespace App\Coroutine;

class Scheduler
{
    protected int $maxTaskId = 0;
    protected array $taskMap = []; // taskId => task
    protected \SplQueue $taskQueue;
    // resourceID => [socket, tasks]
    protected array $waitingForRead = [];
    protected array $waitingForWrite = [];

    public function __construct()
    {
        $this->taskQueue = new \SplQueue();
    }

    public function newTask(\Generator $coroutine): int
    {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    public function killTask(int $tid): bool
    {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }

        unset($this->taskMap[$tid]);

        // This is a bit ugly and could be optimized so it does not have to walk the queue,
        // but assuming that killing tasks is rather rare I won't bother with it now
        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return true;
    }

    public function schedule(Task $task): void
    {
        $this->taskQueue->enqueue($task);
    }

    public function run(): void
    {
//        $this->newTask($this->ioPollTask());

        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $returnValue = $task->run();

            if ($returnValue instanceof SystemCall) {
                $returnValue($task, $this);
                continue;
            }

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }

    public function waitForRead($socket, Task $task) {
        if (isset($this->waitingForRead[(int) $socket])) {
            $this->waitingForRead[(int) $socket][1][] = $task;
        } else {
            $this->waitingForRead[(int) $socket] = [$socket, [$task]];
        }
    }

    public function waitForWrite($socket, Task $task) {
        if (isset($this->waitingForWrite[(int) $socket])) {
            $this->waitingForWrite[(int) $socket][1][] = $task;
        } else {
            $this->waitingForWrite[(int) $socket] = [$socket, [$task]];
        }
    }

    protected function ioPollTask() {
        while (true) {
            if ($this->taskQueue->isEmpty()) {
                $this->ioPoll(null);
            } else {
                $this->ioPoll(0);
            }
            yield;
        }
    }

    protected function ioPoll($timeout) {
        $rSocks = [];

        foreach ($this->waitingForRead as list($socket)) {
            $rSocks[] = $socket;
        }

        $wSocks = [];
        foreach ($this->waitingForWrite as list($socket)) {
            $wSocks[] = $socket;
        }

        $eSocks = []; // dummy

        try {
            if (!stream_select($rSocks, $wSocks, $eSocks, $timeout)) {
                return;
            }
        } catch (\Exception $e) {
            if ('Warning: stream_select(): No stream arrays were passed' === $e->getMessage()) {
                return;
            }

            throw $e;
        }

        foreach ($rSocks as $socket) {
            list(, $tasks) = $this->waitingForRead[(int) $socket];
            unset($this->waitingForRead[(int) $socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }

        foreach ($wSocks as $socket) {
            list(, $tasks) = $this->waitingForWrite[(int) $socket];
            unset($this->waitingForWrite[(int) $socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }
    }

}
