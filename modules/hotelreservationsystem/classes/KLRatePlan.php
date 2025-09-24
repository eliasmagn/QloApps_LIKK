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

class KLRatePlan extends ObjectModel
{
    /** @var string */
    public $plan_code;

    /** @var string */
    public $pricing_method;

    /** @var string */
    public $currency_iso_code;

    /** @var bool */
    public $is_active;

    /** @var string */
    public $resource_kind_scope;

    /** @var string */
    public $audience_segment_scope;

    /** @var string */
    public $cancellation_policy_notes;

    /** @var int */
    public $advance_required_minor;

    /** @var bool */
    public $approval_required;

    /** @var int */
    public $created_by;

    /** @var int */
    public $updated_by;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    /** @var string */
    public $name;

    /** @var string */
    public $tagline;

    /** @var string */
    public $description;

    public static $definition = array(
        'table' => 'kl_rate_plan',
        'primary' => 'id_kl_rate_plan',
        'multilang' => true,
        'fields' => array(
            'plan_code' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 64),
            'pricing_method' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 32),
            'currency_iso_code' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 3),
            'is_active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'resource_kind_scope' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'audience_segment_scope' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'cancellation_policy_notes' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'advance_required_minor' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'approval_required' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'created_by' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'updated_by' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'lang' => true, 'required' => true, 'size' => 255),
            'tagline' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'lang' => true, 'size' => 255),
            'description' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'lang' => true),
        ),
    );

    public function add($autodate = true, $null_values = false)
    {
        $this->date_add = date('Y-m-d H:i:s');
        $this->date_upd = $this->date_add;

        if ($this->is_active === null) {
            $this->is_active = 0;
        }
        if ($this->approval_required === null) {
            $this->approval_required = 0;
        }

        return parent::add($autodate, $null_values);
    }

    public function update($null_values = false)
    {
        $this->date_upd = date('Y-m-d H:i:s');
        return parent::update($null_values);
    }

    /**
     * @return array<int, string>
     */
    public function getResourceKindScope()
    {
        if (!$this->resource_kind_scope) {
            return array();
        }

        $decoded = json_decode($this->resource_kind_scope, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }

    /**
     * @param array<int, string> $resourceKinds
     *
     * @return void
     */
    public function setResourceKindScope(array $resourceKinds)
    {
        if (empty($resourceKinds)) {
            $this->resource_kind_scope = null;
            return;
        }

        $this->resource_kind_scope = json_encode(array_values($resourceKinds));
    }

    /**
     * @return array<int, string>
     */
    public function getAudienceSegmentScope()
    {
        if (!$this->audience_segment_scope) {
            return array();
        }

        $decoded = json_decode($this->audience_segment_scope, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }

    /**
     * @param array<int, string> $segments
     *
     * @return void
     */
    public function setAudienceSegmentScope(array $segments)
    {
        if (empty($segments)) {
            $this->audience_segment_scope = null;
            return;
        }

        $this->audience_segment_scope = json_encode(array_values($segments));
    }
}
