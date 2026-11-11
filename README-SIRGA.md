# SIRGA ASN — Sistem Informasi Rincian Gaji ASN (SMA Negeri 1 Turen)

Aplikasi Laravel 13 + Vue 3 + Inertia (starter kit Vue) sesuai `PRD.md` dan `DESIGN.md`.
ZIP ini berisi **file aplikasi yang ditumpuk di atas starter kit Vue** di folder `sirga` — bukan proyek lengkap (tanpa `vendor/` dan `node_modules/`).

## 1. Instalasi (urut)

> Backup folder `sirga` dulu (atau commit ke git).

**a. Hapus file bawaan starter kit** yang digantikan / tidak dipakai (login memakai NIP, tanpa registrasi/2FA/passkey/pengaturan bawaan):

```
app/Actions
app/Concerns
app/Http/Controllers/Settings
app/Http/Requests/Settings
app/Http/Middleware/HandleAppearance.php
routes/settings.php
database/factories/UserFactory.php
database/migrations/2024_01_01_000000_create_passkeys_table.php
database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php
tests/Feature/Auth
tests/Feature/Settings
tests/Feature/DashboardTest.php
tests/Feature/ExampleTest.php
tests/Unit/ExampleTest.php
resources/js/pages            (seluruh isi)
resources/js/layouts          (seluruh isi)
resources/js/composables      (seluruh isi)
resources/js/types            (seluruh isi)
resources/js/components       (SEMUA kecuali subfolder ui/)
resources/js/actions  resources/js/routes  resources/js/wayfinder   (jika ada)
```

**b. Salin seluruh isi ZIP ke folder `sirga`** (timpa bila ditanya). File starter yang tertimpa: `bootstrap/app.php`, `config/fortify.php`, `config/inertia.php`, `app/Providers/*`, `app/Models/User.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Controllers/Controller.php`, `routes/web.php`, `resources/views/app.blade.php`, `resources/css/app.css`, `resources/js/app.ts`, `vite.config.ts`, `database/migrations/0001_01_01_000000_create_users_table.php`, `database/seeders/DatabaseSeeder.php`, `.env.example`.

**c. Paket PHP tambahan**

```bash
composer require maatwebsite/excel barryvdh/laravel-dompdf
```

Ekstensi PHP yang dibutuhkan: `gd`, `zip`, `xml`, `mbstring`, `fileinfo` (PHP 8.4 standar sudah memenuhi).

**d. Konfigurasi & database**

```bash
cp .env.example .env            # lalu isi DB_DATABASE / DB_USERNAME / DB_PASSWORD (MySQL/MariaDB)
php artisan key:generate
php artisan migrate --seed      # password akun awal ditampilkan di terminal
```

**e. Frontend**

```bash
npm install
npm run build                   # butuh internet (mengunduh font Plus Jakarta Sans dari Bunny Fonts)
# pengembangan: npm run dev
```

**f. Jalankan** — `php artisan serve` (atau Laragon/Valet). Jangan jalankan `php artisan storage:link`: bukti transfer dan aset sekolah sengaja disimpan privat di `storage/app/private`.

> Data uji fiktif (opsional): `php artisan db:seed --class=DemoSeeder` → 5 guru demo (password `Demo12345`), periode berjalan status Review.

## 2. Langkah pertama setelah login

1. Login Super Admin (NIP/password dari terminal saat seed) → wajib ganti password.
2. **Pengaturan**: isi identitas sekolah, logo, nama/NIP bendahara, QR tanda tangan, dan **Rekening Bendahara** (wajib lengkap sebelum publikasi).
3. **Data ASN**: tambah satu per satu, atau **Buat akun dari Excel** memakai file gaji (PNS lalu PPPK) → password awal acak ditampilkan sekali (bisa diunduh CSV).
4. **Periode Gaji** → Buat periode → **Impor Excel** (pilih PNS/PPPK, unggah, periksa hasil validasi, konfirmasi).
5. **Potongan Sekolah**: atur nominal (massal / per ASN).
6. Periode → **Review** → periksa kelengkapan → **Publikasikan**. Guru mendapat notifikasi.
7. Guru login → lihat rincian, unduh slip PDF, transfer, unggah bukti → Admin Keuangan **Pembayaran** → Setujui/Tolak.
8. Akhir bulan: **Tutup periode**.

## 3. Keputusan yang saya ambil di luar PRD (mohon dicek)

