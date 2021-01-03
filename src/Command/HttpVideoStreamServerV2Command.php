<?php

declare(strict_types=1);

namespace App\Command;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Status;
use Amp\Loop;
use Psr\Log\LoggerInterface;
use React\Http\Message\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HttpVideoStreamServerV2Command extends Command
{
    use LockableTrait;

    private string $videos;
    private LoggerInterface $logger;

    public function __construct(string $videos, LoggerInterface $videoStreamLogger)
    {
        $this->videos = $videos;
        $this->logger = $videoStreamLogger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:video-stream:v2')
            ->setDescription('Video stream server in AM PHP library')
            ->addArgument('port', InputArgument::REQUIRED,'Port number for communicate.')
            ->addArgument('uri', InputArgument::REQUIRED);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = (int)$input->getArgument('port');
        $uri = $input->getArgument('uri');

        $logger = $this->logger;

        Loop::run(static function () use ($port, $uri, $logger) {
            $servers = [
                \Amp\Socket\Server::listen($uri . ':' . $port),
                \Amp\Socket\Server::listen('[::]:'. $port),
            ];

            $server = new HttpServer($servers, new CallableRequestHandler(static function () {
                return new Response(Status::OK, [
                    "content-type" => "text/plain; charset=utf-8"
                ], "Hello, World!");
            }), $logger);

            yield $server->start();

            // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
            Loop::onSignal(\SIGINT, static function (string $watcherId) use ($server) {
                Loop::cancel($watcherId);
                yield $server->stop();
            });
        });

        return Command::SUCCESS;
    }
}
