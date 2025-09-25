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

require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/HotelInquiry.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/HotelInquiryNote.php';

class KLInquirySubmission
{
    /**
     * Persist an inquiry from the front-office form and dispatch notifications.
     *
     * @param array<string, mixed> $values
     * @param Context $context
     *
     * @return array{inquiry: HotelInquiry}
     *
     * @throws PrestaShopException
     */
    public static function createFromFront(array $values, Context $context)
    {
        $inquiry = new HotelInquiry();
        $inquiry->subject = self::buildSubject($values, $context);
        $inquiry->requester_name = $values['guest_name'];
        $inquiry->requester_email = $values['guest_email'];
        $inquiry->requester_phone = $values['guest_phone'];
        $inquiry->check_in = $values['arrival_date'];
        $inquiry->check_out = $values['departure_date'];
        $inquiry->resource_request = json_encode(self::buildResourceRequestPayload($values));
        $inquiry->internal_notes = self::buildInternalNote($values, $context);

        if (!$inquiry->add()) {
            throw new PrestaShopException('Unable to persist inquiry.');
        }

        HotelInquiryNote::addNote(
            (int) $inquiry->id,
            self::buildAuditTrailEntry($values, $context),
            null,
            false
        );

        self::dispatchGuestConfirmation($inquiry, $values, $context);
        self::dispatchStaffNotification($inquiry, $values, $context);

        Hook::exec('actionKunstortInquirySubmitted', array(
            'inquiry' => $inquiry,
            'values' => $values,
        ));

        return array(
            'inquiry' => $inquiry,
        );
    }

