#!/usr/bin/env bash
# ============================================================================
#  TEX-GIANG RECRUITMENT — Chạy website ở máy local (Linux / macOS)
#
#  Cách dùng:
#    chmod +x run.sh     # lần đầu
#    ./run.sh            # chạy cổng 8000, tự mở trình duyệt
#    ./run.sh 8080       # cổng khác nếu 8000 bận
#    NO_BROWSER=1 ./run.sh
#    PHP_EXE=/usr/bin/php8.3 ./run.sh
# ============================================================================
set -euo pipefail
cd "$(dirname "$0")"

PORT="${1:-${PORT:-8000}}"
PHP_EXE="${PHP_EXE:-php}"

if ! command -v "$PHP_EXE" >/dev/null 2>&1; then
  echo "[LỖI] Không tìm thấy PHP ($PHP_EXE)."
  echo "      Cài PHP 8.3+ và extension pdo_mysql, mbstring, hoặc đặt PHP_EXE=/đường/dẫn/php."
  exit 1
fi

PHP_VERSION="$("$PHP_EXE" -r 'echo PHP_VERSION;')"
if ! "$PHP_EXE" -m | grep -qi 'pdo_mysql'; then
  echo "[CẢNH BÁO] PHP thiếu extension pdo_mysql — website sẽ lỗi kết nối database."
  echo "           Bật extension trong php.ini rồi chạy lại."
fi
if ! "$PHP_EXE" -m | grep -qi 'mbstring'; then
  echo "[CẢNH BÁO] PHP thiếu extension mbstring — tiếng Việt có thể bị lỗi."
fi

# Cổng đang bận?
if command -v ss >/dev/null 2>&1 && ss -ltn 2>/dev/null | grep -q ":$PORT "; then
  echo "[LỖI] Cổng $PORT đang bận. Chạy: ./run.sh 8080"
  exit 1
fi

echo ""
echo "  TEX-GIANG RECRUITMENT"
echo "  ---------------------------------------------"
echo "  PHP    : $PHP_EXE ($PHP_VERSION)"
echo "  Thư mục: $(pwd)"
echo "  Web    : http://127.0.0.1:$PORT"
echo "  Admin  : http://127.0.0.1:$PORT/admin/login"
echo "  ---------------------------------------------"
echo "  Nhấn Ctrl + C để dừng server."
echo ""

# Mở trình duyệt nếu có (bỏ qua khi NO_BROWSER=1)
if [ "${NO_BROWSER:-0}" != "1" ]; then
  if command -v open >/dev/null 2>&1; then
    ( sleep 1; open "http://127.0.0.1:$PORT/" >/dev/null 2>&1 || true ) &
  elif command -v xdg-open >/dev/null 2>&1; then
    ( sleep 1; xdg-open "http://127.0.0.1:$PORT/" >/dev/null 2>&1 || true ) &
  fi
fi

exec "$PHP_EXE" -S "127.0.0.1:$PORT" -t public
