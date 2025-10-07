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

class KlNotificationSubscription extends ObjectModel
{
    /** @var int */
    public $id_employee;

    /** @var string */
    public $event_type;

    /** @var bool */
    public $channel_email;

    /** @var bool */
    public $channel_digest;

    /** @var bool */
    public $channel_calendar;

    /** @var string */
    public $quiet_hours_start;

    /** @var string */
    public $quiet_hours_end;

    /** @var string */
    public $timezone;

    /** @var string */
    public $metadata;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_notification_subscription',
        'primary' => 'id_kl_notification_subscription',
        'fields' => array(
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'event_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
            'channel_email' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'channel_digest' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'channel_calendar' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'quiet_hours_start' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 5),
            'quiet_hours_end' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 5),
            'timezone' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64),
            'metadata' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );
}
