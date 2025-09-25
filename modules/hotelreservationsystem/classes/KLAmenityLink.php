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

class KLAmenityLink extends ObjectModel
{
    /** @var int */
    public $id_kl_resource_profile;

    /** @var int */
    public $id_kl_resource_amenity;

    /** @var string */
    public $note;

    /** @var bool */
    public $is_required;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_resource_amenity_link',
        'primary' => 'id_kl_resource_amenity_link',
        'fields' => array(
            'id_kl_resource_profile' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_kl_resource_amenity' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'note' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'is_required' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Return the amenity identifiers assigned to a resource profile.
     *
     * @param int $idProfile
     *
     * @return array<int, int>
     */
    public static function getAmenityIdsByProfile($idProfile)
    {
        $idProfile = (int) $idProfile;
        if ($idProfile <= 0) {
            return array();
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_kl_resource_amenity`
            FROM `'._DB_PREFIX_.'kl_resource_amenity_link`
            WHERE `id_kl_resource_profile` = '.(int) $idProfile
        );

        if (!is_array($rows) || empty($rows)) {
            return array();
        }

        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int) $row['id_kl_resource_amenity'];
        }

        return $ids;
    }
}
