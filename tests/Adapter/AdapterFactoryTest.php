<?php

namespace App\Tests\Adapter;

use App\Adapter\AdapterFactory;
use App\Adapter\FanfinityAdapter;
use App\Adapter\PastimeEventsAdapter;
use App\Exception\UnsupportedOrganizerException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class AdapterFactoryTest extends TestCase
{
    private AdapterFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new AdapterFactory();
    }

    #[TestWith(['url' => 'https://www.mtgfestivals.com/prize-wall/123', 'expectedClass' => PastimeEventsAdapter::class], 'Normal Url for mtgfestivals is correctly identified')]
    #[TestWith(['url' => 'https://events.mtgfestivals.com/wall', 'expectedClass' => PastimeEventsAdapter::class], 'Subdomain for mtgfestivals is correctly identified')]
    #[TestWith(['url' => 'https://mtgfestivals.com/wall?round=2', 'expectedClass' => PastimeEventsAdapter::class], 'If url contains queryparams it is correctly identified')]
    #[TestWith(['url' => 'https://fanfinity.gg/wall', 'expectedClass' => FanfinityAdapter::class], 'Normal url for fanfinity is correctly identified')]
    #[TestWith(['url' => 'https://fanfinity.gg/wall?event=rc-london', 'expectedClass' => FanfinityAdapter::class], 'If fanfinity url contains queryparams it is still correctly identified')]
    public function testReturnsCorrectAdapterForUrl(string $url, string $expectedClass): void
    {
        $adapter = $this->factory->getForUrl($url);

        $this->assertInstanceOf($expectedClass, $adapter);
    }

    #[TestWith(['url' => 'https://unknown-organizer.com/wall'], 'Completely unknown domain throws exception')]
    #[TestWith(['url' => 'https://pastimes.com/wall'], 'Old pastimes.com domain is no longer supported')]
    #[TestWith(['url' => 'https://magiccon.com/wall'], 'Old magiccon.com domain is no longer supported')]
    #[TestWith(['url' => 'not-a-url'], 'Invalid URL string throws exception')]
    #[TestWith(['url' => ''], 'Empty string throws exception')]
    public function testThrowsUnsupportedOrganizerExceptionForUnsupportedUrl(string $url): void
    {
        $this->expectException(UnsupportedOrganizerException::class);

        $this->factory->getForUrl($url);
    }
}
