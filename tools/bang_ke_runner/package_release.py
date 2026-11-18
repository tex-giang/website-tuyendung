"""Package only distributable code and a synthetic sample, never real invoices."""
from pathlib import Path
import hashlib
import shutil
import zipfile

ROOT = Path(__file__).resolve().parent
RELEASE = ROOT.parents[1] / "outputs" / "bang_ke_runner_v1"
RELEASE.mkdir(parents=True, exist_ok=True)
for name in ("HUONG_DAN.md", "KIEM_THU.md"):
    shutil.copyfile(ROOT / name, RELEASE / name)
shutil.copyfile(ROOT / "dist" / "BangKeRunner.exe", RELEASE / "BangKeRunner.exe")
exe_hash = hashlib.sha256((RELEASE / "BangKeRunner.exe").read_bytes()).hexdigest()
(RELEASE / "SHA256.txt").write_text(f"{exe_hash}  BangKeRunner.exe\n", encoding="ascii")
source_names = ["app.py", "engine.py", "ooxml.py", "test_engine.py", "requirements.txt", "requirements-dev.txt", "build.ps1", "package_release.py", "HUONG_DAN.md", "KIEM_THU.md", "reference/modKhaiThueTuDongIJKLv7.bas", "reference/source_info.json"]
archive = RELEASE / "BangKeRunner_v1_DayDu.zip"
with zipfile.ZipFile(archive, "w", zipfile.ZIP_DEFLATED, compresslevel=6) as z:
    for name in ("BangKeRunner.exe", "HUONG_DAN.md", "KIEM_THU.md", "SHA256.txt"):
        z.write(RELEASE / name, name)
    for name in source_names:
        z.write(ROOT / name, "MaNguon/" + name)
    for path in (ROOT / "vendor" / "tcl").rglob("*"):
        if path.is_file():
            z.write(path, "MaNguon/" + path.relative_to(ROOT).as_posix())
    sample = ROOT / "validation" / "packed_input.xlsx"
    if sample.is_file():
        z.write(sample, "Mau_Thu.xlsx")
with zipfile.ZipFile(archive) as z:
    assert z.testzip() is None
    assert not any("Bang_Ke_FINAL" in n or "KET_QUA_TEST" in n for n in z.namelist())
print(RELEASE)
print(f"EXE: {(RELEASE / 'BangKeRunner.exe').stat().st_size / 1048576:.1f} MB")
print(f"ZIP: {archive.stat().st_size / 1048576:.1f} MB")
print(f"SHA256: {exe_hash}")
