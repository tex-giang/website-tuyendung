"""Package the standalone EXE with the exact user-supplied template.xlsx."""
from pathlib import Path
import hashlib
import shutil
import zipfile

ROOT = Path(__file__).resolve().parent
RELEASE = ROOT.parents[1] / "outputs" / "bang_ke_runner_v1_6_2_template"
RELEASE.mkdir(parents=True, exist_ok=True)
for name in ("HUONG_DAN.md", "KIEM_THU.md", "HIEU_NANG.md"):
    shutil.copyfile(ROOT / name, RELEASE / name)
shutil.copyfile(ROOT / "dist" / "BangKeRunner.exe", RELEASE / "BangKeRunner.exe")
exe_hash = hashlib.sha256((RELEASE / "BangKeRunner.exe").read_bytes()).hexdigest()
(RELEASE / "SHA256.txt").write_text(f"{exe_hash}  BangKeRunner.exe\n", encoding="ascii")
source_names = ["app.py", "desktop_ui.py", "test_desktop_ui.py", "engine.py", "ooxml.py", "single_sheet.py", "pandas_index.py", "templates.py", "test_engine.py", "test_optimization.py", "test_single_sheet.py", "test_templates.py", "benchmark.py", "requirements.txt", "requirements-dev.txt", "build.ps1", "package_release.py", "HUONG_DAN.md", "KIEM_THU.md", "HIEU_NANG.md", "assets/template.xlsx", "assets/template_info.json", "assets/app.ico", "reference/modKhaiThueTuDongIJKLv7.bas", "reference/source_info.json"]
archive = RELEASE / "BangKeRunner_DayDu.zip"
with zipfile.ZipFile(archive, "w", zipfile.ZIP_DEFLATED, compresslevel=6) as z:
    for name in ("BangKeRunner.exe", "HUONG_DAN.md", "KIEM_THU.md", "HIEU_NANG.md", "SHA256.txt"):
        z.write(RELEASE / name, name)
    for name in source_names:
        z.write(ROOT / name, "MaNguon/" + name)
with zipfile.ZipFile(archive) as z:
    assert z.testzip() is None
    assert not any("Bang_Ke_FINAL" in n or "KET_QUA_TEST" in n for n in z.namelist())
    assert not any('/' not in name and name.lower().endswith('.xlsx') for name in z.namelist())
print(RELEASE)
print(f"EXE: {(RELEASE / 'BangKeRunner.exe').stat().st_size / 1048576:.1f} MB")
print(f"ZIP: {archive.stat().st_size / 1048576:.1f} MB")
print(f"SHA256: {exe_hash}")
