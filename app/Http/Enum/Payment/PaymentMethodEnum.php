<?php
// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\app\Enums\Payment\PaymentMethodEnum.php

namespace App\Enums\Payment;

enum PaymentMethodEnum: string
{
    case PREPAYMENT = 'prepayment';
    case POSTPAYMENT = 'postpayment';
    case VERSEMENT = 'versement';

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all cases as key-value pairs for dropdowns
     */
    public static function toArrayForDropdown(): array
    {
        return [
            ['value' => self::PREPAYMENT->value, 'label' => 'Pre-payment'],
            ['value' => self::POSTPAYMENT->value, 'label' => 'Post-payment'],
            ['value' => self::VERSEMENT->value, 'label' => 'Versement'],
        ];
    }

    /**
     * Get the label for a payment method
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PREPAYMENT => 'Pre-payment',
            self::POSTPAYMENT => 'Post-payment', 
            self::VERSEMENT => 'Versement',
        };
    }

    /**
     * Get label by value (static method)
     */
    public static function getLabelByValue(string $value): string
    {
        return match($value) {
            self::PREPAYMENT->value => 'Pre-payment',
            self::POSTPAYMENT->value => 'Post-payment',
            self::VERSEMENT->value => 'Versement',
            default => $value,
        };
    }
}