| Hal | Keputusan |
|---|---|
| Format Excel | Dianalisis dari `SMAN_1_TUREN.xls`: header 2 baris bergabung; **Gaji Kotor = jumlah kolom penghasilan** (termasuk BPJS 4%, JKK, JKm, TAPERA, PPh, Pembulatan), **Gaji Bersih = Kotor − Jumlah potongan**. Nama kolom yang sama di sisi penghasilan & potongan dipisah per jenis. Kolom baru di file otomatis menjadi komponen baru saat konfirmasi. |
| Selisih hitung vs file | Tidak memblokir impor, tetapi rincian berstatus **Selisih sumber** dan **memblokir publikasi** sampai ditandai diperiksa. |
| Duplikasi periode | Nominal disalin sebagai bahan, status **Perlu verifikasi** (memblokir publikasi sampai diverifikasi). |
| Potongan sekolah | Master → di-*snapshot* ke periode saat periode dibuat. "Bulanan otomatis = Tidak" → nominal awal Rp0, diisi manual. |
| Password awal | Acak 10 karakter, tampil sekali, wajib diganti saat login pertama. Reset hanya oleh Super Admin. |
| Akun massal dari Excel | Fitur eksplisit Super Admin (terpisah dari impor gaji, yang **tidak** membuat akun). |
| Pembayaran | Satu rekening aktif global. Bukti baru hanya bisa diunggah bila status Belum Dibayar/Ditolak. Verifikasi tetap bisa pada periode Closed. |
| Tampilan guru | Komponen penghasilan/potongan resmi bernilai Rp0 disembunyikan; potongan sekolah Rp0 mengikuti pengaturan "Tampilkan Rp0". |
| Koreksi setelah publikasi | Diizinkan dengan alasan wajib; versi lama disimpan (`payroll_record_revisions`), guru diberi notifikasi. |
| Hak akses | Super Admin juga dapat melakukan fungsi keuangan. Admin dengan data ASN punya menu "Gaji Saya". |
| Kolom tambahan | `employees.npwp`, `payroll_records.validation_status` & kolom `source_*`, tabel `import_rows`, `payroll_record_revisions`, `period_deduction_configs`, `user_notifications`. |

## 4. Keamanan yang diterapkan

Hash password bcrypt; login dibatasi 5×/menit; akun nonaktif ditolak & sesinya diakhiri; sesi 60 menit (`SESSION_LIFETIME`); akses data guru selalu dibatasi ke `employee` milik login (tidak ada `employee_id` dari request); bukti transfer di disk privat dengan nama acak + validasi MIME + diunduh lewat route berotorisasi; audit log *append-only*; perhitungan memakai `BigDecimal` (bukan float); header keamanan dasar. **Untuk produksi:** pakai HTTPS, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan jadwalkan backup database + `storage/app/private`.

## 5. Pengujian

```bash
php artisan test
```
Berisi tes unit (Money, kalkulator, parser Excel) dan tes akses (isolasi data guru, login NIP, periode draft tersembunyi).

## 6. Yang perlu Anda ketahui jujur

- Saya **tidak dapat menjalankan Laravel penuh** di lingkungan saya (tanpa akses Packagist). Yang sudah diverifikasi: seluruh PHP lolos *lint*; 9 tes unit lulus; `.xls` asli Anda dibaca **22/22 baris tanpa selisih** (total kotor Rp124.213.724 cocok dengan baris total di file); template buatan aplikasi terbaca ulang oleh parser dengan struktur identik; frontend lolos `vue-tsc` dan `vite build`; komponen kunci di-render. Yang **belum** teruji: eksekusi migrasi, controller/Eloquent end-to-end, PDF slip, ekspor Excel, dan tes *feature*. Kemungkinan ada perbaikan kecil saat pertama dijalankan — kirim pesan error-nya, saya perbaiki.
- NIP pada Excel **harus berformat teks** (seperti file Anda). Jika berupa angka, Excel menyimpan hanya ±15 digit sehingga NIP tidak cocok (baris masuk daftar pengecualian, tidak salah impor).
- Pengumuman terjadwal tampil otomatis saat waktunya tiba, tetapi notifikasi lonceng hanya dikirim untuk yang diterbitkan langsung.
- Belum ada: SSR, dark mode, audit aksesibilitas formal (WCAG), tes beban, integrasi tanda tangan elektronik tersertifikasi (Phase 3 PRD).
