<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    protected $table = 'dosen';

    protected $fillable = ['nidn', 'nama'];

    /** Surat yang disetujui dosen ini. FK: surat.disetujui_oleh */
    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'disetujui_oleh');
    }
}
