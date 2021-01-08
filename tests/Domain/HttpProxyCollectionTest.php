<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\HttpProxy;
use App\Domain\HttpProxyCollection;
use PHPUnit\Framework\TestCase;

class HttpProxyCollectionTest extends TestCase
{

    public function testShouldFindLeastUsedProxy(): void
    {
        $httpProxy1 = new HttpProxy('127.0.0.1:80');
        $httpProxy1->incrementUse();
        $httpProxy1->incrementUse();

        $httpProxy2 = new HttpProxy('127.0.0.2:80');
        $httpProxy2->incrementUse();
        $httpProxy2->incrementUse();
        $httpProxy2->incrementUse();

        $httpProxy3 = new HttpProxy('127.0.0.3:80');
        $httpProxy3->incrementUse();

        $collection = new HttpProxyCollection([$httpProxy1, $httpProxy2, $httpProxy3]);

        $this->assertEquals($httpProxy3, $collection->findLeastUsed());
    }

    public function testShouldSuccessfullyAddProxyToCollection(): void
    {
        $httpProxy1 = new HttpProxy('127.0.0.1:80');
        $httpProxy2 = new HttpProxy('127.0.0.2:80');
        $httpProxy3 = new HttpProxy('127.0.0.3:80');

        $collection = new HttpProxyCollection([$httpProxy1, $httpProxy2]);

        $this->assertEquals(2, $collection->count());

        $collection->addProxy($httpProxy3);

        $this->assertEquals(3, $collection->count());
    }

    public function testShouldSuccessfullyIterateThroughCollection(): void
    {
        $httpProxy1 = new HttpProxy('127.0.0.1:80');
        $httpProxy2 = new HttpProxy('127.0.0.2:80');
        $httpProxy3 = new HttpProxy('127.0.0.3:80');

        $collection = new HttpProxyCollection([$httpProxy1, $httpProxy2, $httpProxy3]);

        $this->assertInstanceOf(\Iterator::class, $collection);
    }

    public function testShouldCheckIfProxyExistInCollection(): void
    {
        $httpProxy1 = new HttpProxy('127.0.0.1:80');
        $httpProxy2 = new HttpProxy('127.0.0.2:80');
        $httpProxy3 = new HttpProxy('127.0.0.3:80');

        $collection = new HttpProxyCollection([$httpProxy1, $httpProxy2]);

        $this->assertTrue($collection->exist($httpProxy1));
        $this->assertFalse($collection->exist($httpProxy3));
    }
}
