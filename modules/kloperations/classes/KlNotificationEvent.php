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

class KlNotificationEvent extends ObjectModel
{
    /** @var string */
    public $event_type;

    /** @var string */
    public $subject;

    /** @var string */
    public $payload;

    /** @var string */
    public $context_type;

    /** @var int */
    public $context_id;

    /** @var string */
    public $scheduled_for;

    /** @var string */
    public $dispatched_at;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_notification_event',
        'primary' => 'id_kl_notification_event',
        'fields' => array(
            'event_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
            'subject' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'payload' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'context_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64),
            'context_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'scheduled_for' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'dispatched_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );
}
