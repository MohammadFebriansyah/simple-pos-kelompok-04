<?php

namespace App\Jobs;

use App\Models\ImportError;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public int $timeout = 60;

    public function __construct(public string $path)
    {
    }

    public function handle(): void
    {
        if (!Storage::exists($this->path)) {
            throw new RuntimeException('File CSV tidak ditemukan.');
        }

        ImportError::truncate();

        $lines = file(
            Storage::path($this->path),
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        if (empty($lines)) {
            Storage::delete($this->path);
            return;
        }

        $headers = array_map('trim', str_getcsv(array_shift($lines)));

        $rowNumber = 1; 

        foreach ($lines as $line) {
            $rowNumber++;
            $values = str_getcsv($line);

            if (count($headers) !== count($values)) {
                ImportError::create([
                    'row_number' => $rowNumber,
                    'error_message' => 'Jumlah kolom tidak sesuai dengan header.',
                ]);
                continue;
            }

            $row = array_combine($headers, $values);

            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'category_id' => ['required', 'exists:categories,id'],
                'price' => ['required', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                $errorMsg = implode(', ', $validator->errors()->all());
                ImportError::create([
                    'row_number' => $rowNumber,
                    'error_message' => $errorMsg,
                ]);
                continue;
            }

            $data = $validator->validated();

            Product::updateOrCreate(
                ['name' => $data['name']],
                [
                    'category_id' => $data['category_id'],
                    'price' => $data['price'],
                ]
            );
        }

        Storage::delete($this->path);
    }
}