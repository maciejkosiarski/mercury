<?php

declare(strict_types=1);

namespace App\Command;

use App\Coroutine\Scheduler;
use App\Coroutine\SystemCall;
use App\Coroutine\Task;
use React\EventLoop\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DevHttpClientTestCommand extends Command
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:dev:http-client-test')
            ->setDescription('Command for developing tests');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('eventName');

        $urls = ['https://google.com','https://amazon.com','https://allegro.pl'];
        $responses = [];
        foreach ($urls as $url) {
            $responses[] = $this->httpClient->request('GET', $url);
        }

        foreach ($this->httpClient->stream($responses) as $response => $chunk) {
            if ($chunk->isFirst()) {
                // headers of $response just arrived
                // $response->getHeaders() is now a non-blocking call
                // do something with headers
            } elseif ($chunk->isLast()) {
                // the full content of $response just completed
                // $response->getContent() is now a non-blocking call
                // do something with complete response
                dump($response->getHeaders());
            } else {
                // $chunk->getContent() will return a piece
                // of the response body that just arrived
            }
        }

        $event = $stopwatch->stop('eventName');

        dump((string)$event);

        return Command::SUCCESS;
    }
}
