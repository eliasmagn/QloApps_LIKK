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

require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/KLQuote.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/QuotePdfGenerator.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/HotelInquiry.php';

class HotelReservationSystemQuoteShareModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $idQuote = (int) Tools::getValue('id_quote');
        $token = (string) Tools::getValue('token');

        if ($idQuote <= 0 || $token === '') {
            $this->renderError(400, $this->module->l('Invalid quote request.', 'QuoteShareModuleFrontController'));
        }

        $quote = new KLQuote($idQuote);
        if (!Validate::isLoadedObject($quote) || !KLQuote::verifyShareToken($quote, $token)) {
            $this->renderError(404, $this->module->l('Quote not available.', 'QuoteShareModuleFrontController'));
        }

        $inquiry = HotelInquiry::findById((int) $quote->id_inquiry);
        if (!$inquiry) {
            $this->renderError(404, $this->module->l('Quote not available.', 'QuoteShareModuleFrontController'));
        }

        $generator = new QuotePdfGenerator();
        $pdf = $generator->generate($quote, array('inquiry' => $inquiry));
        $filename = $generator->buildFilename($quote, array('inquiry' => $inquiry));

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.addslashes($filename).'"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdf;
        exit;
    }

    /**
     * @param int $status
     * @param string $message
     *
     * @return void
     */
    protected function renderError($status, $message)
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo $message;
        exit;
    }
}
