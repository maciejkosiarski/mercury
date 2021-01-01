<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Message\Response;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Promise\Stream\unwrapReadable;

class HttpVideoStreamServerCommand extends Command
{
    private string $videos;

    public function __construct(string $videos)
    {
        $this->videos = $videos;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:video-stream')
            ->setDescription('Video stream server in ReactPHP')
            ->addArgument('port', InputArgument::REQUIRED,'Port number for communicate.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = (int)$input->getArgument('port');
        $loop = Factory::create();

        $filesystem = \React\Filesystem\Filesystem::create($loop);

        $requestHandler = function (ServerRequestInterface $request) use ($filesystem) {
            $params = $request->getQueryParams();
            $fileName = $params['video'] ?? null;
            $iteration = $params['iteration'] ?? null;

            $requestUri = $request->getUri();
            $baseUri = $requestUri->getScheme() . '://' . $requestUri->getHost() . ':' . $requestUri->getPort();
            $body = sprintf('<h1>Video streaming server iteration: %s</h1>', $iteration);
//            $body = sprintf('<h1>Video streaming server %s</h1><ul><li><a href="%s">Lucyna</a></li></ul>',date('h:i:s A'), sprintf('%s?video=lucyna', $baseUri));

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
                        $html = sprintf('<h1>Video streaming server</h1><p>This video doesn\'t exist on server. <a href="%s">Back</a></p>', $baseUri);
                        return new Response(404, ['Content-Type' => 'text/html'], $html);
                    }
                );
        };

        $server = new Server(
            $loop,
            new LimitConcurrentRequestsMiddleware(10),
            new RequestBodyBufferMiddleware(2 * 1024 * 1024),
            $requestHandler
        );

        $socket = new \React\Socket\Server('0.0.0.0:'. $port, $loop);
        $server->listen($socket);

        $output->writeln('<info>Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . '</info>');

        $loop->run();

        return Command::SUCCESS;
    }
}
