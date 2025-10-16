<?php

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Process\Process;

class StorytellingTemplatePantherTest extends PantherTestCase
{
    /** @var Process|null */
    private static $server;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $router = realpath(__DIR__.'/fixtures/router.php');
        if ($router === false) {
            self::markTestSkipped('Storytelling router fixture is missing.');
        }

        self::$server = new Process(array(PHP_BINARY, '-S', '127.0.0.1:9080', $router), dirname($router));
        self::$server->start();
        usleep(500000);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server) {
            self::$server->stop();
        }

        parent::tearDownAfterClass();
    }

    public function testResidenciesTemplateRendersHeroPackagesAndAvailability(): void
    {
        $client = $this->createClientOrSkip();

        $crawler = $client->request('GET', 'http://127.0.0.1:9080/story/residencies');
        $client->waitFor('.kl-storytelling');

        $storytelling = $crawler->filter('.kl-storytelling');
        $this->assertGreaterThan(0, $storytelling->count(), 'Storytelling root missing');
        $this->assertSame('residencies', $storytelling->attr('data-kl-storytelling-resource'));
        $this->assertSame(
            '/api/storytelling/residencies/testimonials',
            $storytelling->attr('data-kl-storytelling-testimonials-endpoint')
        );
        $this->assertSame(
            '/api/storytelling/residencies/faq',
            $storytelling->attr('data-kl-storytelling-faq-endpoint')
        );

        $this->assertGreaterThan(0, $crawler->filter('.kl-storytelling__hero')->count(), 'Hero block missing');
        $this->assertGreaterThan(0, $crawler->filter('.kl-storytelling__section')->count(), 'Story sections missing');
        $this->assertGreaterThan(0, $crawler->filter('.kl-storytelling__package')->count(), 'Package cards missing');
        $this->assertGreaterThan(0, $crawler->filter('.kl-storytelling__availability-slot')->count(), 'Availability slots missing');
        $this->assertSame(
            'testimonials',
            $crawler->filter('[data-kl-storytelling-slot="testimonials"]')->attr('data-kl-storytelling-slot')
        );
        $this->assertSame(
            'faq',
            $crawler->filter('[data-kl-storytelling-slot="faq"]')->attr('data-kl-storytelling-slot')
        );
        $this->assertStringContainsString(
            'utm_source=story_residencies_package',
            $crawler->filter('.kl-storytelling__package-actions a')->attr('href')
        );
    }

    public function testLighthouseThresholdsMeetNavigationTargets(): void
    {
        $client = $this->createClientOrSkip();

        $client->request('GET', 'http://127.0.0.1:9080/story/residencies');
        $client->waitFor('.kl-storytelling');

        $navigation = $client->executeScript('return (performance.getEntriesByType("navigation")[0] || null);');
        if (!$navigation) {
            $this->markTestSkipped('Navigation timing entry unavailable for Lighthouse assertions.');
        }

        $this->assertLessThan(2000, $navigation['domContentLoadedEventEnd'], 'DOM Content Loaded should complete within 2s.');
        $this->assertLessThan(2500, $navigation['loadEventEnd'], 'Load event should complete within 2.5s.');
    }

    private function createClientOrSkip()
    {
        try {
            if (!isset($_SERVER['PANTHER_CHROME_ARGUMENTS'])) {
                $_SERVER['PANTHER_CHROME_ARGUMENTS'] = '--headless --disable-gpu --no-sandbox';
            }

            return static::createPantherClient(array(
                'external_base_uri' => 'http://127.0.0.1:9080',
                'browser' => static::CHROME,
            ));
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Panther prerequisites missing: '.$exception->getMessage());
        }
    }
}
