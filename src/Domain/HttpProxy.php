<?php

declare(strict_types=1);

namespace App\Domain;

class HttpProxy
{
    private string $host;
    private int $port;
    private int $use = 0;

    public function __construct(string $proxy)
    {
        list($host, $port) = explode(':', $proxy);

        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            throw new \LogicException(sprintf('Http proxy object get invalid host value "%s"', $host));
        }
        $port = (int) $port;
        if ($port > 65535 || $port < 80) {
            throw new \LogicException(sprintf('Http proxy object get invalid port value "%s"', $port));
        }

        $this->host = $host;
        $this->port = $port;
    }

    public function __toString(): string
    {
        return sprintf('%s:%s', $this->host, $this->port);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUse(): int
    {
        return $this->use;
    }

    public function incrementUse(): void
    {
        $this->use++;
    }
}
