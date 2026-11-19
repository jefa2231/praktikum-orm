<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            // Boleh NULL: surat yang belum disetujui tidak punya dosen.
            // nullOnDelete: dosen dihapus -> kolom ini jadi NULL, surat tetap ada.
            $table->foreignId('disetujui_oleh')
                  ->nullable()
                  ->constrained('dosen')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disetujui_oleh');
        });
    }
};
