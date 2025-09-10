<?php

namespace App\Models\CONFIGURATION;

use Illuminate\Database\Eloquent\Relations\Pivot; // Import Pivot class
use App\Enums\Payment\PaymentMethodEnum; // Import the enum
use App\Models\User; // Import the User model

class UserPaymentMethod extends Pivot
{
    // If your pivot table name is not alphabetical (e.g., user_payment_method instead of payment_method_user)
    protected $table = 'user_payment_method';

    protected $fillable = [
        'user_id',
        'payment_method_key', // Make sure this is fillable
        'status',
    ];

    protected $casts = [
        // Cast the payment_method_key to the Enum for convenience
        'payment_method_key' => 'array', // Cast JSON to array
    ];

    // Define relationships to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
     // Helper method to add a payment method
    public function addPaymentMethod(string $method)
    {
        $methods = $this->payment_method_key ?? [];
        if (!in_array($method, $methods)) {
            $methods[] = $method;
            $this->payment_method_key = $methods;
        }
    }

    // Helper method to remove a payment method
    public function removePaymentMethod(string $method)
    {
        $methods = $this->payment_method_key ?? [];
        $this->payment_method_key = array_values(array_filter($methods, fn($m) => $m !== $method));
    }

    // Helper method to check if user has a specific payment method
    public function hasPaymentMethod(string $method): bool
    {
        return in_array($method, $this->payment_method_key ?? []);
    }
}