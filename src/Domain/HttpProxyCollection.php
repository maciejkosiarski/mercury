<?php

declare(strict_types=1);

namespace App\Domain;

class HttpProxyCollection implements \Iterator
{
    private int $position = 0;
    private array $proxies;

    public function __construct(array $proxies)
    {
        $this->addProxies($proxies);
    }

    public function findLeastUsed(): HttpProxy
    {
        $position = 0;

        foreach ($this->proxies as $key => $proxy) {
            if (!isset($leastUsed)) {
                $leastUsed = $proxy;
            }

            if ($leastUsed->getUse() > $proxy->getUse() ) {
                $leastUsed = $proxy;
                $position = $key;
            }
        }

        if (!isset($leastUsed)) {
            throw new \LogicException('Proxy collection is empty, can\'t find nothing!');
        }

        $this->proxies[$position]->incrementUse();

        return $leastUsed;
    }

    public function exist(HttpProxy $needle): bool
    {
        $filteredCollection = array_filter($this->proxies, function (HttpProxy $proxy) use ($needle) {
            return ($proxy->getHost() === $needle->getHost() && $proxy->getPort() === $needle->getPort());
        });

        return $filteredCollection > 0;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): HttpProxy
    {
        return $this->proxies[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->proxies[$this->position]);
    }

    public function addProxy(string $proxy): void
    {
        $proxy = new HttpProxy($proxy);

        if ($this->exist($proxy)) {
            throw new \LogicException(sprintf('Http proxy "%s" already exist in collection', $proxy));
        }

        $this->proxies[] = $proxy;
    }

    protected function addProxies(array $proxies): void
    {
        foreach ($proxies as $proxy) {
            $this->addProxy($proxy);
        }
    }
}
