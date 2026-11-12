# SIRGA ASN — Update UI/UX v3 ("Ledger")

Paket ini **hanya berisi file yang berubah**: `resources/` (seluruh frontend), 7 file PHP controller/helper, dan skrip `scripts/`.
Tidak menyentuh `.env`, `vendor/`, `node_modules/`, database, `routes/`, `config/`, atau `bootstrap/`.

## Pasang di Mac — satu baris (disarankan)

```bash
unzip -p ~/Downloads/sirga-ui-v3.zip sirga/scripts/sirga-update.sh | bash -s -- ~/Downloads/sirga-ui-v3.zip /Applications/MAMP/htdocs/sirga
```

Skrip ini: ① backup proyek ke Desktop → ② ekstrak ke folder sementara → ③ `rsync` (hanya menimpa, **tidak pernah menghapus**)
→ ④ `sirga-doctor.sh` memeriksa/memulihkan file starter yang hilang → ⑤ `npm run build` + `php artisan optimize:clear`.

> ⚠️ Jangan drag-and-drop folder lewat Finder lalu "Ganti/Replace" — Finder **menghapus** isi folder lama yang namanya sama.

## Atau manual (4 perintah)

```bash
cd /Applications/MAMP/htdocs/sirga
rm -rf /tmp/sirga-ui && mkdir -p /tmp/sirga-ui && unzip -oq ~/Downloads/sirga-ui-v3.zip -d /tmp/sirga-ui
rsync -av /tmp/sirga-ui/sirga/ ./
bash scripts/sirga-doctor.sh && npm run build && php artisan optimize:clear
```

## Kembali ke versi sebelumnya
```bash
tar -xzf ~/Desktop/sirga-backup-XXXXXXXX-XXXXXX.tar.gz -C /Applications/MAMP/htdocs/sirga && npm run build
```

## Yang berubah di v3

**Arah desain baru — "Ledger"**: datar berbasis garis & tipografi. Dibuang: gradient/lingkaran/pola titik dekoratif, ring progress,
kartu ikon, garis emas di tiap judul, bayangan tebal, emoji. Angka tabular jadi pusat perhatian; label kapital kecil ala buku besar.

**Sidebar bisa dilipat** — tombol di header (atau **⌘B / Ctrl+B**) mengubahnya jadi rail ikon (72px, tooltip nama menu).
Grup menu (Penggajian, Data & Informasi, Sistem) juga bisa diciutkan; pilihan tersimpan di browser.

**Potongan Sekolah** — "Jenis potongan periode ini" kini panel yang **bisa diciutkan** (default tertutup, ringkasan berupa chip nama+nominal).
Tabel "Nominal per ASN": **kolom Nama & tombol Ubah menempel** saat digeser, header menempel saat di-scroll, **paginasi dari server**
(10/15/25/50 per halaman), nominal Rp0 diredam. Total per jenis dihitung untuk seluruh periode.
Paginasi juga dipakai di **Penghasilan, Pembayaran, Data ASN**.

**Skeleton loading di mana-mana** — pindah halaman → kerangka halaman; ganti filter/halaman → baris tabel skeleton;
proses baca Excel → skeleton. (Muncul setelah 150 ms agar tidak berkedip.)

**Dashboard admin** — blok **"Perlu tindakan"** (dengan tombol langsung), strip 4 angka utama (ASN aktif, total diterimakan,
potongan lunas, kelengkapan data), grafik dengan **sumbu & label nilai** + tombol **Grafik/Tabel** untuk angka persis,
penagihan potongan, dan daftar menunggu verifikasi.

**Lebih mudah dipakai** — tombol **Ubah** selalu terlihat di Data ASN (aksi lain di menu "⋯"); halaman edit rincian punya
**bar Simpan yang menempel di bawah** (pratinjau diterimakan + alasan + Simpan); login tanpa dekorasi; menu aktif jelas.
