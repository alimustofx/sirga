# SIRGA ASN — Update v4 (final polish)

Paket ini berisi file yang berubah: `app/`, `routes/`, `resources/`, `scripts/`, dan 1 tes baru.
Tidak menyentuh `.env`, `vendor/`, `node_modules/`, database/migrasi, `config/`, atau `bootstrap/`.
**Tidak perlu `php artisan migrate`.**

## Pasang di Mac — satu baris

```bash
unzip -p ~/Downloads/sirga-ui-v4.zip sirga/scripts/sirga-update.sh | bash -s -- ~/Downloads/sirga-ui-v4.zip /Applications/MAMP/htdocs/sirga
```

Skrip: ① backup ke Desktop → ② ekstrak ke folder sementara → ③ `rsync` (hanya menimpa, tidak menghapus) →
④ `sirga-doctor.sh` (memeriksa & memulihkan file starter yang hilang) → ⑤ `npm run build` + `php artisan optimize:clear`.

> ⚠️ Jangan drag-and-drop folder lewat Finder lalu "Ganti" — Finder menghapus isi folder lama.

### Manual (jika mau)
```bash
cd /Applications/MAMP/htdocs/sirga
rm -rf /tmp/sirga-ui && mkdir -p /tmp/sirga-ui && unzip -oq ~/Downloads/sirga-ui-v4.zip -d /tmp/sirga-ui
rsync -av /tmp/sirga-ui/sirga/ ./
bash scripts/sirga-doctor.sh && npm run build && php artisan optimize:clear
```

### Kembali ke versi sebelumnya
```bash
tar -xzf ~/Desktop/sirga-backup-XXXXXXXX-XXXXXX.tar.gz -C /Applications/MAMP/htdocs/sirga && npm run build
```

Setelah selesai, muat ulang browser dengan **⌘ + Shift + R** (di ponsel: tutup tab lalu buka lagi).

## Favicon dari logo sekolah
Unggah logo di **Pengaturan › Identitas Sekolah**. Logo otomatis menjadi ikon tab browser (64×64) dan ikon layar utama
ponsel (180×180, latar putih). Belum ada logo → memakai ikon bawaan. Perlu ekstensi PHP **GD** (sudah dibutuhkan paket Excel).

## Yang berubah
- **Halaman admin lain** dirapikan dengan gaya "Ledger": paginasi di **semua** daftar besar (Data ASN, Penghasilan, Potongan,
  Pembayaran, Laporan, Audit, Pengumuman, Komponen, Periode, pratinjau impor, Riwayat guru).
- **Perbaikan meluap**: legenda penagihan, angka rupiah yang patah di tengah, nama file panjang, kartu statistik sempit di layar
  1024px, header guru di tablet, grid yang tidak bisa menyusut.
- **Ponsel**: font dasar 16px (sebelumnya terhitung 14px sehingga teks kecil), input 16px → **tidak ada auto-zoom iOS**,
  `viewport-fit=cover` + safe-area, target sentuh ≥ 40px, matriks potongan menjadi **kartu per ASN**, navigasi bawah sampai
  lebar 1024px, teks unggah "Ketuk untuk memilih foto".
- **Keamanan**: batas percobaan ganti password/email, header HSTS & `no-store` untuk halaman login, waktu proses login yang
  seragam (NIP tak bisa ditebak lewat selisih waktu), tipe komponen mengikuti master (bukan input klien), peringatan
  konfigurasi produksi di `sirga-doctor.sh`.
