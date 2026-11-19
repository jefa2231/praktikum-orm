# Praktikum ORM — Pengajuan Surat Mahasiswa

Praktikum Pertemuan 9, mata kuliah **IF T5251P Pemrograman Berbasis Kerangka Kerja**.
CPMK101 [C3]: integrasi database dan Object-Relational Mapping (ORM) pada sisi server.

Aplikasi Laravel + Eloquent untuk studi kasus pengajuan surat: empat tabel ternormalisasi,
CRUD lewat ORM, relasi one-to-many, eager loading, transaksi, REST API, dan latihan mandiri
(CRUD jenis surat, statistik, persetujuan dosen, soft delete).

## Stack

- PHP 8.5, Laravel 13.43, Eloquent ORM
- SQLite (default, tanpa server database); MySQL didukung lewat `.env`
- Laravel Sanctum / routing API dari `php artisan install:api`

## Menjalankan

```bash
git clone https://github.com/jefa2231/praktikum-orm.git
cd praktikum-orm
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
touch database/database.sqlite # Windows: type nul > database\database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

Base URL: `http://127.0.0.1:8000/api/v1`. Selalu kirim header `Accept: application/json`.

Untuk MySQL, isi `DB_CONNECTION=mysql` beserta host, port, nama database, user, dan password
di `.env`, buat database kosong, lalu jalankan `php artisan migrate:fresh --seed`.

`.env` dan `database/database.sqlite` **tidak** ikut di-commit (lihat `.gitignore`).

## Skema

```
mahasiswa (id, nim UNIQUE, nama, prodi, angkatan)
jenis_surat (id, kode UNIQUE, nama)
surat (id, nomor UNIQUE NULL, mahasiswa_id FK, jenis_surat_id FK,
       keperluan, status, disetujui_oleh FK NULL, timestamps, deleted_at)
riwayat_status (id, surat_id FK, status, catatan, timestamps)
dosen (id, nidn UNIQUE, nama)
```

Aturan foreign key:

| Relasi | Aturan hapus |
| --- | --- |
| `surat.mahasiswa_id` → `mahasiswa` | `cascadeOnDelete` |
| `surat.jenis_surat_id` → `jenis_surat` | `restrictOnDelete` |
| `surat.disetujui_oleh` → `dosen` | `nullOnDelete` |
| `riwayat_status.surat_id` → `surat` | `cascadeOnDelete` |

## Model & relasi

- `Mahasiswa` hasMany `Surat`; `Surat` belongsTo `Mahasiswa`
- `JenisSurat` hasMany `Surat`; `Surat` belongsTo `JenisSurat` (`jenisSurat`)
- `Surat` hasMany `RiwayatStatus` (`riwayat`), belongsTo `Dosen` (`disetujuiOleh`)
- `status` di-cast ke enum `App\Enums\StatusSurat` (`diajukan`, `diproses`, `selesai`, `ditolak`)
- `Surat` memakai `SoftDeletes`
- Aturan bisnis di model: `Surat::ajukan()` (nomor + riwayat dalam satu transaksi) dan
  `ubahStatus()` (transisi status divalidasi enum, `DomainException` bila melanggar)

## Endpoint API

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/api/v1/jenis-surat` | daftar jenis surat + `surat_count` |
| POST | `/api/v1/jenis-surat` | tambah jenis surat (201) |
| GET | `/api/v1/jenis-surat/{id}` | detail jenis surat |
| PUT/PATCH | `/api/v1/jenis-surat/{id}` | ubah jenis surat |
| DELETE | `/api/v1/jenis-surat/{id}` | hapus; **409** bila masih dipakai surat |
| GET | `/api/v1/statistik` | jumlah surat per status & per jenis surat |
| GET | `/api/v1/mahasiswa?q=andi&per_page=10` | cari nama/NIM, paginasi, `surat_count` |
| POST | `/api/v1/mahasiswa` | tambah mahasiswa (201) |
| GET | `/api/v1/mahasiswa/{id}` | detail + `surat.jenisSurat` |
| PUT/PATCH | `/api/v1/mahasiswa/{id}` | ubah mahasiswa |
| DELETE | `/api/v1/mahasiswa/{id}` | hapus mahasiswa + suratnya (204) |
| GET | `/api/v1/surat?status=diproses&per_page=2` | daftar surat + relasi (eager loading) |
| POST | `/api/v1/surat` | ajukan surat: nomor + riwayat (201) |
| GET | `/api/v1/surat/{id}` | detail + mahasiswa, jenis surat, riwayat, dosen penyetuju |
| PATCH | `/api/v1/surat/{id}/status` | ubah status (422 bila transisi terlarang) |
| DELETE | `/api/v1/surat/{id}` | soft delete (204) |
| GET | `/api/v1/surat/terhapus` | daftar surat yang di-soft-delete |
| POST | `/api/v1/surat/{id}/restore` | pulihkan surat |

## Latihan mandiri

1. **CRUD jenis surat** — `JenisSuratController` + `Route::apiResource`. Penghapusan jenis surat
   yang masih dipakai mengembalikan **409 Conflict** dengan pesan jelas, bukan 500.
   Pengecekan memakai `$jenisSurat->surat()->withTrashed()->exists()` karena surat memakai soft delete.
2. **Statistik** — `GET /api/v1/statistik` memakai `selectRaw(...)->groupBy(...)->pluck(...)`
   dan `withCount('surat')`.
3. **Dosen & persetujuan** — tabel `dosen`, kolom `surat.disetujui_oleh` (nullable, `nullOnDelete`),
   relasi `Surat::disetujuiOleh()`, dan dosen penyetuju tampil di detail surat.
4. **Soft delete (bonus)** — `SoftDeletes` pada `Surat`, endpoint daftar terhapus dan restore.
   Saat mahasiswa dihapus, suratnya tetap dihapus permanen agar aturan cascade terjaga.

## Bukti pengujian

Semua dijalankan pada PHP 8.5.12 / Laravel 13.43.0 / SQLite 3.53.4.

```text
php artisan migrate:fresh --seed   -> 11 migration Ran, seeder OK
php cek.php                        -> HASIL: 26 OK, 0 GAGAL
N+1 tanpa eager loading            -> 23 query (22 surat)
N+1 dengan with('mahasiswa')       -> 2 query
```

Sepuluh skenario API pada modul menghasilkan status code sesuai harapan:
`200, 200, 201, 422, 200, 200, 201, 422, 200, 422, 200, 204, 404, 404`.
Latihan mandiri: `409` saat hapus jenis surat terpakai, `201/200/204` CRUD jenis surat,
`200` statistik, `200` detail surat dengan dosen, `204/404/200/200/200` alur soft delete–restore.

Laporan lengkap (checkpoint, jawaban pertanyaan, tabel pengujian): [`laporan/LAPORAN.md`](laporan/LAPORAN.md).
