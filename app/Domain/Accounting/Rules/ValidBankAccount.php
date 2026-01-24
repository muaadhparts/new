<?php

namespace App\Domain\Accounting\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valid Bank Account Rule
 *
 * Validates Saudi bank account (IBAN) format.
 */
class ValidBankAccount implements ValidationRule
{
    /**
     * Country code
     */
    protected string $countryCode;

    /**
     * Create a new rule instance.
     */
    public function __construct(string $countryCode = 'SA')
    {
        $this->countryCode = strtoupper($countryCode);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(__('validation.bank_account.must_be_string'));
            return;
        }

        $iban = strtoupper(preg_replace('/\s+/', '', $value));

        // Saudi IBAN: SA + 2 check digits + 2 bank code + 18 account number = 24 chars
        if ($this->countryCode === 'SA') {
            if (!preg_match('/^SA[0-9]{22}$/', $iban)) {
                $fail(__('validation.bank_account.invalid_saudi_iban'));
                return;
            }
        }

        // Validate IBAN checksum
        if (!$this->validateChecksum($iban)) {
            $fail(__('validation.bank_account.invalid_checksum'));
        }
    }

    /**
     * Validate IBAN checksum using MOD 97-10.
     */
    protected function validateChecksum(string $iban): bool
    {
        // Move first 4 chars to end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Convert letters to numbers (A=10, B=11, etc.)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord($char) - 55);
            } else {
                $numeric .= $char;
            }
        }

        // Check MOD 97
        return bcmod($numeric, '97') === '1';
    }
}
