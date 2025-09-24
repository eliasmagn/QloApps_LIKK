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

class KLResourceHistory extends ObjectModel
{
    /** @var int */
    public $id_kl_resource_profile;

    /** @var int */
    public $id_employee;

    /** @var string */
    public $change_source;

    /** @var string */
    public $snapshot;

    /** @var string */
    public $date_add;

    public static $definition = array(
        'table' => 'kl_resource_history',
        'primary' => 'id_kl_resource_history',
        'fields' => array(
            'id_kl_resource_profile' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'change_source' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 32),
            'snapshot' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Log a snapshot for a resource profile.
     *
     * @param int $idProfile
     * @param string $source
     * @param array $payload
     * @param int|null $idEmployee
     *
     * @return bool
     */
    public static function logChange($idProfile, $source, array $payload, $idEmployee = null)
    {
        $history = new self();
        $history->id_kl_resource_profile = (int) $idProfile;
        $history->change_source = (string) $source;
        $history->snapshot = json_encode($payload);
        $history->id_employee = $idEmployee ? (int) $idEmployee : null;

        return (bool) $history->add();
    }
}
