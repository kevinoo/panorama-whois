<?php

namespace kevinoo\PanoramaWhois\Tests;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use kevinoo\PanoramaWhois\Models\Domain;
use Orchestra\Testbench\TestCase as BaseTestCase;
use kevinoo\PanoramaWhois\Helpers;
use kevinoo\PanoramaWhois\Support\Facades\PanoramaWhois;


class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.debug', true);
        $app['config']->set('panorama-whois', (include dirname(__DIR__) .'/config/config.php') );
        $app['config']->set('database',[
            'default' => 'panorama-whois',
            'connections' => [
                'panorama-whois' => [
                    'driver' => 'sqlite',
                    'host' => dirname(__DIR__) .'/database/panorama-whois.sqlite',
                    'database' => dirname(__DIR__) .'/database/panorama-whois.sqlite',
                ],
                'panorama-whois-cache' => [
                    'driver' => 'sqlite',
                    'host' => dirname(__DIR__) .'/database/cache.sqlite',
                    'database' => dirname(__DIR__) .'/database/cache.sqlite',
                ],
            ]
        ]);

        static::buildDatabaseConnection();
    }

    /**
     * Build database connections
     */
    public static function buildDatabaseConnection(): void
    {
        $capsule = new DB();
        $capsule->addConnection( config('database.connections.panorama-whois'), 'panorama-whois' );
        $capsule->addConnection( config('database.connections.panorama-whois-cache'), 'panorama-whois-cache' );
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    public static function tearDownAfterClass(): void
    {
        Domain::find('facebook.com')->delete();
        parent::tearDownAfterClass();
    }

    /**
     * @throws Exception
     */
    public function test_facebook_com(): void
    {
        $whois = PanoramaWhois::getWhoIS('facebook.com',false);

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
    public function test_countries(): void
    {
        static::assertEquals('ITA', Helpers::getCountryISO3('IT') );
        static::assertEquals('USA', Helpers::getCountryISO3('US') );
    }

    /**
     * @throws Exception
     */
    public function test_config(): void
    {
        static::assertTrue( config( 'app.debug' ) );
        static::assertCount(3, PanoramaWhois::getProviders() );
    }

    /**
     * @throws Exception
     */
    public function test_domains_model(): void
    {
        $domain = Domain::find('facebook.com');

        static::assertNotEmpty($domain);
        static::assertNotEmpty($domain['who_is_data']);
    }

    /**
     * @throws Exception
     */
    public function test_cached_option(): void
    {
        $whois = PanoramaWhois::getWhoIS('facebook.com', false);
        $whois_cached = PanoramaWhois::getWhoIS('facebook.com',true);

        self::assertEquals($whois['last_update'],$whois_cached['last_update']);
    }
}
