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

class HotelReservationSystemStorytellingConfiguration
{
    const CONFIGURATION_KEY = 'WK_STORYTELLING_ENABLED';

    /**
     * Ensures the configuration toggle exists for installs that previously
     * relied on the `_KUNSTORT_STORYTELLING_LAUNCH_` constant.
     *
     * @return void
     */
    public static function bootstrapFromLegacyConstant()
    {
        if (Configuration::hasKey(self::CONFIGURATION_KEY)) {
            return;
        }

        if (!defined('_KUNSTORT_STORYTELLING_LAUNCH_')) {
            return;
        }

        Configuration::updateValue(
            self::CONFIGURATION_KEY,
            (int) _KUNSTORT_STORYTELLING_LAUNCH_
        );
    }

    /**
     * Checks whether storytelling pages should be exposed on the front-office.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        self::bootstrapFromLegacyConstant();

        if (Configuration::hasKey(self::CONFIGURATION_KEY)) {
            return (bool) Configuration::get(self::CONFIGURATION_KEY);
        }

        if (defined('_KUNSTORT_STORYTELLING_LAUNCH_')) {
            return (bool) _KUNSTORT_STORYTELLING_LAUNCH_;
        }

        return false;
    }

    /**
     * Assigns the storytelling toggle to Smarty so that theme templates can
     * interrogate the setting without relying on legacy constants.
     *
     * @param Context|null $context
     *
     * @return void
     */
    public static function assignToSmarty(?Context $context = null)
    {
        $context = $context ?: Context::getContext();
        if (!$context || !isset($context->smarty) || !$context->smarty) {
            return;
        }

        $context->smarty->assign(array(
            'wk_storytelling_enabled' => self::isEnabled(),
        ));
    }
}
