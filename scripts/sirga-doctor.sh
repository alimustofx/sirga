#!/usr/bin/env bash
# SIRGA Doctor — memeriksa file bawaan starter kit yang dibutuhkan SIRGA dan MEMULIHKANNYA
# bila hilang (mis. akibat "Ganti" folder di Finder). Aman: tidak pernah menimpa file yang sudah ada.
#
# Pakai (dari folder proyek):   bash scripts/sirga-doctor.sh
set -u

ROOT="${1:-$(pwd)}"
cd "$ROOT" 2>/dev/null || { echo "❌ Folder tidak ditemukan: $ROOT"; exit 1; }
[ -f artisan ] || { echo "❌ Jalankan dari folder proyek Laravel (file 'artisan' tidak ditemukan)."; exit 1; }

STARTER_URL="https://github.com/laravel/vue-starter-kit.git"
SRC=""
MISSING=0
RESTORED=0
FAILED=0

say()  { printf '%s\n' "$*"; }
ok()   { say "  ✅ $*"; }
warn() { say "  ⚠️  $*"; }
bad()  { say "  ❌ $*"; }

fetch_starter() {
    [ -n "$SRC" ] && return 0
    local tmp; tmp="$(mktemp -d)"
    say "  ↳ mengunduh starter kit dari GitHub (sekali saja)…"
    if git clone --depth 1 --quiet "$STARTER_URL" "$tmp/vsk" 2>/dev/null; then
        SRC="$tmp/vsk"
        return 0
    fi
    bad "Gagal mengunduh starter kit (cek koneksi internet)."
    return 1
}

# restore <path-relatif>: salin dari starter hanya jika belum ada
restore() {
    local rel="$1"
    if [ -e "$rel" ]; then ok "$rel"; return 0; fi
    MISSING=$((MISSING + 1))
    warn "HILANG: $rel"
    fetch_starter || { FAILED=$((FAILED + 1)); return 1; }
    if [ -e "$SRC/$rel" ]; then
        mkdir -p "$(dirname "$rel")"
        rsync -a --ignore-existing "$SRC/$rel" "$(dirname "$rel")/" && { RESTORED=$((RESTORED + 1)); ok "dipulihkan: $rel"; return 0; }
    fi
    bad "tidak ada di starter kit: $rel"
    FAILED=$((FAILED + 1))
    return 1
}

say "🔎 SIRGA Doctor — $(pwd)"
say ""
say "1) File inti Laravel"
restore bootstrap/providers.php
restore routes/console.php
restore public/index.php
for f in app auth cache database filesystems logging mail queue services session; do restore "config/$f.php"; done

say ""
say "2) Migrasi bawaan starter (cache & jobs dipakai login/antrean)"
restore database/migrations/0001_01_01_000001_create_cache_table.php
restore database/migrations/0001_01_01_000002_create_jobs_table.php

say ""
say "3) File frontend bawaan starter yang dipakai SIRGA"
restore resources/js/lib/flashToast.ts
restore resources/js/lib/utils.ts
restore resources/js/components/ui/sonner

say ""
say "4) Folder tulis (cache & storage)"
mkdir -p bootstrap/cache storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rwX bootstrap/cache storage 2>/dev/null && ok "bootstrap/cache dan storage dapat ditulis"

say ""
say "5) Berkas aplikasi SIRGA (dari ZIP) sudah ada?"
APP_OK=1
for f in config/sirga.php app/Services/Payroll/SourceFormat.php routes/web.php resources/js/app.ts resources/js/layouts/AdminLayout.vue app/Http/Middleware/EnsureRole.php; do
    if [ -e "$f" ]; then ok "$f"; else bad "belum ada: $f  → salin ulang isi ZIP aplikasi dengan rsync"; APP_OK=0; fi
done
grep -q "sirga" routes/web.php 2>/dev/null || grep -q "SchoolAssetController" routes/web.php 2>/dev/null || { bad "routes/web.php masih milik starter kit (bukan SIRGA)"; APP_OK=0; }

say ""
say "6) Sisa file starter yang sebaiknya dihapus (hanya peringatan)"
STALE=0
for f in database/migrations/2024_01_01_000000_create_passkeys_table.php \
         database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php \
         routes/settings.php app/Actions resources/js/pages/Welcome.vue resources/js/pages/settings; do
    [ -e "$f" ] && { warn "sisa starter: $f"; STALE=1; }
done
[ -d sirga ] && { warn "ada folder bersarang ./sirga (salinan tak sengaja). Cek lalu hapus: rm -rf sirga"; STALE=1; }
[ "$STALE" -eq 0 ] && ok "tidak ada sisa starter yang mengganggu"

say ""
say "7) Dependensi"
[ -d vendor ] && ok "vendor/ ada" || warn "vendor/ belum ada → jalankan: composer install"
[ -d node_modules ] && ok "node_modules/ ada" || warn "node_modules/ belum ada → jalankan: npm install"
[ -f .env ] && ok ".env ada" || warn ".env belum ada → cp .env.example .env && php artisan key:generate"

say ""
say "────────────────────────────────────────"
say "Hilang: $MISSING | Dipulihkan: $RESTORED | Gagal: $FAILED"
if [ "$FAILED" -gt 0 ] || [ "$APP_OK" -eq 0 ]; then
    say "Masih ada yang perlu dibereskan (lihat ❌ di atas)."
    exit 1
fi
say "Proyek sehat ✔  Lanjutkan: composer dump-autoload && php artisan optimize:clear && npm run build"
