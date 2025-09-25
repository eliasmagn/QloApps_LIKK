<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/license/osl-3-0-php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@qloapps.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to a newer
 * versions in the future. If you wish to customize this module for your needs
 * please refer to https://store.webkul.com/customisation-guidelines for more information.
 *
 * @author Webkul IN
 * @copyright Since 2010 Webkul
 * @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
 */

class KLResourceCapacityGuard
{
    /** @var Db */
    private $db;

    /**
     * @param Db|null $db
     */
    public function __construct($db = null)
    {
        $this->db = $db ?: Db::getInstance();
    }

    /**
     * Detect bookings that would exceed the proposed capacity values.
     *
     * @param int   $idProfile
     * @param int   $idRoomType
     * @param array $candidateCapacity keyed array of capacity fields
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBlockingBookings($idProfile, $idRoomType, array $candidateCapacity)
    {
        $idProfile = (int) $idProfile;
        $idRoomType = (int) $idRoomType;

        if ($idRoomType <= 0) {
            return array();
        }

        $roomTypeRow = $this->db->getRow(
            'SELECT hrt.`id_product`
            FROM `' . $this->prefix('htl_room_type') . '` hrt
            WHERE hrt.`id` = ' . (int) $idRoomType
        );

        if (!$roomTypeRow || empty($roomTypeRow['id_product'])) {
            return array();
        }

        $idProduct = (int) $roomTypeRow['id_product'];
        if ($idProduct <= 0) {
            return array();
        }

        $today = $this->escape(date('Y-m-d'));

        $rows = $this->db->executeS(
            'SELECT hbd.`id`, hbd.`adults`, hbd.`children`, hbd.`date_from`, hbd.`date_to`
            FROM `' . $this->prefix('htl_booking_detail') . '` hbd
            WHERE hbd.`id_product` = ' . (int) $idProduct . '
                AND hbd.`is_cancelled` = 0
                AND hbd.`is_refunded` = 0
                AND hbd.`date_to` >= \'' . $today . '\''
        );

        if (!is_array($rows) || empty($rows)) {
            return array();
        }

        $limits = $this->normaliseCandidate($candidateCapacity);
        if ($limits['capacity_total'] === null
            && $limits['capacity_adults'] === null
            && $limits['capacity_children'] === null
        ) {
            return array();
        }

        $conflicts = array();
        foreach ($rows as $row) {
            $adults = (int) $row['adults'];
            $children = (int) $row['children'];
            $total = $adults + $children;

            if ($limits['capacity_adults'] !== null && $adults > $limits['capacity_adults']) {
                $conflicts[] = array(
                    'id_booking' => (int) $row['id'],
                    'dimension' => 'adults',
                    'required' => $adults,
                    'limit' => (int) $limits['capacity_adults'],
                    'date_from' => $row['date_from'],
                    'date_to' => $row['date_to'],
                );
                continue;
            }

            if ($limits['capacity_children'] !== null && $children > $limits['capacity_children']) {
                $conflicts[] = array(
                    'id_booking' => (int) $row['id'],
                    'dimension' => 'children',
                    'required' => $children,
                    'limit' => (int) $limits['capacity_children'],
                    'date_from' => $row['date_from'],
                    'date_to' => $row['date_to'],
                );
                continue;
            }

            if ($limits['capacity_total'] !== null && $total > $limits['capacity_total']) {
                $conflicts[] = array(
                    'id_booking' => (int) $row['id'],
                    'dimension' => 'total',
                    'required' => $total,
                    'limit' => (int) $limits['capacity_total'],
                    'date_from' => $row['date_from'],
                    'date_to' => $row['date_to'],
                );
            }
        }

        return $conflicts;
    }

    /**
     * @param array<string, mixed> $candidateCapacity
     *
     * @return array<string, int|null>
     */
    protected function normaliseCandidate(array $candidateCapacity)
    {
        $fields = array('capacity_adults', 'capacity_children', 'capacity_total');
        $normalised = array(
            'capacity_adults' => null,
            'capacity_children' => null,
            'capacity_total' => null,
        );

        foreach ($fields as $field) {
            if (!array_key_exists($field, $candidateCapacity)) {
                continue;
            }

            $value = $candidateCapacity[$field];
            if ($value === '' || $value === null) {
                continue;
            }

            if (!Validate::isUnsignedInt($value)) {
                continue;
            }

            $intValue = (int) $value;
            if ($intValue >= 0) {
                $normalised[$field] = $intValue;
            }
        }

        return $normalised;
    }

    /**
     * Prefix helper kept separate for testability.
     *
     * @param string $table
     *
     * @return string
     */
    private function prefix($table)
    {
        if (defined('_DB_PREFIX_')) {
            return _DB_PREFIX_ . $table;
        }

        return $table;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function escape($value)
    {
        if (function_exists('pSQL')) {
            return pSQL($value);
        }

        return addslashes($value);
    }
}
