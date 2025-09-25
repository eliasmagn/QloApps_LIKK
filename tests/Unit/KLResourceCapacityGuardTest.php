<?php

if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

require_once dirname(dirname(__DIR__)).'/modules/hotelreservationsystem/classes/KLResourceCapacityGuard.php';
require_once dirname(dirname(__DIR__)).'/modules/hotelreservationsystem/classes/KLResourceCapacity.php';
require_once dirname(dirname(__DIR__)).'/modules/hotelreservationsystem/classes/KLAmenityLink.php';

class StubDb
{
    /** @var array<string, mixed>|false */
    public $roomTypeRow;

    /** @var array<int, array<string, mixed>> */
    public $bookingRows;

    public function __construct($roomTypeRow, array $bookingRows)
    {
        $this->roomTypeRow = $roomTypeRow;
        $this->bookingRows = $bookingRows;
    }

    public function getRow($sql)
    {
        return $this->roomTypeRow;
    }

    public function executeS($sql)
    {
        return $this->bookingRows;
    }
}

class KLResourceCapacityGuardTest extends PHPUnit_Framework_TestCase
{
    public function testNoRoomTypeSkipsChecks()
    {
        $db = new StubDb(false, array());
        $guard = new KLResourceCapacityGuard($db);

        $conflicts = $guard->findBlockingBookings(4, 0, array('capacity_total' => 2));

        $this->assertEmpty($conflicts);
    }

    public function testUnlimitedCapacitySkipsConflicts()
    {
        $db = new StubDb(array('id_product' => 9), array(
            array(
                'id' => 11,
                'adults' => 3,
                'children' => 0,
                'date_from' => '2024-07-01',
                'date_to' => '2024-07-04',
            ),
        ));
        $guard = new KLResourceCapacityGuard($db);

        $conflicts = $guard->findBlockingBookings(7, 5, array(
            'capacity_total' => '',
            'capacity_adults' => '',
            'capacity_children' => '',
        ));

        $this->assertEmpty($conflicts);
    }

    public function testDetectsBookingsExceedingProposedCapacity()
    {
        $bookings = array(
            array(
                'id' => 21,
                'adults' => 2,
                'children' => 1,
                'date_from' => '2024-08-01',
                'date_to' => '2024-08-05',
            ),
            array(
                'id' => 22,
                'adults' => 1,
                'children' => 0,
                'date_from' => '2024-09-01',
                'date_to' => '2024-09-02',
            ),
        );
        $db = new StubDb(array('id_product' => 14), $bookings);
        $guard = new KLResourceCapacityGuard($db);

        $conflicts = $guard->findBlockingBookings(9, 3, array(
            'capacity_total' => 2,
            'capacity_adults' => 1,
            'capacity_children' => 1,
        ));

        $this->assertCount(1, $conflicts);
        $this->assertEquals(21, $conflicts[0]['id_booking']);
        $this->assertEquals('total', $conflicts[0]['dimension']);
        $this->assertEquals(3, $conflicts[0]['required']);
        $this->assertEquals(2, $conflicts[0]['limit']);
    }
}
