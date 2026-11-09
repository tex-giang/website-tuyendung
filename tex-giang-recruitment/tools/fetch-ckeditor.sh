#!/usr/bin/env bash
# Tải CKEditor 4.22.1 vào public/assets/vendor/ckeditor4
# (thư mục này KHÔNG nằm trong git để repo nhẹ — chạy script này sau khi clone)
#
# Cách dùng (Linux/macOS/cPanel SSH, tại thư mục gốc project):
#   bash tools/fetch-ckeditor.sh

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST="$PROJECT_ROOT/public/assets/vendor/ckeditor4"
TARBALL="$(mktemp /tmp/ckeditor4-XXXXXX.tgz)"
URL="https://registry.npmjs.org/ckeditor4/-/ckeditor4-4.22.1.tgz"

echo "→ Tải CKEditor 4.22.1 từ npm..."
curl -fsSL "$URL" -o "$TARBALL"

if [ -d "$DEST" ]; then
  echo "→ Xoá thư mục cũ: $DEST"
  rm -rf "$DEST"
fi
mkdir -p "$DEST"

echo "→ Giải nén..."
tar -xzf "$TARBALL" -C "$DEST" --strip-components=1

if [ ! -f "$DEST/ckeditor.js" ]; then
  echo "✗ Giải nén thất bại: không thấy ckeditor.js trong $DEST" >&2
  exit 1
fi

rm -f "$TARBALL"
echo "✓ CKEditor 4.22.1 đã sẵn sàng tại public/assets/vendor/ckeditor4"
