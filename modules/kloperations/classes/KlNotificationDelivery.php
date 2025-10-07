<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/license/osl-3-0-php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@qloapps.com so we can send you a copy immediately.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class KlNotificationDelivery extends ObjectModel
{
    /** @var int */
    public $id_kl_notification_event;

    /** @var int */
    public $id_kl_notification_subscription;

    /** @var int */
    public $id_employee;

    /** @var int */
    public $id_lang;

    /** @var string */
    public $channel;

    /** @var string */
    public $recipient;

    /** @var string */
    public $status;

    /** @var string */
    public $quiet_until;

    /** @var string */
    public $metadata;

    /** @var string */
    public $sent_at;

    /** @var string */
    public $acknowledged_at;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_notification_delivery',
        'primary' => 'id_kl_notification_delivery',
        'fields' => array(
            'id_kl_notification_event' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_kl_notification_subscription' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'channel' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
            'recipient' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
            'quiet_until' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'metadata' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'sent_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'acknowledged_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );
}
