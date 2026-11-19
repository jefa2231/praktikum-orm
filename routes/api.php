<?php

use App\Http\Controllers\Api\JenisSuratController;
use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\SuratController;
use App\Models\JenisSurat;
use App\Models\Surat;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Latihan 2: jumlah surat per status dan per jenis surat
    Route::get('statistik', fn () => [
        'per_status' => Surat::selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status'),
        'per_jenis_surat' => JenisSurat::withCount('surat')->get(['id', 'kode', 'nama']),
    ]);

    // Latihan 1: CRUD jenis surat (409 jika masih dipakai)
    Route::apiResource('jenis-surat', JenisSuratController::class);

    Route::apiResource('mahasiswa', MahasiswaController::class);

    // Latihan 4: soft delete. Route statis harus didaftarkan sebelum {surat}.
    Route::get('surat/terhapus', [SuratController::class, 'terhapus']);
    Route::post('surat/{id}/restore', [SuratController::class, 'restore']);

    Route::apiResource('surat', SuratController::class)->except('update');
    Route::patch('surat/{surat}/status', [SuratController::class, 'ubahStatus']);
});
