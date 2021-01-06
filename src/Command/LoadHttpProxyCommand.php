<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\DomCrawler\UriResolver;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class LoadHttpProxyCommand extends Command
{
    private FilesystemAdapter $cache;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $tmpDirectory;

    public function __construct(FilesystemAdapter $pageContentsCache, LoggerInterface $hostsLogger, HttpClientInterface $httpClient, string $tmpDirectory)
    {
        $this->cache = $pageContentsCache;
        $this->httpClient = $httpClient;
        $this->logger = $hostsLogger;
        $this->tmpDirectory = $tmpDirectory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:http-proxy:load')
            ->setDescription('Load proxy addresses');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $urls = [
            new Url('http://www.freeproxylists.net/'),
            new Url('http://www.freeproxylists.net/?page=2'),
            new Url('http://www.freeproxylists.net/?page=3'),
            new Url('http://www.freeproxylists.net/?page=4'),
            new Url('http://www.freeproxylists.net/?page=5'),
            new Url('http://www.freeproxylists.net/?page=6'),
            new Url('http://www.freeproxylists.net/?page=7'),
            new Url('http://www.freeproxylists.net/?page=8'),
            new Url('http://www.freeproxylists.net/?page=9'),
            new Url('http://www.freeproxylists.net/?page=10'),
        ];

        $responses = [];


        foreach ($urls as $url) {
            if (!$this->cache->hasItem($url->getHost())) {
                $responses[] = $this->httpClient->request('GET', $url->getUrl());
            }
        }

        $results = [];
        $failed = [];
        /**
         * @var ResponseInterface $response
         * @var ChunkInterface $chunk
         */
        foreach ($this->httpClient->stream($responses) as $response => $chunk) {
            try {
                if ($chunk->isTimeout()) {
                    $response->cancel();
                }
                if ($chunk->isFirst()) {
                    if (200 !== $response->getStatusCode()) {
                        $failed[] = $response->getInfo('url');
                        $response->cancel();
                    }
                }
                if ($chunk->isLast()) {
                    $url = new Url($response->getInfo('url'));
                    $pageContent = $this->cache->getItem($url->getHost());
                    $pageContent->set($response->getContent());
                    $this->cache->save($pageContent);
                }
            } catch (TransportExceptionInterface $e) {
                dd($e);
            }
        }

        foreach ($urls as $url) {
            dump($this->cache->getItem($url->getHost())->get());
        }
//        foreach ($results as $result) {
//            dd($result->getContent());
//            $crawler = new Crawler($result->getContent());
//            dd($crawler->filter('body')->html());
//        }

        $allLinks = [];

        list($allLinks, $responses) = $this->parseResults($results, $allLinks);

        while (!empty($responses)) {
            $results = [];
            foreach ($this->httpClient->stream($responses) as $response => $chunk) {
                try {
                    if ($chunk->isTimeout()) {
                        $response->cancel();
                    }
                    if ($chunk->isFirst()) {
                        if (200 !== $response->getStatusCode()) {
                            $response->cancel();
                        }
                    }
                    if ($chunk->isLast()) {
                        $results[] = $response;
                    }
                } catch (TransportExceptionInterface $e) {
                    dd($e);
                }
            }

            list($allLinks, $responses) = $this->parseResults($results, $allLinks);
        }

        dd($allLinks);

        return Command::SUCCESS;
    }

    protected function parseResults(array $results, array $allLinks): array
    {
        $responses = [];

        foreach ($results as $result) {
            $uri = UriResolver::resolve('/', $result->getInfo('url'));

            $crawler = new Crawler($result->getContent(), $uri);
            $linksFromUri = $crawler->filter('a')->links();

            $links = array_unique(array_map(function (Link $link) {
                return $link->getUri();
            }, $linksFromUri));

            foreach ($links as $link) {
                if (UriResolver::resolve('/', $link) === $uri) {
                    if (!array_key_exists($link, $allLinks)) {
                        $allLinks[$link] = [];
                        $responses[] = $this->httpClient->request('GET', $link);
                    }
                } else {
                    $link = UriResolver::resolve('/', $link);

                    if (!array_key_exists($uri, $allLinks) || !in_array($link, $allLinks[$uri])) {
                        $allLinks[$uri][] = $link;
                    }
                }
            }
        }

        return [$allLinks, $responses];
    }
}
