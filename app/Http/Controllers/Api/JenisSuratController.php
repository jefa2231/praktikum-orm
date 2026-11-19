<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisSuratController extends Controller
{
    /** GET /api/v1/jenis-surat */
    public function index()
    {
        return ['data' => JenisSurat::withCount('surat')->orderBy('nama')->get()];
    }

    /** POST /api/v1/jenis-surat */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:10', 'unique:jenis_surat,kode'],
            'nama' => ['required', 'string', 'max:100'],
        ]);

        return response()->json(['data' => JenisSurat::create($data)], 201);
    }

    /** GET /api/v1/jenis-surat/{jenisSurat} */
    public function show(JenisSurat $jenisSurat)
    {
        return ['data' => $jenisSurat->loadCount('surat')];
    }

    /** PUT/PATCH /api/v1/jenis-surat/{jenisSurat} */
    public function update(Request $request, JenisSurat $jenisSurat)
    {
        $data = $request->validate([
            'kode' => ['sometimes', 'string', 'max:10',
                       Rule::unique('jenis_surat', 'kode')->ignore($jenisSurat->id)],
            'nama' => ['sometimes', 'string', 'max:100'],
        ]);

        $jenisSurat->update($data);

        return ['data' => $jenisSurat];
    }

    /** DELETE /api/v1/jenis-surat/{jenisSurat} */
    public function destroy(JenisSurat $jenisSurat)
    {
        // withTrashed(): surat yang sudah di-soft-delete tetap memegang foreign key,
        // jadi tetap dihitung agar database tidak melempar error 500.
        if ($jenisSurat->surat()->withTrashed()->exists()) {
            return response()->json([
                'message' => 'Jenis surat masih dipakai oleh surat lain sehingga tidak bisa dihapus.',
            ], 409);
        }

        $jenisSurat->delete();

        return response()->noContent();
    }
}
