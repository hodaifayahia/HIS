<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
// app/Http/Requests/StoreProductReserveRequest.php

class StoreProductReserveRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id'            => ['nullable', 'exists:products,id'],
            'reserve_id'            => ['nullable', 'exists:reserves,id'],
            'pharmacy_product_id'   => ['nullable', 'exists:pharmacy_products,id'],
            'stockage_id'           => ['nullable', 'exists:stockages,id'],
            'pharmacy_stockage_id'  => ['nullable', 'exists:pharmacy_stockages,id'],
            'destination_service_id'=> ['nullable', 'exists:services,id'],
            'quantity'              => ['required', 'integer', 'min:1'],
            'expires_at'            => ['nullable', 'date', 'after:now'],
            'source'                => ['nullable', 'string', 'max:50'],
            'reservation_notes'     => ['nullable', 'string', 'max:1000'],
            'meta'                  => ['nullable', 'json'],
        ];
    }
}
