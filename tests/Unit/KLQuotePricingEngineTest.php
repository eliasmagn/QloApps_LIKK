<?php

use PHPUnit\Framework\TestCase;

require_once dirname(dirname(__DIR__)).'/modules/hotelreservationsystem/classes/KLQuotePricingEngine.php';

class KLQuotePricingEngineTest extends TestCase
{
    public function testBuildQuoteAggregatesBaseSeasonsAndComponents()
    {
        $payload = array(
            'currency_iso_code' => 'EUR',
            'pricing_method' => 'nightly',
            'nights' => 3,
            'base_line' => array(
                'code' => 'base',
                'label' => 'Nightly rate',
                'quantity' => 3,
                'unit_net_minor' => 10000,
                'unit_gross_minor' => 11900,
            ),
            'base_nightly_net_minor' => 10000,
            'base_nightly_gross_minor' => 11900,
            'season_definitions' => array(
                array(
                    'code' => 'season-fixed',
                    'label' => 'Fixed uplift',
                    'method' => 'fixed',
                    'adjustment_amount_minor' => 2000,
                    'dates' => array('2024-01-01', '2024-01-02', '2024-01-03'),
                ),
                array(
                    'code' => 'season-percent',
                    'label' => 'Percent uplift',
                    'method' => 'percent',
                    'adjustment_percent_basis_points' => 500,
                    'dates' => array('2024-01-01', '2024-01-02'),
                ),
            ),
            'package_components' => array(
                array(
                    'code' => 'meal-plan',
                    'label' => 'Catering',
                    'quantity' => 1,
                    'unit_net_minor' => 15000,
                    'unit_gross_minor' => 17850,
                    'is_optional' => false,
                ),
            ),
        );

        $quote = KLQuotePricingEngine::buildQuote($payload);

        $this->assertEquals(52000, $quote['net_total_minor']);
        $this->assertEquals(61880, $quote['gross_total_minor']);
        $this->assertEquals(9880, $quote['tax_total_minor']);
        $this->assertCount(4, $quote['line_items']);
    }

    public function testOptionalComponentCanBeExcluded()
    {
        $payload = array(
            'currency_iso_code' => 'EUR',
            'pricing_method' => 'nightly',
            'nights' => 2,
            'base_line' => array(
                'code' => 'base',
                'label' => 'Nightly rate',
                'quantity' => 2,
                'unit_net_minor' => 8000,
                'unit_gross_minor' => 9280,
            ),
            'base_nightly_net_minor' => 8000,
            'base_nightly_gross_minor' => 9280,
            'season_definitions' => array(
                array(
                    'code' => 'percent',
                    'label' => 'Percent',
                    'method' => 'percent',
                    'adjustment_percent_basis_points' => 250,
                    'dates' => array('2024-03-01', '2024-03-02'),
                ),
            ),
            'package_components' => array(
                array(
                    'code' => 'optional-meal',
                    'label' => 'Optional meal',
                    'quantity' => 1,
                    'unit_net_minor' => 3000,
                    'unit_gross_minor' => 3480,
                    'is_optional' => true,
                    'selected' => false,
                ),
            ),
        );

        $quote = KLQuotePricingEngine::buildQuote($payload);

        $this->assertEquals(16400, $quote['net_total_minor']);
        $this->assertEquals(19024, $quote['gross_total_minor']);
        $this->assertEquals(2624, $quote['tax_total_minor']);
        $this->assertCount(2, $quote['line_items']);
    }
}
