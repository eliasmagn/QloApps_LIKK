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

class KLResourceStory extends ObjectModel
{
    /** @var int */
    public $id_kl_resource_profile;

    /** @var int */
    public $id_lang;

    /** @var string */
    public $headline;

    /** @var string */
    public $excerpt;

    /** @var string */
    public $body;

    /** @var string */
    public $image_reference;

    /** @var string */
    public $alt_text;

    /** @var int */
    public $updated_by;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_resource_story',
        'primary' => 'id_kl_resource_story',
        'fields' => array(
            'id_kl_resource_profile' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'headline' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'excerpt' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'body' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'image_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255),
            'alt_text' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'updated_by' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Fetches the localized story record for a profile, falling back to language id 0.
     *
     * @param int $idProfile
     * @param int $idLang
     *
     * @return array|false
     */
    public static function getByProfileAndLang($idProfile, $idLang)
    {
        $idProfile = (int) $idProfile;
        $idLang = (int) $idLang;

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('kl_resource_story');
        $sql->where('id_kl_resource_profile = '.(int) $idProfile);
        $sql->where('id_lang IN ('.(int) $idLang.', 0)');
        $sql->orderBy('id_lang = '.(int) $idLang.' DESC');
        $sql->limit(1);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
    }
}
