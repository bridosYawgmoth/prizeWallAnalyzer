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

    #[TestWith(['url' => 'https://www.mtgfestivals.com/prize-wall/123'], 'Normal Url for mtgfestivals is correctly identified')]
    #[TestWith(['url' => 'https://events.mtgfestivals.com/wall'], 'Subdomain for mtgfestivals is correctly identified')]
    #[TestWith(['url' => 'https://mtgfestivals.com/wall?round=2'], 'If url contains queryparams it is correctly identified')]
    public function testReturnsPastimeEventsAdapterForMtgFestivalsUrl(string $url): void
    {
        $adapter = $this->factory->getForUrl($url);

        $this->assertInstanceOf(PastimeEventsAdapter::class, $adapter);
    }

    #[TestWith(['url' => 'https://fanfinity.gg/wall'], 'Normal url for fanfinity is correctly identified')]
    #[TestWith(['url' => 'https://fanfinity.gg/wall?event=rc-london'], 'If fanfinity url contains queryparams it is still correctly identified')]
    public function testReturnsFanfinityAdapterForFanfinityUrl(string $url): void
    {
        $adapter = $this->factory->getForUrl($url);

        $this->assertInstanceOf(FanfinityAdapter::class, $adapter);
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