    /**
     * @param array<string, mixed> $values
     * @param Context $context
     *
     * @return string
     */
    protected static function buildSubject(array $values, Context $context)
    {
        $module = Module::getInstanceByName('hotelreservationsystem');
        $langId = (int) $context->language->id;

        $name = (string) $values['guest_name'];
        $checkIn = $values['arrival_date'];
        $checkOut = $values['departure_date'];

        $formattedWindow = '';
        if ($checkIn && $checkOut) {
            if (class_exists('IntlDateFormatter')) {
                $dateFormatter = new IntlDateFormatter(
                    $context->language->getLocale(),
                    IntlDateFormatter::MEDIUM,
                    IntlDateFormatter::NONE,
                    $context->employee ? $context->employee->getTimezone() : $context->shop->getTimezone()
                );
                $formattedWindow = $dateFormatter->format(new DateTime($checkIn)).' – '.$dateFormatter->format(new DateTime($checkOut));
            } else {
                $formattedWindow = Tools::displayDate($checkIn, (int) $context->language->id).' – '.Tools::displayDate($checkOut, (int) $context->language->id);
            }
        }

        $resourceSummary = self::summariseResourceInterests($values, $module, $langId);

        $subject = sprintf('%s (%s)', $name, $formattedWindow);
        if ($resourceSummary) {
            $subject .= ' · '.$resourceSummary;
        }

        return Tools::substr($subject, 0, 255);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    protected static function buildResourceRequestPayload(array $values)
    {
        return array(
            'resource_interests' => $values['resource_interests'],
            'resource_notes' => $values['resource_notes'],
            'party_size' => array(
                'adults' => (int) $values['party_size_adults'],
                'children' => (int) $values['party_size_children'],
            ),
            'programme_focus' => $values['programme_focus'],
            'date_flexibility' => (bool) $values['date_flexibility'],
            'package_preferences' => $values['package_preferences'],
            'additional_notes' => $values['additional_notes'],
        );
    }

    /**
     * @param array<string, mixed> $values
     * @param Context $context
     *
     * @return string|null
     */
    protected static function buildInternalNote(array $values, Context $context)
    {
        $lines = array();
        $lines[] = 'Submitted via front-office inquiry form on '.date(DATE_ATOM);
        $lines[] = 'Locale: '.$context->language->iso_code;
        if (!empty($values['consent_newsletter'])) {
            $lines[] = 'Guest opted into programme updates.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $values
     * @param Context $context
     *
     * @return string
     */
    protected static function buildAuditTrailEntry(array $values, Context $context)
    {
        $module = Module::getInstanceByName('hotelreservationsystem');
        $translator = $module;

        $lines = array();
        $lines[] = $translator->l('Inquiry submitted from the front-office form.', 'KLInquirySubmission');
        $lines[] = $translator->l('Summary:', 'KLInquirySubmission');

        $lines[] = '- '.$translator->l('Guest', 'KLInquirySubmission').': '.$values['guest_name'].' <'.$values['guest_email'].'>';
        if (!empty($values['guest_phone'])) {
            $lines[] = '  '.$translator->l('Phone', 'KLInquirySubmission').': '.$values['guest_phone'];
        }

        $lines[] = '- '.$translator->l('Stay window', 'KLInquirySubmission').': '.$values['arrival_date'].' → '.$values['departure_date'];
        $lines[] = '- '.$translator->l('Party size', 'KLInquirySubmission').': '.(int) $values['party_size_adults'].' '.$translator->l('adults', 'KLInquirySubmission').', '.(int) $values['party_size_children'].' '.$translator->l('children', 'KLInquirySubmission');

        if (!empty($values['resource_interests'])) {
            $lines[] = '- '.$translator->l('Resource interests', 'KLInquirySubmission').': '.implode(', ', $values['resource_interests']);
        }

        if (!empty($values['package_preferences'])) {
            $lines[] = '- '.$translator->l('Package preferences', 'KLInquirySubmission').': '.implode(', ', $values['package_preferences']);
        }

        if (!empty($values['programme_focus'])) {
            $lines[] = '- '.$translator->l('Programme focus', 'KLInquirySubmission').': '.$values['programme_focus'];
        }

        if (!empty($values['additional_notes'])) {
            $lines[] = '- '.$translator->l('Additional notes', 'KLInquirySubmission').': '.$values['additional_notes'];
        }

        if (!empty($values['date_flexibility'])) {
            $lines[] = '- '.$translator->l('Dates flexible', 'KLInquirySubmission');
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $values
     * @param Module $module
     * @param int $idLang
     *
     * @return string
     */
    protected static function summariseResourceInterests(array $values, Module $module, $idLang)
    {
        if (empty($values['resource_interests']) || !is_array($values['resource_interests'])) {
            return '';
        }

        $labels = array(
            KLResourceProfile::RESOURCE_KIND_ROOM => $module->l('Residency rooms', 'KLInquirySubmission'),
            KLResourceProfile::RESOURCE_KIND_ATELIER => $module->l('Studios & ateliers', 'KLInquirySubmission'),
            KLResourceProfile::RESOURCE_KIND_SEMINAR => $module->l('Seminar spaces', 'KLInquirySubmission'),
            KLResourceProfile::RESOURCE_KIND_GASTRONOMY => $module->l('Gastronomy', 'KLInquirySubmission'),
        );

        $resolved = array();
        foreach ($values['resource_interests'] as $interest) {
            if (isset($labels[$interest])) {
                $resolved[] = $labels[$interest];
            } else {
                $resolved[] = (string) $interest;
            }
        }

        return implode(', ', array_unique($resolved));
    }

    /**
     * @param HotelInquiry $inquiry
     * @param array<string, mixed> $values
     * @param Context $context
     *
     * @return void
     */
    protected static function dispatchGuestConfirmation(HotelInquiry $inquiry, array $values, Context $context)
    {
        if (empty($values['guest_email']) || !Validate::isEmail($values['guest_email'])) {
            return;
        }

        $templateVars = self::buildMailTemplateVars($inquiry, $values, $context);

        Mail::Send(
            (int) $context->language->id,
            'kl_inquiry_confirmation',
            Mail::l('We received your residency inquiry', (int) $context->language->id),
            $templateVars,
            $values['guest_email'],
            $values['guest_name'],
            null,
            null,
            null,
            null,
            _PS_MAIL_DIR_,
            false,
            (int) $context->shop->id
        );
    }

    /**
     * @param HotelInquiry $inquiry
     * @param array<string, mixed> $values
     * @param Context $context
     *
     * @return void
     */
    protected static function dispatchStaffNotification(HotelInquiry $inquiry, array $values, Context $context)
    {
        $staffEmail = Configuration::get('PS_SHOP_EMAIL');
        if (!$staffEmail || !Validate::isEmail($staffEmail)) {
            return;
        }

        $templateVars = self::buildMailTemplateVars($inquiry, $values, $context);

        Mail::Send(
            (int) $context->language->id,
            'kl_inquiry_staff_alert',
            Mail::l('New residency inquiry received', (int) $context->language->id),
            $templateVars,
            $staffEmail,
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            null,
            null,
            _PS_MAIL_DIR_,
            false,
            (int) $context->shop->id
        );
    }

    /**
     * @param HotelInquiry $inquiry
     * @param array<string, mixed> $values
     * @param Context $context
     *
     * @return array<string, string>
     */
    protected static function buildMailTemplateVars(HotelInquiry $inquiry, array $values, Context $context)
    {
        $resourcePayload = self::buildResourceRequestPayload($values);

        $lines = array();
        if (!empty($resourcePayload['resource_interests'])) {
            $lines[] = implode(', ', $resourcePayload['resource_interests']);
        }
        if (!empty($resourcePayload['programme_focus'])) {
            $lines[] = $resourcePayload['programme_focus'];
        }
        if (!empty($resourcePayload['resource_notes'])) {
            $lines[] = $resourcePayload['resource_notes'];
        }
        if (!empty($resourcePayload['additional_notes'])) {
            $lines[] = $resourcePayload['additional_notes'];
        }

        $summary = implode("\n", array_filter($lines));

        return array(
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{reference}' => $inquiry->reference,
            '{guest_name}' => $values['guest_name'],
            '{guest_email}' => $values['guest_email'],
            '{guest_phone}' => $values['guest_phone'],
            '{check_in}' => $values['arrival_date'],
            '{check_out}' => $values['departure_date'],
            '{party_adults}' => (string) (int) $values['party_size_adults'],
            '{party_children}' => (string) (int) $values['party_size_children'],
            '{summary}' => $summary,
            '{resource_summary}' => self::summariseResourceInterests($values, Module::getInstanceByName('hotelreservationsystem'), (int) $context->language->id),
            '{inquiry_board_url}' => self::buildBoardUrl($inquiry, $context),
        );
    }

    /**
     * @param HotelInquiry $inquiry
     * @param Context $context
     *
     * @return string
     */
    protected static function buildBoardUrl(HotelInquiry $inquiry, Context $context)
    {
        if (!isset($context->link)) {
            return '';
        }

        return $context->link->getAdminLink('AdminHotelInquiries', true, array(), array('id_inquiry' => (int) $inquiry->id));
    }
}
