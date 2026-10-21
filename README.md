# Portal Kredit Kendaraan

Monolith Laravel 13, Vue 3, dan PostgreSQL untuk jawaban tes coding PT. JKL. [Soal asli](Soal%20Tes%20Coding%202025.pdf), [jawaban tertulis](JAWABAN.md), dan [PPTX pengumpulan](output/Jawaban_Tes_Coding_2025.pptx) tersedia dalam repo ini.

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
| `output` | PPTX jawaban dan pratinjau antarmuka. |

## Menjalankan aplikasi

Prasyarat: PHP 8.3+, Composer, Node.js, PostgreSQL, serta ekstensi PHP `pdo_pgsql`. Jalankan perintah dari root repo.

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
```

Siapkan database `portal_kredit`. Jika memakai Docker Desktop, contoh perintahnya:

```powershell
docker volume create pdp-postgres-data
docker run --name pdp-postgres -e POSTGRES_DB=portal_kredit -e POSTGRES_USER=portal_kredit -e POSTGRES_PASSWORD=GantiPasswordIni -p 5432:5432 -v pdp-postgres-data:/var/lib/postgresql/data -d postgres:17
```

Isi `.env` sesuai koneksi PostgreSQL. Untuk contoh Docker di atas:

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=portal_kredit
DB_USERNAME=portal_kredit
DB_PASSWORD=GantiPasswordIni
```

Isi `JWT_SECRET` dengan keluaran perintah `php -r "echo base64_encode(random_bytes(64)), PHP_EOL;"`. Gunakan kunci yang berbeda pada setiap lingkungan. Lalu jalankan:

```powershell
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000/login`. Jika kontainer database sudah pernah dibuat tetapi berhenti, jalankan `docker start pdp-postgres`. Untuk pengembangan frontend, jalankan `npm run dev` pada terminal kedua.

`migrate --seed` membuat akun dan enam pengajuan demo saat `APP_ENV` bukan `production`. Pada lingkungan production khusus evaluasi yang aksesnya dibatasi, ganti `DEMO_PASSWORD`, isi `DEMO_SEED=true` di `.env`, lalu jalankan `php artisan migrate --seed --force`. Kembalikan `DEMO_SEED=false` jika tidak lagi memerlukan data demo.

## Akun demo

Kata sandi awal semua akun mengikuti `DEMO_PASSWORD` (`Demo12345!` pada `.env.example`).

| Peran | Username | Akses |
| --- | --- | --- |
| Marketing | `marketing` | Membuat pengajuan, melihat pengajuan sendiri, mengunggah dokumen bertanda tangan. |
| Atasan Marketing | `approver` | Melihat pengajuan, menyetujui atau menolak, melihat laporan. |
| Admin Backoffice | `backoffice` | Melihat pengajuan, menerbitkan dokumen cetak, mencatat pencairan, melihat laporan. |

Login memakai JWT berumur 15 menit dan refresh token berumur 7 hari dalam cookie HttpOnly, SameSite Lax, serta Secure pada HTTPS. Refresh token berputar setiap dipakai; logout mencabut pasangan token. Semua aksi tulis tetap dilindungi CSRF Laravel. Percobaan login dibatasi berdasarkan IP dan username.

Unggahan disimpan pada disk `local` yang privat dan diunduh melalui endpoint berizin; `storage:link` tidak diperlukan.

## Alur

1. Marketing mengisi data konsumen, kendaraan, dan pinjaman serta mengunggah KTP, kartu keluarga, dan bukti tanda jadi. Status menjadi `submitted`.
2. Atasan Marketing menyetujui (`approved`) atau menolak (`rejected`) dengan alasan. Pengajuan yang ditolak berhenti di sini.
3. Backoffice menerbitkan ringkasan kontrak dan PO yang dapat dicetak. Status menjadi `issued`.
4. Marketing mengunggah dokumen bertanda tangan. Status menjadi `signed`.
5. Backoffice mencatat nilai, tanggal, dan nomor referensi pencairan. Status menjadi `disbursed`.

Seeder menyediakan contoh berikut:

| Nomor | Status | Yang dapat diperiksa |
| --- | --- | --- |
| `CR-DEMO-001` | `submitted` | Menunggu keputusan atasan. |
| `CR-DEMO-002` | `rejected` | Alasan penolakan dan riwayat keputusan. |
| `CR-DEMO-003` | `approved` | Siap diterbitkan kontrak dan PO. |
| `CR-DEMO-004` | `issued` | Dokumen cetak tersedia; menunggu hasil tanda tangan. |
| `CR-DEMO-005` | `signed` | Dokumen tanda tangan tersedia; siap dicairkan. |
| `CR-DEMO-006` | `disbursed` | Riwayat lengkap dan referensi pencairan. |

Tiap contoh memiliki data konsumen dan kendaraan fiktif, tiga PDF pendukung yang dapat diunduh, serta riwayat tindakan sesuai tahapnya. Kasus `signed` dan `disbursed` memiliki PDF tanda tangan simulasi; kasus `disbursed` juga memiliki catatan pencairan. PDF diberi penanda simulasi dan bukan dokumen resmi. Seeder dapat dijalankan ulang tanpa menggandakan kasus.

Setiap perubahan status mencatat pelaku dan waktu. Endpoint memeriksa peran serta status di server, sehingga tindakan tidak bisa dilompati melalui URL langsung. Halaman laporan menampilkan user aktif, hak menu per user, dan jumlah user aktif per area, termasuk area tanpa user aktif.

## Pemeriksaan

```powershell
php artisan test
npm run build
```

Pengujian otomatis memakai SQLite dalam memori. Migrasi membuat tiga view laporan saat aplikasi memakai PostgreSQL. Seeder demo juga telah dijalankan dua kali pada PostgreSQL untuk memeriksa bahwa data tidak terduplikasi.

## Batas implementasi

Halaman cetak adalah ringkasan data untuk demonstrasi, bukan kontrak hukum resmi. Integrasi tanda tangan elektronik, verifikasi identitas, penilaian kredit, dan transfer dana bank belum diberikan dalam soal; pencairan di aplikasi adalah pencatatan operasional setelah dokumen TTD diterima.
