"""Bang Ke Runner — local Windows GUI and command-line entry point."""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import sys

from engine import VERSION, process_file
from templates import save_template, TEMPLATE_SHEETS


def main():
    parser = argparse.ArgumentParser(description="Chạy bảng kê V71, không cần Excel.")
    parser.add_argument("--input", help="Tệp .xlsx/.xlsm gồm đủ 3 sheet")
    parser.add_argument("--output", help="Tệp .xlsx chỉ gồm sheet KHAI THUE")
    parser.add_argument("--report", help="Ghi báo cáo JSON để tự động hóa/kiểm tra")
    parser.add_argument("--save-template", metavar="FILE.xlsx", help="Lưu mẫu trống được nhúng trong ứng dụng")
    parser.add_argument("--ui-preview", help=argparse.SUPPRESS)
    args = parser.parse_args()
    if args.save_template and (args.input or args.output):
        parser.error("--save-template dùng riêng, không kèm --input/--output")
    if args.save_template:
        try:
            result = {"status": "THANH_CONG", "template": save_template(args.save_template), "sheets": list(TEMPLATE_SHEETS)}
            code = 0
        except Exception as exc:
            result = {"status": "LOI", "error": str(exc)}
            code = 1
    elif args.input:
        if not args.output:
            parser.error("--input cần có --output")
        try:
            result = process_file(args.input, args.output)
            code = 0
        except Exception as exc:
            result = {"status": "LOI", "error": str(exc)}
            code = 1
    else:
        return run_gui(args.ui_preview)
    if args.report:
        Path(args.report).write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")
    elif sys.stdout is not None:
        print(json.dumps(result, ensure_ascii=False, indent=2))
    return code


def run_gui(preview=None):
    from desktop_ui import run_gui as desktop_main
    return desktop_main(preview)


if __name__ == "__main__":
    raise SystemExit(main())
