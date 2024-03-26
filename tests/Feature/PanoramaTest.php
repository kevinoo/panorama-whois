<?php

namespace Tests\Feature;

use Exception;
use kevinoo\PanoramaWhois\PanoramaWhoIs;
use Tests\TestCase;


class PanoramaTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_facebook_com(): void
    {
        $whois = PanoramaWhoIs::getWhoIS('facebook.com');

        static::assertNotEmpty($whois);
        static::assertIsArray($whois);
        static::assertEquals('3237',$whois['registrar']['code']);
        static::assertEquals('1997-03-29T05:00:00Z',$whois['domain']['created_at']);
        static::assertCount(4,$whois['domain']['dns']);
        static::assertStringContainsString('Meta',$whois['admin']['name']);
        static::assertEquals('USA',$whois['admin']['country']);
        static::assertStringContainsString('fb.com',$whois['technical']['email']);
    }

    /**
     * @throws Exception
     */
    public function test_config(): void
    {
        $whois = PanoramaWhoIs::defaultProviders();

    }

    /**
     * @throws Exception
     */
    public function test_cached_option(): void
    {
        $whois = PanoramaWhoIs::getWhoIS('facebook.com');
        $whois_cached = PanoramaWhoIs::getWhoIS('facebook.com',true);
        // TODO.
    }
}
