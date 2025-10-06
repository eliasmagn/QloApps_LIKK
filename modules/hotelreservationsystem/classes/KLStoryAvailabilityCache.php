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

class KLStoryAvailabilityCache
{
    const CACHE_KEY_PREFIX = 'KL_STORY_AVAILABILITY_';

    /**
     * Purge every cached storytelling availability snapshot.
     *
     * @return void
     */
    public static function invalidateAll()
    {
        Cache::clean(self::CACHE_KEY_PREFIX.'*');
    }

    /**
     * Purge cached storytelling availability snapshots for a given shop.
     *
     * @param int $idShop
     *
     * @return void
     */
    public static function invalidateForShop($idShop)
    {
        $idShop = (int) $idShop;
        if ($idShop <= 0) {
            self::invalidateAll();

            return;
        }

        Cache::clean(self::CACHE_KEY_PREFIX.$idShop.'_*');
    }

    /**
     * Purge cached storytelling availability snapshots for a given shop/language pair.
     *
     * @param int $idShop
     * @param int $idLang
     *
     * @return void
     */
    public static function invalidateForShopAndLang($idShop, $idLang)
    {
        $idShop = (int) $idShop;
        $idLang = (int) $idLang;
        if ($idShop <= 0) {
            self::invalidateAll();

            return;
        }
        if ($idLang <= 0) {
            self::invalidateForShop($idShop);

            return;
        }

        Cache::clean(self::CACHE_KEY_PREFIX.$idShop.'_'.$idLang.'*');
    }
}
