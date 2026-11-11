# SIRGA ASN — Update UI/UX v2

Paket ini **hanya berisi file yang berubah** (`resources/`, 3 file PHP dashboard, dan skrip `scripts/sirga-doctor.sh`).
Tidak menyentuh `.env`, `vendor/`, `node_modules/`, database, `routes/`, `config/`, atau `bootstrap/`.

## Cara pasang di Mac (copy-paste, urut)

> ⚠️ **Jangan** drag-and-drop folder lewat Finder dan memilih "Ganti/Replace". Finder akan **menghapus** isi folder
> lama yang namanya sama (itulah penyebab `bootstrap/cache`, tabel `cache`, `flashToast.ts` hilang sebelumnya).
> Gunakan `rsync` di bawah: ia hanya menimpa file yang ada di ZIP dan **tidak pernah menghapus** file lain.

```bash
# 0) Masuk ke proyek + backup cepat (di Desktop)
cd /Applications/MAMP/htdocs/sirga
tar -czf ~/Desktop/sirga-backup-$(date +%Y%m%d-%H%M).tar.gz --exclude=node_modules --exclude=vendor --exclude=storage/logs .

# 1) Ekstrak ZIP ke folder SEMENTARA (bukan ke proyek)
rm -rf /tmp/sirga-ui && mkdir -p /tmp/sirga-ui
unzip -oq ~/Downloads/sirga-ui-v2.zip -d /tmp/sirga-ui

# 2) Gabungkan ke proyek (perhatikan garis miring di akhir kedua path)
rsync -av /tmp/sirga-ui/sirga/ /Applications/MAMP/htdocs/sirga/

# 3) Periksa kesehatan proyek; file starter yang hilang dipulihkan otomatis
bash scripts/sirga-doctor.sh

# 4) Build ulang frontend dan bersihkan cache
npm run build
php artisan optimize:clear
```

Lalu buka `http://127.0.0.1:8000` (atau `npm run dev` + `php artisan serve` selama pengembangan) dan tekan **⌘ + Shift + R** untuk memuat ulang tanpa cache.

### Kalau ingin kembali ke versi sebelumnya
```bash
cd /Applications/MAMP/htdocs/sirga
tar -xzf ~/Desktop/sirga-backup-XXXXXXXX-XXXX.tar.gz -C .     # ganti nama file sesuai backup Anda
npm run build
```

### Kalau ada masalah
Jalankan `bash scripts/sirga-doctor.sh` — skrip ini mengecek 7 hal (file inti Laravel, migrasi cache/jobs, file
frontend starter, folder tulis, berkas SIRGA, sisa starter, dependensi) dan memulihkan yang hilang tanpa menimpa apa pun.
Skrip bisa dijalankan kapan saja, aman diulang.

## Yang baru

**Sistem desain**: gradient navy berlapis, aksen emas tipis, pola titik, kartu bertingkat, tipografi lebih rapat,
animasi masuk halus (menghormati *reduced motion*), skeleton/transisi saat pindah halaman.

**Visualisasi data (tanpa library tambahan)**: grafik batang tren per periode, donut komposisi gaji, progress ring
kelengkapan/penagihan, bar komposisi, sparkline, angka yang berhitung naik (count-up).

**Guru**: hero "Yang Diterimakan" dengan animasi angka, komposisi gaji (donut), tren 6 periode, timeline status
pembayaran, kartu rekening bergaya kartu bank, unggah bukti drag-and-drop, bottom-nav melayang di ponsel, ringkasan
riwayat dengan grafik.

**Admin**: dashboard KPI + tren + penagihan potongan sekolah (donut/ring), timeline alur periode, kartu periode
dengan progress ring, **command palette ⌘K / Ctrl+K** untuk lompat ke menu mana pun, sidebar baru dengan kartu
pengguna, breadcrumb halaman aktif, tabel lebih rapi, empty state berilustrasi, tombol dengan varian emas.

**Login**: panel brand dengan pratinjau slip melayang, ikon di input, peringatan Caps Lock.

**Aksesibilitas**: tautan "Lewati ke konten", `aria-current`, label grafik, fokus terlihat, target sentuh ≥ 44px.
