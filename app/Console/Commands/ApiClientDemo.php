<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApiClientDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate consuming the Simple POS API as an external client';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = 'http://127.0.0.1:8000';

        // klien yang baik mengulang hanya error yang memang layak diulang, dengan jeda yang makin panjang
        $when = fn ($e) => $e instanceof ConnectionException
            || in_array($e->response->status(), [429, 502, 503, 504]);

        $backoff = fn (int $attempt) => $attempt * 1000;

        $login = Http::acceptJson()->post("{$baseUrl}/api/v1/login", [
            'email' => 'kasir@pos.test',
            'password' => 'password-salah',
        ]);

        if ($login->failed()) {
            $this->error('Login gagal: '.$login->json('message'));

            return self::FAILURE;
        }

        $token = $login->json('token');
        $this->info("Login berhasil, token: {$token}");

        $products = Http::acceptJson()->withToken($token)->retry(3, $backoff, $when, throw: false)->get("{$baseUrl}/api/v1/products");

        if ($products->failed()) {
            $this->error('Token kedaluwarsa/dicabut, atau server sedang bermasalah.');

            return self::FAILURE;
        }

        $this->info('Jumlah produk: '.count($products->json('data')));

        $productId = Product::first()->id;
        $payload = ['items' => [['product_id' => $productId, 'qty' => 1]]];
        $key = (string) Str::uuid();

        $transaction = Http::acceptJson()->withToken($token)
            ->withHeaders(['Idempotency-Key' => $key])
            ->retry(3, $backoff, $when, throw: false)
            ->post("{$baseUrl}/api/v1/transactions", $payload);

        match ($transaction->status()) {
            201 => $this->info('Transaksi tersimpan: '.json_encode($transaction->json('data'))),
            401 => $this->error('Token tidak valid, login ulang.'),
            422 => $this->error('Input ditolak: '.$transaction->json('message')),
            default => $this->error('Error lain: '.$transaction->status()),
        };

        // simulasi retry setelah timeout: request yang sama dengan key yang sama
        $retry = Http::acceptJson()->withToken($token)
            ->withHeaders(['Idempotency-Key' => $key])
            ->retry(3, $backoff, $when, throw: false)
            ->post("{$baseUrl}/api/v1/transactions", $payload);

        $this->info('Ulang dengan key yang sama: '.$retry->status().', replayed = '.$retry->header('Idempotent-Replayed'));

        return self::SUCCESS;
    }
}
