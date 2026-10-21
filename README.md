# Portal Kredit Kendaraan

Monolith Laravel 13, Vue 3, dan PostgreSQL untuk jawaban tes coding PT. JKL. Analisis lengkap soal ada di [JAWABAN.md](JAWABAN.md). Rekap untuk dikumpulkan ada di `output/Jawaban_Tes_Coding_2025.pptx`.

## Struktur proyek

| Lokasi | Tanggung jawab |
| --- | --- |
| `app/Http/Controllers` | Menerima HTTP dan mengembalikan JSON atau unduhan. |
| `app/Http/Requests` | Validasi data dan otorisasi tindakan. |
| `app/Policies` | Aturan akses per peran dan pemilik pengajuan. |
| `app/Services` | Transaksi pengajuan, perpindahan status, dokumen, riwayat, dan query laporan. |
| `app/Models` | Relasi data Eloquent. |
| `database/migrations`, `database/seeders` | Skema PostgreSQL, view laporan, dan akun demo. |
| `resources/js/pages` | Halaman Vue dalam proyek Laravel yang sama. |

## Menjalankan aplikasi

Prasyarat: PHP 8.3+, Composer, Node.js, PostgreSQL, serta ekstensi PHP `pdo_pgsql`.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database PostgreSQL bernama `portal_kredit`, lalu isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`. Isi `JWT_SECRET` dengan keluaran perintah `php -r "echo base64_encode(random_bytes(64)), PHP_EOL;"`. Gunakan kunci yang berbeda pada setiap lingkungan. Jalankan:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`. Seeder menyediakan akun demo `marketing`, `approver`, dan `backoffice`; kata sandinya mengikuti `DEMO_PASSWORD` di `.env.example`. Akun demo hanya dibuat di lingkungan nonproduksi.

Login memakai JWT berumur 15 menit dan refresh token berumur 7 hari dalam cookie HttpOnly, SameSite Lax, serta Secure pada HTTPS. Refresh token berputar setiap dipakai; logout mencabut pasangan token. Semua aksi tulis tetap dilindungi CSRF Laravel. Percobaan login dibatasi berdasarkan IP dan username.

Untuk pengembangan frontend, jalankan `npm run dev` pada terminal kedua. Unggahan disimpan pada disk `local` yang privat; tidak memerlukan `storage:link`.

## Alur

1. Marketing masuk dan membuat pengajuan dengan KTP, kartu keluarga, dan bukti tanda jadi.
2. Atasan marketing menyetujui atau menolak, disertai catatan saat menolak.
3. Backoffice menerbitkan ringkasan kontrak dan PO yang dapat dicetak.
4. Marketing mengunggah dokumen bertanda tangan.
5. Backoffice mencatat pencairan dengan nomor referensi unik.

Setiap perubahan status mencatat pelaku dan waktu. Endpoint memeriksa peran serta status di server, sehingga tindakan tidak bisa dilompati melalui URL langsung.

## Pemeriksaan

```bash
php artisan test
npm run build
```

Pengujian otomatis memakai SQLite dalam memori agar dapat berjalan tanpa layanan database lokal. Skema dan tiga view laporan PostgreSQL dibuat oleh migrasi saat aplikasi memakai `DB_CONNECTION=pgsql`. Pengujian terhadap server PostgreSQL sungguhan perlu dijalankan pada lingkungan yang menyediakan layanan tersebut.

## Batas implementasi

Halaman cetak adalah ringkasan data untuk demonstrasi, bukan kontrak hukum resmi. Integrasi tanda tangan elektronik, verifikasi identitas, penilaian kredit, dan transfer dana bank belum diberikan dalam soal; pencairan di aplikasi adalah pencatatan operasional setelah dokumen TTD diterima.
