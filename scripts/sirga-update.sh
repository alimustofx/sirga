#!/usr/bin/env bash
# SIRGA Update — pasang paket update dengan AMAN (backup → rsync tanpa menghapus → doctor → build).
#
# Pakai (satu baris, dari mana saja):
#   unzip -p ~/Downloads/sirga-ui-v3.zip sirga/scripts/sirga-update.sh | bash -s -- ~/Downloads/sirga-ui-v3.zip /Applications/MAMP/htdocs/sirga
#
# Argumen:  $1 = path ZIP update   $2 = path proyek (default: folder saat ini)
# Opsi env: SKIP_BUILD=1 (lewati npm run build)
set -euo pipefail

ZIP="${1:-$HOME/Downloads/sirga-ui-v3.zip}"
PROJECT="${2:-$(pwd)}"

[ -f "$ZIP" ] || { echo "❌ ZIP tidak ditemukan: $ZIP"; exit 1; }
[ -f "$PROJECT/artisan" ] || { echo "❌ Bukan folder proyek Laravel (artisan tidak ada): $PROJECT"; exit 1; }
command -v rsync >/dev/null || { echo "❌ rsync tidak ditemukan"; exit 1; }

echo "1/5  Backup proyek ke Desktop…"
mkdir -p "$HOME/Desktop"
BACKUP="$HOME/Desktop/sirga-backup-$(date +%Y%m%d-%H%M%S).tar.gz"
tar -czf "$BACKUP" --exclude=node_modules --exclude=vendor --exclude=storage/logs --exclude=public/build -C "$PROJECT" .
echo "     ✅ $BACKUP"

echo "2/5  Ekstrak ZIP ke folder sementara…"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
unzip -oq "$ZIP" -d "$TMP"
[ -d "$TMP/sirga" ] || { echo "❌ Struktur ZIP tidak dikenali (folder sirga/ tidak ada)"; exit 1; }

echo "3/5  Menggabungkan (rsync: hanya menimpa file di ZIP, TIDAK menghapus apa pun)…"
rsync -a "$TMP/sirga/" "$PROJECT/"
echo "     ✅ $(find "$TMP/sirga" -type f | wc -l | tr -d ' ') file diperbarui"

echo "4/5  Pemeriksaan kesehatan proyek…"
( cd "$PROJECT" && bash scripts/sirga-doctor.sh ) || { echo "⚠️  Doctor melaporkan masalah (lihat di atas). Backup ada di: $BACKUP"; exit 1; }

echo "5/5  Build frontend & bersihkan cache…"
if [ "${SKIP_BUILD:-0}" = "1" ]; then
    echo "     (dilewati: SKIP_BUILD=1)"
else
    ( cd "$PROJECT" && npm run build )
fi
if [ -d "$PROJECT/vendor" ]; then ( cd "$PROJECT" && php artisan optimize:clear ) || true; fi

echo ""
echo "✅ Selesai. Muat ulang browser dengan ⌘ + Shift + R."
echo "   Kembali ke versi sebelumnya:  tar -xzf \"$BACKUP\" -C \"$PROJECT\""
