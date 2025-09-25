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

class KlOperationRun extends ObjectModel
{
    public $id_kl_operation_run;
    public $run_type;
    public $status;
    public $started_at;
    public $completed_at;
    public $timezone;
    public $metadata;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_operation_run',
        'primary' => 'id_kl_operation_run',
        'fields' => array(
            'run_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
            'started_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true),
            'completed_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'timezone' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
            'metadata' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );
}
