<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithChunkReading, WithCustomCsvSettings, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public function model(array $row): ?Product
    {
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return null;
        }

        $data = $validator->validated();
        $product = Product::firstOrNew([
            'name' => $data['name'],
            'category_id' => $data['category_id'],
        ]);
        $product->price = $data['price'];

        $product->exists ? $this->updated++ : $this->created++;

        // ToModel menyerahkan penyimpanan model ke Laravel Excel.
        return $product;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getCsvSettings(): array
    {
        return ['delimiter' => ',', 'input_encoding' => 'UTF-8'];
    }
}
