<?php

use PHPUnit\Framework\TestCase;

$rootDir = dirname(__DIR__, 2);

require_once $rootDir.'/classes/Context.php';
require_once $rootDir.'/classes/Tools.php';
require_once $rootDir.'/classes/cache/Cache.php';
require_once $rootDir.'/modules/hotelreservationsystem/classes/HotelReservationSystemStorytellingPresenter.php';
require_once $rootDir.'/modules/hotelreservationsystem/classes/KLResourceProfile.php';

if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

class StorytellingPresenterTestDouble extends HotelReservationSystemStorytellingPresenter
{
    /** @var array<int, array<string, mixed>> */
    private $profiles;

    /** @var array<string, array<string, mixed>> */
    private $metadata;

    /** @var int */
    public $snapshotBuilds = 0;

    /** @var object */
    private $link;

    public function __construct(array $profiles = array(), array $metadata = array())
    {
        $this->link = new class() {
            public function getPageLink($controller, $ssl = null, $idLang = null, $request = null, $idShop = null, $idShopGroup = null, $relativeProtocol = false)
            {
                $query = $request ? http_build_query($request, '', '&', PHP_QUERY_RFC3986) : '';

                return 'https://example.test/'.$controller.($query ? '?'.$query : '');
            }
        };

        $context = new Context();
        $context->language = (object) array('id' => 1);
        $context->shop = (object) array('id' => 1);
        $context->link = $this->link;

        parent::__construct($context);

        $this->profiles = $profiles;
        $this->metadata = $metadata;
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array<int, string> $allowed
     *
     * @return array<string, array<string, mixed>>
     */
    public function exposeSectionGroups($idLang = 1, $idShop = 1, array $allowed = array())
    {
        return $this->groupProfilesByKind($idLang, $idShop, $allowed);
    }

    /**
     * @param array<string, mixed> $profile
     *
     * @return array<int, string>
     */
    public function exposeCapacitySummary(array $profile)
    {
        return $this->summariseCapacity($profile);
    }

    /**
     * @param array<string, mixed> $package
     * @param string|null $scope
     * @param array<string, string> $base
     *
     * @return array<string, mixed>
     */
    public function exposePackageEntry(array $package, $scope, array $base)
    {
        return $this->buildPackageEntryForStorytelling($package, $scope, $base, $this->link, 1, 1);
    }

    /**
     * @param array<int, string> $allowed
     *
     * @return array<string, mixed>
     */
    public function exposeAvailabilitySnapshot(array $allowed = array())
    {
        return $this->buildAvailabilitySnapshot(1, 1, $allowed);
    }

    protected function resolvePackageHighlight(array $package, $idLang, $idShop)
    {
        return array('status' => 'stub');
    }

    protected function getPublishedProfiles($idLang, $idShop)
    {
        return $this->profiles;
    }

    protected function getSectionMetadata($idLang, $idShop, array $allowedResourceKinds = array())
    {
        return $this->metadata;
    }

    protected function formatProfileForSection(array $profile)
    {
        return array(
            'resource_code' => $profile['resource_code'],
        );
    }

    protected function findNextAvailabilityForProfile(array $profile, DateTimeImmutable $start, DateTimeImmutable $horizon)
    {
        ++$this->snapshotBuilds;

        return array(
            'resource_kind' => $profile['resource_kind'],
            'start' => $start,
            'end' => $start->add(new DateInterval('P1D')),
            'profile' => $profile,
            'nights' => 1,
            'available_rooms' => 1,
            'total_rooms' => 1,
            'inquiry_params' => array('resource_kind' => $profile['resource_kind']),
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

class StorytellingPresenterTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::clear();
        Tools::$round_mode = PS_ROUND_HALF_UP;
    }

    public function testSectionGroupingHonoursMetadataOrdering(): void
    {
        $profiles = array(
            array('resource_kind' => 'atelier', 'resource_code' => 'AT-1'),
            array('resource_kind' => 'room', 'resource_code' => 'RM-1'),
            array('resource_kind' => 'room', 'resource_code' => 'RM-2'),
            array('resource_kind' => 'campus', 'resource_code' => 'CP-1'),
        );
        $metadata = array(
            'room' => array(
                'key' => 'residencies',
                'title' => 'Residencies',
                'anchor' => 'residencies',
                'intro' => 'Residency intro',
            ),
            'atelier' => array(
                'key' => 'ateliers',
                'title' => 'Ateliers',
                'anchor' => 'ateliers',
                'intro' => 'Atelier intro',
            ),
        );

        $presenter = new StorytellingPresenterTestDouble($profiles, $metadata);
        $sections = $presenter->exposeSectionGroups(1, 1, array('room', 'atelier'));

        $this->assertSame(array('residencies', 'ateliers'), array_keys($sections));
        $this->assertSame(array('RM-1', 'RM-2'), array_column($sections['residencies']['profiles'], 'resource_code'));
        $this->assertSame(array('AT-1'), array_column($sections['ateliers']['profiles'], 'resource_code'));
        $this->assertArrayNotHasKey('campus', $sections);
    }

    public function testSummariseCapacityBuildsReadableSummary(): void
    {
        $presenter = new StorytellingPresenterTestDouble();
        $profile = array(
            'capacity' => array(
                'total' => 4,
                'adults' => 2,
                'children' => 2,
                'seated' => 20,
                'standing' => 40,
                'floor_area_sqm' => 120.457,
                'ceiling_height_m' => 3.4,
            ),
            'capacity_notes' => '<p>Private mezzanine lounge</p>',
            'is_bookable' => false,
        );

        $summary = $presenter->exposeCapacitySummary($profile);

        $this->assertContains('Sleeps up to 4 guests (2 adults + 2 children).', $summary);
        $this->assertContains('Seated capacity: 20', $summary);
        $this->assertContains('Standing capacity: 40', $summary);
        $this->assertContains('Floor area: 120.46 m²', $summary);
        $this->assertContains('Ceiling height: 3.4 m', $summary);
        $this->assertContains('Private mezzanine lounge', $summary);
        $this->assertContains('Available on request via inquiry.', $summary);
    }

    public function testBuildPackageEntryGeneratesInquiryPayload(): void
    {
        $presenter = new StorytellingPresenterTestDouble();

        $package = array(
            'id_kl_package' => 9,
            'package_code' => 'WINTER-RETREAT',
            'name' => 'Winter Retreat',
            'tagline' => 'A cosy winter stay',
            'description' => 'Includes meals',
        );

        $entry = $presenter->exposePackageEntry($package, 'room', array('utm_source' => 'story_residencies_package'));

        $this->assertSame('WINTER-RETREAT', $entry['package_code']);
        $this->assertSame('room', $entry['primary_scope']);
        $this->assertSame(
            array(
                'utm_source' => 'story_residencies_package',
                'resource_kind' => 'room',
                'package_code' => 'WINTER-RETREAT',
            ),
            $entry['inquiry_params']
        );
        $this->assertStringContainsString('https://example.test/inquiry?', $entry['inquiry_url']);
        $this->assertSame(array('status' => 'stub'), $entry['highlight']);
    }

    public function testAvailabilitySnapshotCachesResults(): void
    {
        $profiles = array(
            array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_ROOM,
                'resource_code' => 'RM-1',
                'is_bookable' => true,
                'id_product' => 1,
                'id_room_type' => 1,
            ),
        );
        $metadata = array(
            KLResourceProfile::RESOURCE_KIND_ROOM => array(
                'key' => 'residencies',
                'title' => 'Residencies',
                'anchor' => 'residencies',
                'intro' => '',
            ),
        );

        $presenter = new StorytellingPresenterTestDouble($profiles, $metadata);

        $first = $presenter->exposeAvailabilitySnapshot();
        $this->assertSame(1, $presenter->snapshotBuilds);

        $second = $presenter->exposeAvailabilitySnapshot();
        $this->assertSame(1, $presenter->snapshotBuilds, 'snapshot should reuse cache');
        $this->assertSame($first, $second);
    }
}
