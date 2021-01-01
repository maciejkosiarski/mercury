<?php

declare(strict_types=1);

namespace App\Command;

use App\Coroutine\SystemCall;
use App\Coroutine\Task;
use React\EventLoop\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class DevCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:dev')
            ->setDescription('Command for developing tests');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loop = Factory::create();

        $stopwatch = new Stopwatch();
        $stopwatch->start('eventName');

        try {
            $generator = call_user_func(function() {
                $input = yield;
                dump("inside: " . $input);
            });

            dump($generator->current());

            $generator->send("bar");
            die;

            $scheduler = new Scheduler();

//            $scheduler->newTask(server(8888));
//            $scheduler->newTask(\App\Command\task(6));
//            $scheduler->newTask(\App\Command\task(4));
//            $scheduler->newTask(\App\Command\task(8));
            $scheduler->newTask(taskTest());
            $scheduler->run();

        } catch (\Exception $e) {
            dump($e);
        }

        $event = $stopwatch->stop('eventName');

        dump((string)$event);

        return Command::SUCCESS;
    }
}

function task(int $max) {
    $tid = (yield getTaskId());
    $childTid = (yield newTask(childTask()));

    for ($i = 1; $i <= $max; ++$i) {
        dump("Parent task $tid iteration $i.");
        yield;

        if ($i == 3) yield killTask((int)$childTid);
    }
}

function childTask() {
    $tid = (yield getTaskId());
    while (true) {
        dump("Child task $tid still alive!");
        yield;
    }
}

function getTaskId() {
    return new SystemCall(function(Task $task, Scheduler $scheduler) {
        $task->setSendValue((string)$task->getTaskId());
        $scheduler->schedule($task);
    });
}

function newTask(\Generator $coroutine) {
    return new SystemCall(function(Task $task, Scheduler $scheduler) use ($coroutine) {
        $task->setSendValue((string)$scheduler->newTask($coroutine));
        $scheduler->schedule($task);
    });
}

function killTask(int $tid) {
    return new SystemCall(function(Task $task, Scheduler $scheduler) use ($tid) {
        $task->setSendValue($scheduler->killTask($tid) ? 'Task killed!' : 'Task not killed!');
        $scheduler->schedule($task);
    });
}

function waitForRead($socket) {
    dump('waitForRead');
    dump($socket);
    return new SystemCall(
        function(Task $task, Scheduler $scheduler) use ($socket) {
            $scheduler->waitForRead($socket, $task);
        }
    );
}

function waitForWrite($socket) {
    dump('waitForWrite');
    dump($socket);
    return new SystemCall(
        function(Task $task, Scheduler $scheduler) use ($socket) {
            $scheduler->waitForWrite($socket, $task);
        }
    );
}

function server($port) {
    echo "Starting server at port $port...\n";

    $socket = @stream_socket_server("tcp://0.0.0.0:$port", $errNo, $errStr);
    if (!$socket) throw new \Exception($errStr, $errNo);

    stream_set_blocking($socket, false);

    while (true) {
        yield waitForRead($socket);
        $clientSocket = stream_socket_accept($socket, 0);
        dump('Client socket:');
        dump($socket);
        yield newTask(handleClient($clientSocket));
    }
}

function handleClient($socket) {
    dump('Handle socket from client');
    yield waitForRead($socket);
    $data = fread($socket, 8192);

    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

    yield waitForWrite($socket);
    fwrite($socket, $response);

    fclose($socket);
}

function echoTimes($msg, $max) {
    for ($i = 1; $i <= $max; ++$i) {
        echo "$msg iteration $i\n";
        yield;
    }
}

function taskTest() {
    echoTimes('foo', 10); // print foo ten times
    echo "---\n";
    echoTimes('bar', 5); // print bar five times
    yield;
}
