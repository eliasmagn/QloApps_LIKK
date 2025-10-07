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

require_once _PS_TOOL_DIR_.'tcpdf/tcpdf.php';

class QuotePdfGenerator
{
    /** @var Module */
    protected $module;

    public function __construct()
    {
        try {
            $this->module = Module::getInstanceByName('hotelreservationsystem');
        } catch (Exception $exception) {
            $this->module = null;
        }
    }

    /**
     * Generate a branded PDF document for the provided quote.
     *
     * @param KLQuote $quote
     * @param array<string, mixed> $options
     *
     * @return string binary PDF contents
     */
    public function generate(KLQuote $quote, array $options = array())
    {
        $payload = $quote->getPayload();
        $inquiry = $this->normaliseInquiry(isset($options['inquiry']) ? $options['inquiry'] : array());
        $brand = $this->resolveBrand(isset($options['brand']) ? $options['brand'] : array());

        $timestamp = $this->resolveDocumentTimestamp($quote, $options);

        $documentTitle = $this->l('Residency quote');
        if (!empty($inquiry['reference'])) {
            $documentTitle .= ' – '.$inquiry['reference'];
        }

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Kunstort Lehnin');
        $pdf->SetAuthor($brand['name']);
        $pdf->SetTitle($documentTitle);
        $pdf->SetSubject($this->l('Residency pricing breakdown'));
        $pdf->setDocCreationTimestamp($timestamp);
        $pdf->setDocModificationTimestamp($timestamp);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 18, 15, true);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 10, '', true);

        $html = $this->buildDocumentHtml($quote, $payload, $inquiry, $brand);
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('', 'S');
    }

    /**
     * Build a deterministic filename for downloads or attachments.
     *
     * @param KLQuote $quote
     * @param array<string, mixed> $options
     *
     * @return string
     */
    public function buildFilename(KLQuote $quote, array $options = array())
    {
        $inquiry = $this->normaliseInquiry(isset($options['inquiry']) ? $options['inquiry'] : array());
        $reference = isset($inquiry['reference']) && $inquiry['reference'] !== '' ? $inquiry['reference'] : 'inquiry';
        $referenceSlug = Tools::strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $reference));
        $referenceSlug = trim(preg_replace('/-+/', '-', $referenceSlug), '-');
        if ($referenceSlug === '') {
            $referenceSlug = 'INQUIRY';
        }

        $quoteDate = $quote->date_add ? $this->formatDateForFilename($quote->date_add) : date('Ymd');
        $identifier = $quote->id ? (int) $quote->id : (int) $quote->id_kl_quote;
        if ($identifier <= 0) {
            $identifier = time();
        }

        return sprintf('%s-QUOTE-%s-%d.pdf', $referenceSlug, $quoteDate, $identifier);
    }

    /**
     * @param string $date
     *
     * @return string
     */
    protected function formatDateForFilename($date)
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return date('Ymd');
        }

        return date('Ymd', $timestamp);
    }

    /**
     * @param KLQuote $quote
     * @param array<string, mixed> $payload
     * @param array<string, string> $inquiry
     * @param array<string, mixed> $brand
     *
     * @return string
     */
    protected function buildDocumentHtml(KLQuote $quote, array $payload, array $inquiry, array $brand)
    {
        $statusLabels = $this->getQuoteStatusLabels();
        $status = isset($statusLabels[$quote->status]) ? $statusLabels[$quote->status] : $quote->status;

        $summaryRows = array();
        $summaryRows[] = array(
            $this->l('Quote number'),
            Tools::safeOutput(isset($inquiry['reference']) && $inquiry['reference'] !== '' ? $inquiry['reference'] : '#'.$quote->id_kl_quote)
        );
        $summaryRows[] = array(
            $this->l('Status'),
            Tools::safeOutput($status)
        );
        if (!empty($quote->valid_until)) {
            $summaryRows[] = array(
                $this->l('Valid until'),
                Tools::displayDate($quote->valid_until, null, true)
            );
        }
        if (!empty($payload['plan']['name'])) {
            $summaryRows[] = array(
                $this->l('Rate plan'),
                Tools::safeOutput($payload['plan']['name'])
            );
        }
        if (!empty($payload['package']['name'])) {
            $summaryRows[] = array(
                $this->l('Package'),
                Tools::safeOutput($payload['package']['name'])
            );
        }
        if (!empty($payload['metadata']['stay_label'])) {
            $summaryRows[] = array(
                $this->l('Stay'),
                Tools::safeOutput($payload['metadata']['stay_label'])
            );
        } elseif (!empty($payload['metadata']['nights'])) {
            $summaryRows[] = array(
                $this->module->l('Stay length', 'QuotePdfGenerator'),
                sprintf($this->l('%d nights'), (int) $payload['metadata']['nights'])
            );
        } elseif (!empty($payload['nights'])) {
            $summaryRows[] = array(
                $this->module->l('Stay length', 'QuotePdfGenerator'),
                sprintf($this->l('%d nights'), (int) $payload['nights'])
            );
        }

        $guestDetails = $this->buildGuestDetails($inquiry);
        $lineItemsHtml = $this->buildLineItemsTable($payload);
        $warningsHtml = $this->buildWarningsHtml($payload);

        $brandAddress = '';
        if (!empty($brand['address_lines'])) {
            $brandAddress = implode('<br>', array_map('Tools::safeOutput', $brand['address_lines']));
        }

        $contactLines = array();
        if (!empty($brand['contact_email'])) {
            $contactLines[] = Tools::safeOutput($brand['contact_email']);
        }
        if (!empty($brand['contact_phone'])) {
            $contactLines[] = Tools::safeOutput($brand['contact_phone']);
        }
        if (!empty($brand['website'])) {
            $contactLines[] = Tools::safeOutput($brand['website']);
        }

        $summaryTableHtml = $this->renderKeyValueTable($summaryRows);

        $html = '<style>'
            .'h1{font-size:18px;margin-bottom:8px;}'
            .'h2{font-size:14px;margin-top:14px;margin-bottom:6px;}'
            .'table.meta{width:100%;border-spacing:0 3px;}'
            .'table.meta td.label{width:35%;font-weight:bold;color:#333;}'
            .'table.meta td.value{width:65%;color:#333;}'
            .'table.pricing{width:100%;border-collapse:collapse;margin-top:6px;}'
            .'table.pricing th{background-color:#f4f4f4;border:1px solid #ddd;padding:6px;font-weight:bold;}'
            .'table.pricing td{border:1px solid #ddd;padding:6px;}'
            .'.totals{margin-top:8px;}'
            .'.totals td{padding:4px 0;}'
            .'.quote-section{margin-bottom:14px;}'
            .'.quote-brand{text-align:right;}'
            .'.quote-brand strong{display:block;font-size:14px;}'
            .'.quote-brand span{display:block;font-size:10px;color:#666;}'
            .'.guest-block{margin-top:6px;}'
            .'.guest-block p{margin:0 0 4px 0;}'
            .'.warning-list{margin:6px 0 0 12px;padding:0;}'
            .'.warning-list li{margin-bottom:3px;}'
            .'</style>';

        $html .= '<table width="100%"><tr>'
            .'<td><h1>'.Tools::safeOutput($documentTitle = $this->l('Residency quote')).'</h1>'
            .'<div class="guest-block">'.$guestDetails.'</div>'
            .'</td>'
            .'<td class="quote-brand">'
            .'<strong>'.Tools::safeOutput($brand['name']).'</strong>';
        if ($brandAddress !== '') {
            $html .= '<span>'.$brandAddress.'</span>';
        }
        if (!empty($contactLines)) {
            $html .= '<span>'.implode(' · ', $contactLines).'</span>';
        }
        $html .= '</td></tr></table>';

        $html .= '<div class="quote-section">'
            .'<h2>'.$this->l('Quote summary').'</h2>'
            .$summaryTableHtml
            .'</div>';

        $html .= '<div class="quote-section">'
            .'<h2>'.$this->l('Pricing breakdown').'</h2>'
            .$lineItemsHtml
            .'</div>';

        if ($warningsHtml !== '') {
            $html .= '<div class="quote-section">'
                .'<h2>'.$this->l('Important notes').'</h2>'
                .$warningsHtml
                .'</div>';
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $inquiry
     *
     * @return string
     */
    protected function buildGuestDetails(array $inquiry)
    {
        $lines = array();
        if (!empty($inquiry['subject'])) {
            $lines[] = '<strong>'.Tools::safeOutput($inquiry['subject']).'</strong>';
        }
        $contactParts = array();
        if (!empty($inquiry['requester_name'])) {
            $contactParts[] = Tools::safeOutput($inquiry['requester_name']);
        }
        if (!empty($inquiry['requester_email'])) {
            $contactParts[] = Tools::safeOutput('<'.$inquiry['requester_email'].'>');
        }
        if (!empty($inquiry['requester_phone'])) {
            $contactParts[] = Tools::safeOutput($inquiry['requester_phone']);
        }
        if (!empty($contactParts)) {
            $lines[] = '<span>'.implode(' · ', $contactParts).'</span>';
        }
        if (!empty($inquiry['check_in']) || !empty($inquiry['check_out'])) {
            $checkIn = !empty($inquiry['check_in']) ? Tools::displayDate($inquiry['check_in'], null, false) : '?';
            $checkOut = !empty($inquiry['check_out']) ? Tools::displayDate($inquiry['check_out'], null, false) : '?';
            $lines[] = '<span>'.$this->l('Stay window:').' '.$checkIn.' → '.$checkOut.'</span>';
        }
        if (!empty($inquiry['resource_request'])) {
            $lines[] = '<span>'.Tools::safeOutput($this->stripTagsAndNormalise($inquiry['resource_request'])).'</span>';
        }

        if (empty($lines)) {
            return '';
        }

        return '<p>'.implode('<br>', $lines).'</p>';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string
     */
    protected function buildLineItemsTable(array $payload)
    {
        $lineItems = isset($payload['line_items']) && is_array($payload['line_items']) ? $payload['line_items'] : array();
        $currency = isset($payload['currency_iso_code']) ? (string) $payload['currency_iso_code'] : 'EUR';

        $rows = '';
        foreach ($lineItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $rows .= '<tr>'
                .'<td>'.Tools::safeOutput(isset($item['label']) ? (string) $item['label'] : '').'</td>'
                .'<td>'.Tools::safeOutput($this->formatQuantity(isset($item['quantity']) ? $item['quantity'] : 0)).'</td>'
                .'<td class="text-right">'.$this->formatMoney(isset($item['unit_gross_minor']) ? (int) $item['unit_gross_minor'] : 0, $currency).'</td>'
                .'<td class="text-right">'.$this->formatMoney(isset($item['total_gross_minor']) ? (int) $item['total_gross_minor'] : 0, $currency).'</td>'
                .'</tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4">'.$this->l('No pricing lines available.').'</td></tr>';
        }

        $totals = '<table class="totals" width="100%">'
            .'<tr><td>'.$this->l('Net total').'</td><td class="text-right">'.$this->formatMoney(isset($payload['net_total_minor']) ? (int) $payload['net_total_minor'] : 0, $currency).'</td></tr>'
            .'<tr><td>'.$this->l('Tax total').'</td><td class="text-right">'.$this->formatMoney(isset($payload['tax_total_minor']) ? (int) $payload['tax_total_minor'] : 0, $currency).'</td></tr>'
            .'<tr><td><strong>'.$this->l('Grand total').'</strong></td><td class="text-right"><strong>'.$this->formatMoney(isset($payload['gross_total_minor']) ? (int) $payload['gross_total_minor'] : 0, $currency).'</strong></td></tr>'
            .'</table>';

        return '<table class="pricing">'
            .'<thead><tr>'
            .'<th>'.$this->l('Description').'</th>'
            .'<th>'.$this->l('Qty').'</th>'
            .'<th>'.$this->l('Unit (gross)').'</th>'
            .'<th>'.$this->l('Line total').'</th>'
            .'</tr></thead><tbody>'
            .$rows
            .'</tbody></table>'
            .$totals;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return string
     */
    protected function buildWarningsHtml(array $payload)
    {
        if (empty($payload['warnings']) || !is_array($payload['warnings'])) {
            return '';
        }

        $items = array();
        foreach ($payload['warnings'] as $warning) {
            if (!is_string($warning) || trim($warning) === '') {
                continue;
            }
            $items[] = '<li>'.Tools::safeOutput($warning).'</li>';
        }

        if (empty($items)) {
            return '';
        }

        return '<ul class="warning-list">'.implode('', $items).'</ul>';
    }

    /**
     * @param array<int, array<int, string>> $rows
     *
     * @return string
     */
    protected function renderKeyValueTable(array $rows)
    {
        if (empty($rows)) {
            return '';
        }

        $html = '<table class="meta">';
        foreach ($rows as $row) {
            if (!is_array($row) || count($row) < 2) {
                continue;
            }
            $html .= '<tr><td class="label">'.Tools::safeOutput((string) $row[0]).'</td><td class="value">'.(string) $row[1].'</td></tr>';
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * @param array<string, mixed>|HotelInquiry $inquiry
     *
     * @return array<string, string>
     */
    protected function normaliseInquiry($inquiry)
    {
        if ($inquiry instanceof HotelInquiry) {
            return array(
                'reference' => (string) $inquiry->reference,
                'subject' => (string) $inquiry->subject,
                'requester_name' => (string) $inquiry->requester_name,
                'requester_email' => (string) $inquiry->requester_email,
                'requester_phone' => (string) $inquiry->requester_phone,
                'check_in' => (string) $inquiry->check_in,
                'check_out' => (string) $inquiry->check_out,
                'resource_request' => (string) $inquiry->resource_request,
            );
        }

        if (!is_array($inquiry)) {
            return array();
        }

        $allowed = array('reference', 'subject', 'requester_name', 'requester_email', 'requester_phone', 'check_in', 'check_out', 'resource_request');
        $sanitised = array();
        foreach ($allowed as $key) {
            if (isset($inquiry[$key]) && $inquiry[$key] !== '') {
                $sanitised[$key] = (string) $inquiry[$key];
            }
        }

        return $sanitised;
    }

    /**
     * @param array<string, mixed> $brandOverrides
     *
     * @return array<string, mixed>
     */
    protected function resolveBrand(array $brandOverrides)
    {
        $brand = array(
            'name' => isset($brandOverrides['name']) && $brandOverrides['name'] !== ''
                ? (string) $brandOverrides['name']
                : (Configuration::get('PS_SHOP_NAME') ?: 'Kunstort Lehnin'),
            'address_lines' => array(),
            'contact_email' => isset($brandOverrides['contact_email']) && $brandOverrides['contact_email'] !== ''
                ? (string) $brandOverrides['contact_email']
                : (string) Configuration::get('PS_SHOP_EMAIL'),
            'contact_phone' => isset($brandOverrides['contact_phone']) && $brandOverrides['contact_phone'] !== ''
                ? (string) $brandOverrides['contact_phone']
                : (string) Configuration::get('PS_SHOP_PHONE'),
            'website' => isset($brandOverrides['website']) && $brandOverrides['website'] !== ''
                ? (string) $brandOverrides['website']
                : Tools::getShopDomainSsl(true, true),
        );

        if (isset($brandOverrides['address_lines']) && is_array($brandOverrides['address_lines'])) {
            foreach ($brandOverrides['address_lines'] as $line) {
                if ($line !== '') {
                    $brand['address_lines'][] = (string) $line;
                }
            }
        } else {
            $brand['address_lines'] = array_filter(array(
                Configuration::get('PS_SHOP_ADDR1'),
                Configuration::get('PS_SHOP_ADDR2'),
                trim(Configuration::get('PS_SHOP_CODE').' '.Configuration::get('PS_SHOP_CITY')),
                Configuration::get('PS_SHOP_COUNTRY_NAME'),
            ));
        }

        return $brand;
    }

    /**
     * @param mixed $value
     * @param string $currency
     *
     * @return string
     */
    protected function formatMoney($value, $currency)
    {
        $amount = ((int) $value) / 100;
        return Tools::safeOutput($currency.' '.number_format($amount, 2, '.', ','));
    }

    /**
     * @return array<string, string>
     */
    protected function getQuoteStatusLabels()
    {
        try {
            return KLQuote::getStatusLabels();
        } catch (Exception $exception) {
            return array();
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function l($string)
    {
        if ($this->module && method_exists($this->module, 'l')) {
            return $this->module->l($string, 'QuotePdfGenerator');
        }

        return $string;
    }

    /**
     * @param mixed $quantity
     *
     * @return string
     */
    protected function formatQuantity($quantity)
    {
        if (is_float($quantity) || (is_string($quantity) && strpos($quantity, '.') !== false)) {
            return Tools::safeOutput((string) round((float) $quantity, 2));
        }

        return Tools::safeOutput((string) (int) $quantity);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function stripTagsAndNormalise($value)
    {
        $stripped = trim(strip_tags($value));
        $stripped = preg_replace('/\s+/', ' ', $stripped);

        return (string) $stripped;
    }

    /**
     * @param KLQuote $quote
     * @param array<string, mixed> $options
     *
     * @return int
     */
    protected function resolveDocumentTimestamp(KLQuote $quote, array $options)
    {
        if (!empty($options['document_timestamp'])) {
            $ts = strtotime((string) $options['document_timestamp']);
            if ($ts !== false) {
                return $ts;
            }
        }
        if (!empty($quote->date_upd)) {
            $ts = strtotime($quote->date_upd);
            if ($ts !== false) {
                return $ts;
            }
        }
        if (!empty($quote->date_add)) {
            $ts = strtotime($quote->date_add);
            if ($ts !== false) {
                return $ts;
            }
        }

        return time();
    }
}
