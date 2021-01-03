<?php

declare(strict_types=1);

namespace App\Command;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Status;
use Amp\Loop;
use Amp\Socket\Server;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HttpServerV1Command extends Command
{
    use LockableTrait;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $httpServerLogger)
    {
        $this->logger = $httpServerLogger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:http-server:v1')
            ->setDescription('HTTP "Hello World" server in AMP library')
            ->addArgument('port', InputArgument::REQUIRED, 'Port number for communicate.')
            ->addArgument('uri', InputArgument::REQUIRED);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = (int)$input->getArgument('port');
        $uri = $input->getArgument('uri');

        if (!$this->lock(sprintf('lock://%s:%s', $uri, $port))) {
            return Command::SUCCESS;
        }

        $logger = $this->logger;

        try {
            Loop::run(function () use ($port, $uri, $logger) {
                $servers = [
                    Server::listen(sprintf('%s:%s', $uri, $port)),
                    Server::listen(sprintf('[::]:%s', $port)),
                ];

                $server = new HttpServer($servers, new CallableRequestHandler(static function () use ($port) {
                    return new \Amp\Http\Server\Response(Status::OK, [
                        "content-type" => "text/html; charset=utf-8"
                    ], sprintf('<h1>Amp http server: Hello, on port %s!</h1>', $port));
                }), $logger);

                yield $server->start();

                // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
                Loop::onSignal(\SIGINT, static function (string $watcherId) use ($server) {
                    Loop::cancel($watcherId);
                    yield $server->stop();
                });
            });
        } catch (\Exception $e) {
            $this->logger->warning('Exception occurred!', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return Command::FAILURE;
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }
}
