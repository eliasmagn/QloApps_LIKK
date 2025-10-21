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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/HotelReservationSystemStorytellingConfiguration.php';

function upgrade_module_1_9_1(HotelReservationSystem $module)
{
    if (!Configuration::hasKey(HotelReservationSystemStorytellingConfiguration::CONFIGURATION_KEY)) {
        $legacyFlag = defined('_KUNSTORT_STORYTELLING_LAUNCH_') && _KUNSTORT_STORYTELLING_LAUNCH_;
        Configuration::updateValue(HotelReservationSystemStorytellingConfiguration::CONFIGURATION_KEY, (int) $legacyFlag);
    }

    HotelReservationSystemStorytellingConfiguration::assignToSmarty();

    return true;
}
