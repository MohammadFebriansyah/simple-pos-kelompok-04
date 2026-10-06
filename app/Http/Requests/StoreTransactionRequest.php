<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            foreach ($this->items ?? [] as $index => $item) {

                if (!isset($item['product_id']) || !isset($item['qty'])) {
                    continue;
                }

                $product = Product::find($item['product_id']);

                if (!$product) {
                    continue;
                }

                if ($item['qty'] > $product->stock) {
                    $validator->errors()->add(
                        "items.$index.qty",
                        "Stok produk {$product->name} tidak mencukupi. Stok tersedia: {$product->stock}."
                    );
                }
            }
        });
    }
}