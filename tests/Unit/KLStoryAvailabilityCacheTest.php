<?php

use PHPUnit\Framework\TestCase;

if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', dirname(dirname(__DIR__)));
}
if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', _PS_ROOT_DIR_.'/modules/');
}

require_once _PS_ROOT_DIR_.'/classes/cache/Cache.php';
require_once _PS_ROOT_DIR_.'/classes/Context.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/KLStoryAvailabilityCache.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/HotelReservationSystemStorytellingPresenter.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/KLResourceProfile.php';

class StoryPresenterTestDouble extends HotelReservationSystemStorytellingPresenter
{
    /** @var int */
    public $slotCalls = 0;

    public function __construct()
    {
        $context = new Context();
        $context->language = (object) array('id' => 1);
        $context->shop = (object) array('id' => 1);

        parent::__construct($context);
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array<int, string> $resourceKinds
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot($idLang = 1, $idShop = 1, array $resourceKinds = array())
    {
        return $this->buildAvailabilitySnapshot($idLang, $idShop, $resourceKinds);
    }

    protected function getPublishedProfiles($idLang, $idShop)
    {
        return array(
            array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_ROOM,
                'is_bookable' => true,
                'id_product' => 1,
                'id_room_type' => 1,
            ),
        );
    }

    protected function getSectionMetadata($idLang, $idShop, array $allowedResourceKinds = array())
    {
        return array(
            KLResourceProfile::RESOURCE_KIND_ROOM => array(
                'key' => 'residencies',
                'title' => 'Residencies',
                'anchor' => 'residencies',
                'intro' => '',
            ),
        );
    }

    protected function findNextAvailabilityForProfile(array $profile, DateTimeImmutable $start, DateTimeImmutable $horizon)
    {
        ++$this->slotCalls;
        $slotStart = new DateTimeImmutable(sprintf('2024-01-%02d', $this->slotCalls));
        $slotEnd = $slotStart->add(new DateInterval('P1D'));

        return array(
            'resource_kind' => $profile['resource_kind'],
            'start' => $slotStart,
            'end' => $slotEnd,
            'profile' => array('resource_kind' => $profile['resource_kind']),
            'nights' => 1,
            'available_rooms' => 1,
            'total_rooms' => 1,
            'inquiry_params' => array('sequence' => $this->slotCalls),
        );
    }

    protected function normaliseSlotForTemplate(array $slot, array $sectionMetadata = array())
    {
        return array(
            'resource_kind' => $slot['resource_kind'],
            'start' => $slot['start']->format('Y-m-d'),
            'end' => $slot['end']->format('Y-m-d'),
            'inquiry_params' => $slot['inquiry_params'],
        );
    }

    protected function getTranslator()
    {
        return null;
    }
}

class KLStoryAvailabilityCacheTest extends TestCase
{
    protected function setUp()
    {
        Cache::clear();
    }

    public function testInvalidateAllClearsEverySnapshot()
    {
        Cache::store('KL_STORY_AVAILABILITY_1_1', array('payload' => 'foo'));
        Cache::store('KL_STORY_AVAILABILITY_2_1', array('payload' => 'bar'));

        KLStoryAvailabilityCache::invalidateAll();

        $this->assertFalse(Cache::isStored('KL_STORY_AVAILABILITY_1_1'));
        $this->assertFalse(Cache::isStored('KL_STORY_AVAILABILITY_2_1'));
    }

    public function testInvalidateForShopIsScoped()
    {
        Cache::store('KL_STORY_AVAILABILITY_3_1', array('payload' => 'foo'));
        Cache::store('KL_STORY_AVAILABILITY_4_1', array('payload' => 'bar'));

        KLStoryAvailabilityCache::invalidateForShop(3);

        $this->assertFalse(Cache::isStored('KL_STORY_AVAILABILITY_3_1'));
        $this->assertTrue(Cache::isStored('KL_STORY_AVAILABILITY_4_1'));
    }

    public function testSnapshotRebuildsAfterInvalidation()
    {
        $presenter = new StoryPresenterTestDouble();

        $first = $presenter->buildSnapshot(1, 1);
        $this->assertEquals(1, $presenter->slotCalls);

        $second = $presenter->buildSnapshot(1, 1);
        $this->assertEquals(1, $presenter->slotCalls);
        $this->assertEquals($first, $second);

        KLStoryAvailabilityCache::invalidateForShop(1);

        $third = $presenter->buildSnapshot(1, 1);
        $this->assertEquals(2, $presenter->slotCalls);
        $this->assertEquals(1, count($third['slots']));
        $this->assertNotEquals(
            $second['slots'][0]['inquiry_params']['sequence'],
            $third['slots'][0]['inquiry_params']['sequence']
        );
    }
}
