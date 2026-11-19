"""Copy the user-supplied embedded workbook verbatim."""
from pathlib import Path
import os
import shutil
import sys
import tempfile
import zipfile

TEMPLATE_NAME = "template.xlsx"
TEMPLATE_SHEETS = ("KHAI THUE", "tong hop hoa don", "chi tiet hoa don")
TEMPLATE_KHAI_START_ROW = 8
TEMPLATE_SOURCE_START_ROW = 2


def template_path():
    base = Path(getattr(sys, "_MEIPASS", Path(__file__).resolve().parent))
    return base / "assets" / TEMPLATE_NAME


def save_template(destination):
    destination = Path(destination).resolve()
    source = template_path().resolve()
    if destination.suffix.lower() != ".xlsx":
        raise ValueError("Template cần được lưu với đuôi .xlsx.")
    if destination == source:
        raise ValueError("Chọn nơi lưu khác tệp template bên trong ứng dụng.")
    if not source.is_file():
        raise FileNotFoundError("Không tìm thấy template đính kèm. Hãy dùng bản ứng dụng đầy đủ.")
    with zipfile.ZipFile(source) as archive:
        if archive.testzip() is not None:
            raise ValueError("Template đính kèm bị lỗi. Hãy dùng lại bản ứng dụng đầy đủ.")
    destination.parent.mkdir(parents=True, exist_ok=True)
    fd, temp = tempfile.mkstemp(prefix=".template_", suffix=".xlsx", dir=destination.parent)
    os.close(fd)
    try:
        shutil.copyfile(source, temp)
        os.replace(temp, destination)
    finally:
        if os.path.exists(temp):
            os.unlink(temp)
    return str(destination)
