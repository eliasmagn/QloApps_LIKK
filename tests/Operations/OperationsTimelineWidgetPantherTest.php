<?php

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Process\Process;

class OperationsTimelineWidgetPantherTest extends PantherTestCase
{
    /** @var Process|null */
    private static $server;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $router = realpath(__DIR__ . '/fixtures/router.php');
        if ($router === false) {
            self::markTestSkipped('Operations router fixture is missing.');
        }

        self::$server = new Process(array(PHP_BINARY, '-S', '127.0.0.1:9081', $router), dirname($router));
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

    public function testTimelineWidgetVisibleWhenModuleEnabled(): void
    {
        $client = $this->createClientOrSkip();

        $crawler = $client->request('GET', 'http://127.0.0.1:9081/admin/panel?module=kloperations');
        $client->waitFor('[data-kloperations-widget="timeline"]');

        $this->assertGreaterThan(0, $crawler->filter('[data-kloperations-widget="timeline"]')->count(), 'Widget wrapper missing');
        $this->assertGreaterThan(0, $crawler->filter('[data-kloperations-widget-table] tbody tr')->count(), 'Summary rows missing');
        $this->assertGreaterThan(0, $crawler->filter('[data-kloperations-widget="timeline"] a[href*="AdminKlOperationTasks"]')->count(), 'Console link missing');
    }

    public function testRoomRowShowsQuickLinks(): void
    {
        $client = $this->createClientOrSkip();

        $crawler = $client->request('GET', 'http://127.0.0.1:9081/admin/panel?module=kloperations');
        $client->waitFor('[data-kloperations-resource="room"]');

        $roomRow = $crawler->filter('[data-kloperations-resource="room"]');
        $this->assertSame('Rooms', trim($roomRow->filter('strong')->text()));
        $this->assertGreaterThan(0, $roomRow->filter('a.label.label-warning')->count(), 'Expected status quick link badges');
        $this->assertGreaterThan(0, $roomRow->filter('a.btn-link')->count(), 'Expected view tasks quick link');
    }

    private function createClientOrSkip()
    {
        try {
            if (!isset($_SERVER['PANTHER_CHROME_ARGUMENTS'])) {
                $_SERVER['PANTHER_CHROME_ARGUMENTS'] = '--headless --disable-gpu --no-sandbox';
            }

            return static::createPantherClient(array(
                'external_base_uri' => 'http://127.0.0.1:9081',
                'browser' => static::CHROME,
            ));
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Panther prerequisites missing: ' . $exception->getMessage());
        }
    }
}
