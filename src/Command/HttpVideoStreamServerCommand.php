<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\Http\Message\Response;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Promise\Stream\unwrapReadable;

class HttpVideoStreamServerCommand extends Command
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
        $this->setName('app:video-stream:v1')
            ->setDescription('Video stream server in ReactPHP library')
            ->addArgument('port', InputArgument::REQUIRED,'Port number for communicate.')
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

        try {
            $this->logger->info(sprintf('Video stream server on %s port starting', $port));

            $loop = Factory::create();
            $server = $this->initServer($loop);
            $socket = new \React\Socket\Server($uri . ':' . $port, $loop);
            $server->listen($socket);

            $this->logger->info('Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()));

            $loop->run();

            return Command::SUCCESS;
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
    }

    protected function getRequestHandler(\React\Filesystem\FilesystemInterface $filesystem): \Closure
    {
        return function (ServerRequestInterface $request) use ($filesystem) {
            $params = $request->getQueryParams();
            $fileName = $params['video'] ?? null;

            $requestUri = $request->getUri();
            $baseUri = $requestUri->getScheme() . '://' . $requestUri->getHost() . ':' . $requestUri->getPort();

            $html = '<h1>Video streaming server %s</h1><ul><li><a href="%s">Lucyna</a></li></ul>';
            $body = sprintf($html, date('h:i:s A'), sprintf('%s?video=lucyna', $baseUri));

            if ($fileName === null) {
                return new Response(200, ['Content-Type' => 'text/html'], $body);
            }

            $filePath = sprintf('%s/%s.mp4', $this->videos, $fileName);
            $file = $filesystem->file($filePath);

            return $file->exists()
                ->then(
                    function () use ($file) {
                        return new Response(200, ['Content-Type' => 'video/mp4'], unwrapReadable($file->open('r')));
                    },
                    function () use ($baseUri) {
                        $html = sprintf('<h1>Video streaming server %s</h1><p>This video doesn\'t exist on server. <a href="%s">Back</a></p>', date('h:i:s A'), $baseUri);
                        return new Response(404, ['Content-Type' => 'text/html'], $html);
                    }
                );
        };
    }

    protected function initServer(\React\EventLoop\StreamSelectLoop $loop): Server
    {
        return new Server(
            $loop,
            new LimitConcurrentRequestsMiddleware(20),
            new RequestBodyBufferMiddleware(10 * 1024 * 1024),
            $this->getRequestHandler(\React\Filesystem\Filesystem::create($loop))
        );
    }
}
