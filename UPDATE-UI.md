# SIRGA ASN — Update v5: berita transfer singkat

Hanya 10 file berubah. **Tidak perlu migrasi.** Data pembayaran lama tidak terpengaruh
(berita transfer dihitung otomatis saat ditampilkan, tidak disimpan).

## Pasang di Mac — satu baris

```bash
unzip -p ~/Downloads/sirga-ui-v5.zip sirga/scripts/sirga-update.sh | bash -s -- ~/Downloads/sirga-ui-v5.zip /Applications/MAMP/htdocs/sirga
```

(backup otomatis ke Desktop → rsync tanpa menghapus → sirga-doctor → build). Lalu muat ulang browser: **⌘ + Shift + R**.

## Format berita transfer baru

`POT <NAMA> <BLNTH>` — maksimal **18 karakter**, hanya huruf/angka/spasi.

| Nama di sistem | Berita transfer |
|---|---|
| Dra. Fiva Tri Wahyuni, M.Pd. | `POT FIVA NOV26` |
| Drs. Muhammad Abdurrahman Al-Faruqi, M.M. | `POT ABDURRAH NOV26` |
| Hj. Siti Nurhaliza Kusumawardhani, S.Pd., M.Si. | `POT SITI NOV26` |

Aturan: gelar dibuang; awalan umum (Muhammad, Ni, Luh, Putu…) dilewati bila ada nama berikutnya; nama dipotong
agar total ≤ 18 karakter. Tampil di halaman Pembayaran guru (besar + tombol salin + penghitung karakter), halaman
verifikasi admin, dan slip gaji PDF. Aturan ada di satu tempat: `app/Support/TransferNote.php`.
