<?php

use PHPUnit\Framework\TestCase;

require_once dirname(dirname(__DIR__)).'/modules/hotelreservationsystem/classes/KLQuote.php';
require_once dirname(dirname(__DIR__)).'/modules/hotelreservationsystem/classes/QuotePdfGenerator.php';

Context::setInstanceForTesting(new Context());
$context = Context::getContext();
$language = new stdClass();
$language->date_format_lite = 'Y-m-d';
$language->date_format_full = 'Y-m-d H:i:s';
$context->language = $language;

class QuotePdfGeneratorKlQuoteStub extends KLQuote
{
    public function __construct()
    {
        // Bypass ObjectModel constructor to avoid database interactions in unit tests.
    }
}

class QuotePdfGeneratorTestDouble extends QuotePdfGenerator
{
    public function __construct()
    {
        $this->module = new class {
            public function l($string)
            {
                return $string;
            }
        };
    }

    protected function getQuoteStatusLabels()
    {
        return array(
            KLQuote::STATUS_DRAFT => 'Draft',
            KLQuote::STATUS_SENT => 'Sent to guest',
            KLQuote::STATUS_APPROVED => 'Approved',
            KLQuote::STATUS_DECLINED => 'Declined',
        );
    }
}

class QuotePdfGeneratorTest extends TestCase
{
    public function testGeneratesDeterministicPdfForSampleQuote()
    {
        $quote = new QuotePdfGeneratorKlQuoteStub();
        $quote->id = 42;
        $quote->id_kl_quote = 42;
        $quote->id_inquiry = 7;
        $quote->status = KLQuote::STATUS_SENT;
        $quote->currency_iso_code = 'EUR';
        $quote->net_total_minor = 54000;
        $quote->tax_total_minor = 10260;
        $quote->gross_total_minor = 64260;
        $quote->valid_until = '2024-05-31';
        $quote->date_add = '2024-04-16 10:45:00';
        $quote->date_upd = '2024-04-16 10:45:00';
        $quote->setPayload(array(
            'currency_iso_code' => 'EUR',
            'pricing_method' => 'nightly',
            'nights' => 5,
            'line_items' => array(
                array(
                    'label' => 'Base stay',
                    'quantity' => 5,
                    'unit_gross_minor' => 9800,
                    'total_gross_minor' => 49000,
                ),
                array(
                    'label' => 'Seasonal uplift',
                    'quantity' => 5,
                    'unit_gross_minor' => 800,
                    'total_gross_minor' => 4000,
                ),
                array(
                    'label' => 'Catering',
                    'quantity' => 1,
                    'unit_gross_minor' => 11260,
                    'total_gross_minor' => 11260,
                ),
            ),
            'net_total_minor' => 54000,
            'tax_total_minor' => 10260,
            'gross_total_minor' => 64260,
            'metadata' => array(
                'stay_label' => '20 April 2024 → 25 April 2024',
                'nights' => 5,
            ),
            'warnings' => array(
                'Pricing subject to confirmation of atelier availability.',
            ),
            'plan' => array(
                'name' => 'Resident Artist Week',
            ),
            'package' => array(
                'name' => 'Gastronomy add-on',
            ),
        ));

        $generator = new QuotePdfGeneratorTestDouble();
        $pdf = $generator->generate($quote, array(
            'inquiry' => array(
                'reference' => 'INQ-240401-000123',
                'subject' => 'Artist residency stay',
                'requester_name' => 'Ada Lovelace',
                'requester_email' => 'ada@example.test',
                'requester_phone' => '+49 331 1234567',
                'check_in' => '2024-04-20',
                'check_out' => '2024-04-25',
                'resource_request' => '1 atelier, shared kitchen access',
            ),
            'brand' => array(
                'name' => 'Kunstort Lehnin Residency',
                'address_lines' => array('Klosterkirchplatz 6', '14797 Kloster Lehnin, Germany'),
                'contact_email' => 'hello@kunstort.test',
                'contact_phone' => '+49 331 7654321',
                'website' => 'https://kunstort.example',
            ),
            'document_timestamp' => '2024-04-16T10:45:00+00:00',
        ));

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame('c998fab7e136c7db2e2183170c9f30ea', md5($this->normalisePdfBinary($pdf)));
    }

    /**
     * Strip non-deterministic metadata (document IDs) before hashing.
     *
     * @param string $pdfBinary
     *
     * @return string
     */
    private function normalisePdfBinary($pdfBinary)
    {
        $replacements = array(
            '~<xmpMM:DocumentID>uuid:[^<]+</xmpMM:DocumentID>~i' => '<xmpMM:DocumentID>uuid:00000000-0000-0000-0000-000000000000</xmpMM:DocumentID>',
            '~<xmpMM:InstanceID>uuid:[^<]+</xmpMM:InstanceID>~i' => '<xmpMM:InstanceID>uuid:00000000-0000-0000-0000-000000000000</xmpMM:InstanceID>',
            '~\/ID \[ <[A-F0-9]+> <[A-F0-9]+> \]~i' => '/ID [ <00000000000000000000000000000000> <00000000000000000000000000000000> ]',
        );

        return (string) preg_replace(array_keys($replacements), array_values($replacements), $pdfBinary);
    }
}
