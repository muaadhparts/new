<?php

namespace App\Domain\Payment\Constants;

/**
 * AcceptedCurrencies
 *
 * Currency codes accepted by various payment gateways.
 */
final class AcceptedCurrencies
{
    /**
     * Mollie accepted currency codes
     */
    public const MOLLIE = [
        'AED', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK',
        'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'ILS', 'ISK', 'JPY',
        'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB',
        'SEK', 'SGD', 'THB', 'TWD', 'USD', 'ZAR',
    ];

    /**
     * Flutterwave accepted currency codes
     */
    public const FLUTTERWAVE = [
        'BIF', 'CAD', 'CDF', 'CVE', 'EUR', 'GBP', 'GHS', 'GMD',
        'GNF', 'KES', 'LRD', 'MWK', 'NGN', 'RWF', 'SLL', 'STD',
        'TZS', 'UGX', 'USD', 'XAF', 'XOF', 'ZMK', 'ZMW', 'ZWD',
    ];

    /**
     * Mercadopago accepted currency codes
     */
    public const MERCADOPAGO = [
        'ARS', 'BRL', 'CLP', 'MXN', 'PEN', 'UYU', 'VEF',
    ];

    /**
     * Stripe accepted currency codes (most common)
     */
    public const STRIPE = [
        'USD', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS',
        'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BIF',
        'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BWP', 'BYN', 'BZD',
        'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CVE',
        'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR',
        'FJD', 'FKP', 'GBP', 'GEL', 'GIP', 'GMD', 'GNF', 'GTQ',
        'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS',
        'INR', 'ISK', 'JMD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF',
        'KRW', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL',
        'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO',
        'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN',
        'NIO', 'NOK', 'NPR', 'NZD', 'PAB', 'PEN', 'PGK', 'PHP',
        'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF',
        'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS',
        'SRD', 'STD', 'SZL', 'THB', 'TJS', 'TOP', 'TRY', 'TTD',
        'TWD', 'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VND', 'VUV',
        'WST', 'XAF', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW',
    ];

    /**
     * MyFatoorah accepted currency codes
     */
    public const MYFATOORAH = [
        'KWD', 'SAR', 'BHD', 'AED', 'QAR', 'OMR', 'JOD', 'EGP',
    ];

    /**
     * Tap accepted currency codes
     */
    public const TAP = [
        'KWD', 'SAR', 'BHD', 'AED', 'QAR', 'OMR', 'JOD', 'EGP', 'USD',
    ];

    /**
     * Moyasar accepted currency codes
     */
    public const MOYASAR = [
        'SAR', 'USD', 'EUR', 'GBP',
    ];

    /**
     * Check if currency is accepted by gateway
     */
    public static function isAccepted(string $gateway, string $currencyCode): bool
    {
        $currencies = self::getForGateway($gateway);
        return in_array(strtoupper($currencyCode), $currencies, true);
    }

    /**
     * Get currencies for a specific gateway
     */
    public static function getForGateway(string $gateway): array
    {
        return match (strtolower($gateway)) {
            'mollie' => self::MOLLIE,
            'flutterwave' => self::FLUTTERWAVE,
            'mercadopago' => self::MERCADOPAGO,
            'stripe' => self::STRIPE,
            'myfatoorah' => self::MYFATOORAH,
            'tap' => self::TAP,
            'moyasar' => self::MOYASAR,
            default => [],
        };
    }
}
