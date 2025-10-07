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
 */

class KLQuoteMailDispatcher
{
    /** @var Context */
    protected $context;

    /** @var Module|null */
    protected $module;

    public function __construct(?Context $context = null)
    {
        $this->context = $context ?: Context::getContext();

        try {
            $this->module = Module::getInstanceByName('hotelreservationsystem');
        } catch (Exception $exception) {
            $this->module = null;
        }
    }

    /**
     * @param KLQuote $quote
     * @param array<string, mixed> $inquiry
     * @param array<string, mixed> $options
     *
     * @return bool
     */
    public function sendQuoteToGuest(KLQuote $quote, array $inquiry, array $options = array())
    {
        $email = isset($inquiry['requester_email']) ? trim((string) $inquiry['requester_email']) : '';
        if ($email === '' || !Validate::isEmail($email)) {
            throw new PrestaShopException($this->trans('The inquiry is missing a valid guest email address.'));
        }

        $context = $this->context ?: Context::getContext();
        $langId = isset($options['id_lang']) && (int) $options['id_lang'] > 0
            ? (int) $options['id_lang']
            : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));

        $generator = new QuotePdfGenerator();
        $pdf = $generator->generate($quote, array('inquiry' => $inquiry));
        $filename = $generator->buildFilename($quote, array('inquiry' => $inquiry));

        $templateVars = $this->buildTemplateVars($quote, $inquiry, $langId);
        $fromAddress = $this->resolveFromAddress();
        $fromName = $this->resolveFromName();
        $replyTo = $this->resolveReplyToAddress($fromAddress);
        $guestName = $this->resolveGuestName($inquiry, $langId);
        $subject = $this->buildSubject($quote, $inquiry, $langId);

        $attachments = array(
            array(
                'content' => $pdf,
                'name' => $filename,
                'mime' => 'application/pdf',
            ),
        );

        $shopId = $context && $context->shop ? (int) $context->shop->id : null;

        return Mail::Send(
            $langId,
            'kl_quote_notification',
            $subject,
            $templateVars,
            $email,
            $guestName !== '' ? $guestName : null,
            $fromAddress,
            $fromName,
            $attachments,
            null,
            _PS_MODULE_DIR_.'hotelreservationsystem/mails/',
            false,
            $shopId,
            null,
            $replyTo ?: null
        );
    }

    /**
     * @param KLQuote $quote
     * @param array<string, mixed> $inquiry
     * @param int $langId
     *
     * @return array<string, string>
     */
    protected function buildTemplateVars(KLQuote $quote, array $inquiry, $langId)
    {
        $statusLabels = KLQuote::getStatusLabels();
        $status = isset($statusLabels[$quote->status]) ? $statusLabels[$quote->status] : $quote->status;

        $reference = isset($inquiry['reference']) && $inquiry['reference'] !== ''
            ? (string) $inquiry['reference']
            : '#' . (int) ($quote->id ? $quote->id : $quote->id_kl_quote);

        $subject = isset($inquiry['subject']) ? trim((string) $inquiry['subject']) : '';

        $brandName = trim((string) Configuration::get('PS_SHOP_NAME'));
        if ($brandName === '') {
            $brandName = 'Kunstort Lehnin';
        }

        $contactEmail = trim((string) Configuration::get('PS_SHOP_EMAIL'));
        if ($contactEmail === '') {
            $contactEmail = $this->resolveFromAddress();
        }
        $contactPhone = trim((string) Configuration::get('PS_SHOP_PHONE'));
        $contactPhoneFormatted = $contactPhone !== '' ? ' · '.$contactPhone : '';

        $totalDisplay = $this->formatMoney($quote->gross_total_minor, $quote->currency_iso_code);
        $validUntil = '';
        if ($quote->valid_until) {
            $validUntil = Tools::displayDate($quote->valid_until, $langId, true);
        }
        if ($validUntil === '') {
            $validUntil = $this->trans('Until further notice');
        }

        $downloadLink = $this->buildDownloadLink($quote);

        return array(
            '{guest_name}' => $this->resolveGuestName($inquiry, $langId),
            '{inquiry_reference}' => $reference,
            '{inquiry_subject}' => $subject,
            '{quote_status}' => $status,
            '{quote_total}' => $totalDisplay,
            '{quote_valid_until}' => $validUntil,
            '{quote_download_link}' => $downloadLink,
            '{contact_email}' => $contactEmail,
            '{contact_phone_formatted}' => $contactPhoneFormatted,
            '{brand_name}' => $brandName,
        );
    }

    /**
     * @param KLQuote $quote
     * @param array<string, mixed> $inquiry
     * @param int $langId
     *
     * @return string
     */
    protected function buildSubject(KLQuote $quote, array $inquiry, $langId)
    {
        $reference = isset($inquiry['reference']) && $inquiry['reference'] !== ''
            ? (string) $inquiry['reference']
            : '#' . (int) ($quote->id ? $quote->id : $quote->id_kl_quote);

        $pattern = $this->trans('Residency quote %s');

        return sprintf($pattern, $reference);
    }

    /**
     * @param array<string, mixed> $inquiry
     * @param int $langId
     *
     * @return string
     */
    protected function resolveGuestName(array $inquiry, $langId)
    {
        if (!empty($inquiry['requester_name'])) {
            return trim((string) $inquiry['requester_name']);
        }

        return $this->trans('there');
    }

    /**
     * @return string
     */
    protected function resolveFromName()
    {
        $brandName = trim((string) Configuration::get('PS_SHOP_NAME'));
        if ($brandName !== '') {
            return $brandName;
        }

        return 'Kunstort Lehnin';
    }

    /**
     * @return string
     */
    protected function resolveFromAddress()
    {
        $configured = trim((string) Configuration::get('KL_QUOTE_MAIL_FROM_ADDRESS'));
        if ($configured !== '' && Validate::isEmail($configured)) {
            return $configured;
        }

        $shopEmail = trim((string) Configuration::get('PS_SHOP_EMAIL'));
        if ($shopEmail !== '' && Validate::isEmail($shopEmail)) {
            return $shopEmail;
        }

        $context = $this->context ?: Context::getContext();
        if ($context && $context->shop && $context->shop->email && Validate::isEmail($context->shop->email)) {
            return $context->shop->email;
        }

        return $shopEmail;
    }

    /**
     * @param string $fromAddress
     *
     * @return string|null
     */
    protected function resolveReplyToAddress($fromAddress)
    {
        $configured = trim((string) Configuration::get('KL_QUOTE_MAIL_REPLY_TO_ADDRESS'));
        if ($configured !== '' && Validate::isEmail($configured)) {
            return $configured;
        }

        if ($fromAddress && Validate::isEmail($fromAddress)) {
            return $fromAddress;
        }

        return null;
    }

    /**
     * @param KLQuote $quote
     *
     * @return string
     */
    protected function buildDownloadLink(KLQuote $quote)
    {
        $token = $quote->getShareToken();
        if (!$token) {
            return '';
        }

        $context = $this->context ?: Context::getContext();
        $link = $context && $context->link ? $context->link : new Link();
        $id = (int) ($quote->id ? $quote->id : $quote->id_kl_quote);

        return $link->getModuleLink(
            'hotelreservationsystem',
            'quoteshare',
            array(
                'id_quote' => $id,
                'token' => $token,
            ),
            true
        );
    }

    /**
     * @param int $amountMinor
     * @param string $currencyIso
     *
     * @return string
     */
    protected function formatMoney($amountMinor, $currencyIso)
    {
        $value = ((int) $amountMinor) / 100;

        return $currencyIso.' '.number_format($value, 2, '.', ',');
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function trans($string)
    {
        if ($this->module) {
            return $this->module->l($string, 'KLQuoteMailDispatcher');
        }

        return $string;
    }
}
