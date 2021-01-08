<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\HttpProxy;
use PHPUnit\Framework\TestCase;

class HttpProxyTest extends TestCase
{
    private string $validHost = '127.0.0.1';
    private string $invalidHost = '127.259.0.1';
    private int $validPort = 80;
    private int $invalidPort = 70000;

    public function testShouldCreateValidUnusedHttpProxyObject(): void
    {
        $proxy = new HttpProxy(sprintf('%s:%s', $this->validHost, $this->validPort));

        $this->assertEquals($this->validHost, $proxy->getHost());
        $this->assertEquals($this->validPort, $proxy->getPort());
        $this->assertEquals(0, $proxy->getUse());
    }

    public function testShouldThrowExceptionBecauseOfInvalidHost(): void
    {
        $this->expectExceptionMessage(sprintf('Http proxy object get invalid host value "%s"', $this->invalidHost));
        new HttpProxy(sprintf('%s:%s', $this->invalidHost, $this->validPort));
    }

    public function testShouldThrowExceptionBecauseOfInvalidPort(): void
    {
        $this->expectExceptionMessage(sprintf('Http proxy object get invalid port value "%s"', $this->invalidPort));
        new HttpProxy(sprintf('%s:%s', $this->validHost, $this->invalidPort));
    }

    public function testShouldIncrementUseOfProxyTwice(): void
    {
        $proxy = new HttpProxy(sprintf('%s:%s', $this->validHost, $this->validPort));
        $proxy->incrementUse();
        $proxy->incrementUse();
        $this->assertEquals(2, $proxy->getUse());
    }
}
