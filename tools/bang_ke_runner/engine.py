"""Port of ChayTatCaKhaiThue / CapNhatKhaiThueTuChiTiet from V71.

See reference/modKhaiThueTuDongIJKLv7.bas for the original VBA. Business
rules intentionally follow the VBA, including its conservative skip rules.
"""
from __future__ import annotations

from collections import Counter, defaultdict
import datetime as dt
import hashlib
from pathlib import Path
import re
import shutil
import tempfile
import unicodedata

from ooxml import Book, WorkbookError, FormulaError, Q, E, Cell, colname, xround
from pandas_index import InvoiceIndex

VERSION = "1.6.2"
FIELDS = ("model", "series", "number", "date", "vendor", "tax_id", "item", "unit", "qty", "price", "amount", "rate", "vat")
ALIASES = {
    "model": ["MAU SO"], "series": ["KY HIEU", "KY HIEU HOA DON"],
    "number": ["SO HOA DON", "SO HD"], "date": ["NGAY HOA DON"],
    "vendor": ["TEN NGUOI BAN", "NGUOI BAN"],
    "tax_id": ["MST NGUOI BAN", "MA SO THUE NGUOI BAN", "MA SO THUE"],
    "item": ["DIEN GIAI", "TEN HANG HOA DICH VU", "NOI DUNG"],
    "unit": ["DON VI TINH", "DVT"], "qty": ["SO LUONG", "SL"],
    "price": ["DON GIA", "GIA DON VI"], "goods": ["TONG TIEN HANG", "TIEN HANG"],
    "amount": ["DOANH SO BAN CHUA THUE", "DOANH SO CHUA THUE", "GIA TRI CHUA THUE"],
    "rate": ["THUE SUAT", "THUE SUAT GTGT"],
    "vat": ["THUE GTGT", "TIEN THUE GTGT", "TIEN THUE"],
}
K_ALIASES = {
    "number": ["SO HOA DON", "SO HD", "SO"],
    "date": ["NGAY HOA DON", "NGAY THANG NAM", "NGAY"],
    "vendor": ["TEN NGUOI BAN", "TEN NHA CUNG CAP", "NGUOI BAN"],
    "tax_id": ["MA SO THUE NGUOI BAN", "MST NGUOI BAN", "MA SO THUE", "MST"],
    "item": ["TEN HANG HOA DICH VU", "DIEN GIAI", "NOI DUNG"],
    "amount": ["GIA TRI HHDV MUA VAO CHUA CO THUE GTGT", "GIA TRI HHDV MUA VAO CHUA CO THE GTGT", "GIA TRI HANG HOA DICH VU CHUA THUE", "DOANH SO CHUA THUE", "GIA TRI CHUA THUE", "TIEN TRUOC THUE"],
    "vat": ["TIEN THUE GTGT", "THUE GTGT", "TIEN THUE"],
}


def error_value(v):
    return isinstance(v, str) and v in ("#REF!", "#DIV/0!", "#VALUE!", "#NAME?", "#N/A", "#NUM!", "#NULL!", "#SPILL!", "#CALC!")


def safe_text(v):
    if v is None or error_value(v):
        return ""
    if isinstance(v, float) and v.is_integer():
        return str(int(v))
    return str(v)


def blank(v):
    return not safe_text(v).strip()


def missing(v):
    return blank(v) or (isinstance(v, str) and v.strip().upper() == "1916")


def numeric(v):
    if v is None or isinstance(v, bool) or isinstance(v, (dt.date, dt.datetime)) or error_value(v):
        return False
    try:
        float(str(v).strip())
        return str(v).strip() != ""
    except (ValueError, TypeError):
        return False


def number(v):
    if numeric(v):
        return float(v)
    s = safe_text(v).strip().replace(",", "").replace("%", "")
    return float(s) if numeric(s) else 0.0


def loose(v):
    if missing(v):
        return ""
    return re.sub(" +", " ", safe_text(v).strip().upper().replace("\xa0", " "))


def compact(v):
    return re.sub(r"[ ._/-]", "", re.sub(" +", " ", safe_text(v).strip().upper().replace("\xa0", " ")))


def header(v):
    s = unicodedata.normalize("NFD", safe_text(v).upper().replace("Đ", "D"))
    s = "".join(ch for ch in s if unicodedata.category(ch) != "Mn")
    return re.sub(r"[^A-Z0-9]+", " ", s).strip()


def invoice(v):
    s = str(int(float(v))) if numeric(v) and float(v).is_integer() else safe_text(v).strip()
    return (s.lstrip("0") or "0") if s.isascii() and s.isdigit() else compact(s)


def taxid(v):
    s = compact(v)
    return (s.lstrip("0") or "0") if s.isascii() and s.isdigit() else s


def datekey(v):
    if blank(v):
        return ""
    if isinstance(v, (dt.datetime, dt.date)):
        return v.strftime("%Y%m%d")
    s = safe_text(v).strip()
    if len(s) == 8 and s.isdigit():
        try:
            return dt.datetime.strptime(s, "%Y%m%d").strftime("%Y%m%d")
        except ValueError:
            pass
    if numeric(v) and "/" not in s and "-" not in s and 1 <= float(v) <= 2958465:
        return (dt.datetime(1899, 12, 30) + dt.timedelta(days=int(float(v)))).strftime("%Y%m%d")
    parts = s.replace("-", "/").replace(".", "/").split("/")
    if len(parts) == 3:
        try:
            a, b, c = (int(p) for p in parts)
            y, m, d = (a, b, c) if len(parts[0].strip()) == 4 else (c, b, a)
            return dt.date(y, m, d).strftime("%Y%m%d")
        except (ValueError, OverflowError):
            pass
    # ISO date-time is accepted by Excel IsDate on common Windows locales.
    try:
        return dt.datetime.fromisoformat(s).strftime("%Y%m%d")
    except ValueError:
        return ""


def rate(v):
    if missing(v):
        return ""
    s = safe_text(v).strip().upper().replace(" ", "").replace("%", "")
    if numeric(s):
        n = float(s)
        if 0 < abs(n) <= 1:
            n *= 100
        return f"{n:.8f}".rstrip("0").rstrip(".") or "0"
    return s


def rate_output(v):
    if missing(v):
        return None
    s = rate(v)
    return float(s) if numeric(s) else s


def canon(v, places):
    if missing(v):
        return "<BLANK>"
    if not numeric(v):
        return "<TEXT>" + loose(v)
    return f"{xround(float(v), places):.{places}f}"


class Layout:
    def __init__(self, sheet, role):
        self.sheet, self.role, self.cols = sheet, role, {}
        self._index = None
        limit = 20 if role == "K" else 15
        bottom = 0
        aliases = {**ALIASES, **(K_ALIASES if role == "K" else {})}
        if role == "S":
            aliases["tax_id"] = ["MA SO THUE", "MST NGUOI BAN", "MA SO THUE NGUOI BAN"]
        header_cells = [(r, c, header(cell.value)) for r, row in sorted(sheet.rows.items()) if r <= limit for c, cell in sorted(row.cells.items()) if c <= 250 and cell.value is not None]
        for field, names in aliases.items():
            for name in names:
                found = next(((r, c) for r, c, text in header_cells if text == name), None)
                if found:
                    r, col = found
                    self.cols[field] = col
                    bottom = max(bottom, r)
                    break
            self.cols.setdefault(field, 0)
        if role == "D":
            found = next((r for r, c, text in header_cells if c == 23 and text in ("DIEN GIAI", "TEN HANG HOA DICH VU", "NOI DUNG")), 0)
            self.cols["item"] = 23 if found else 0
            bottom = max(bottom, found)
        if role == "K":
            for index, field in enumerate(FIELDS, 2):
                if not self.cols[field]:
                    found = next((c for r, c, text in header_cells if text == str(index)), 0)
                    self.cols[field] = found
            required = FIELDS
        elif role == "D":
            required = ("model", "series", "number", "date", "tax_id", "item", "unit", "qty", "price", "amount", "rate", "vat")
        else:
            required = ("model", "series", "number", "date", "tax_id", "amount", "rate", "vat")
        for f in required:
            if f == "vendor":
                continue
            if not self.cols.get(f):
                label = "Diễn giải (cột W)" if role == "D" and f == "item" else aliases[f][0]
                raise WorkbookError(f"Sheet '{sheet.name}' thiếu cột {label}.")
        if not self.cols["vendor"]:
            self.cols["vendor"] = self.cols["tax_id"] - (1 if role == "K" else 2)
        if not self.cols["goods"]:
            self.cols["goods"] = self.cols["amount"]
        self.first = next((r for r in sorted(sheet.rows) if r > bottom and safe_text(self.get(r, "number")).strip() and not safe_text(self.get(r, "number")).strip().startswith("(")), 0)
        if role == "S" and not self.first:
            self.first = bottom + 1
        columns = ("model", "series", "number", "tax_id") if role == "K" else ("number",)
        self.last = max(sheet.last(self.cols[c]) for c in columns)
        if not self.first or self.last < self.first:
            raise WorkbookError(f"Sheet '{sheet.name}' không có dòng hóa đơn.")

    def get(self, row, field):
        return self.sheet.get(row, self.cols.get(field, 0))

    def set(self, row, field, value):
        self.sheet.set(row, self.cols[field], value)

    def formula(self, row, field):
        return self.sheet.cell(row, self.cols[field]).formula is not None

    def parts(self, row, kind="full"):
        if self._index is not None and self._index.revision == self.sheet.revision and row in self._index.parts[kind]:
            return self._index.parts[kind][row]
        model, series = compact(self.get(row, "model")), compact(self.get(row, "series"))
        num, day, tid = invoice(self.get(row, "number")), datekey(self.get(row, "date")), taxid(self.get(row, "tax_id"))
        if kind == "core":
            return model, series, num, tid
        if kind == "fallback":
            return model, num, day, tid
        return series, num, day, tid

    def key(self, row, kind="full"):
        if self._index is not None and self._index.revision == self.sheet.revision and row in self._index.keys[kind]:
            return self._index.keys[kind][row]
        return "|".join(self.parts(row, kind))

    def valid(self, row, kind="full"):
        if self._index is not None and self._index.revision == self.sheet.revision and row in self._index.valid[kind]:
            return self._index.valid[kind][row]
        return all(self.parts(row, kind))

    def prepare_index(self):
        if self._index is None or self._index.revision != self.sheet.revision:
            self._index = InvoiceIndex(
                self, {"model": compact, "series": compact, "number": invoice, "date": datekey, "tax_id": taxid},
                number, missing,
            )
        return self._index

    def data_rows(self):
        return [r for r in sorted(self.sheet.rows) if self.first <= r <= self.last and not blank(self.get(r, "number"))]


def groups(layout, require_full=False):
    table = layout.prepare_index()
    return table.groups_valid if require_full else table.group_rows


def sum_field(layout, rows, field):
    if layout._index is not None and layout._index.revision == layout.sheet.revision:
        cached = layout._index.sum_for(rows, field)
        if cached is not None:
            return cached
    values = [layout.get(r, field) for r in rows]
    return sum(number(v) for v in values if not missing(v)), any(not missing(v) for v in values)


def unique(layout, rows, field, normalized=loose):
    values = [layout.get(r, field) for r in rows if not missing(layout.get(r, field))]
    if not values:
        return None
    return values[0] if len({normalized(v) for v in values}) == 1 else None


def select_amount(detail, rows, target, present=True):
    goods, hasgoods = sum_field(detail, rows, "goods")
    pretax, haspretax = sum_field(detail, rows, "amount")
    if hasgoods and haspretax:
        return ("goods", goods) if present and abs(target - goods) <= abs(target - pretax) else ("amount", pretax)
    if hasgoods:
        return "goods", goods
    if haspretax:
        return "amount", pretax
    return None, 0


def tolerance(count):
    return max(2.0, count * 0.6)


def assignment_reconciles(k, kr, d, drs):
    amount, vat = k.get(kr, "amount"), k.get(kr, "vat")
    col, total = select_amount(d, drs, number(amount), not blank(amount))
    tax, _ = sum_field(d, drs, "vat")
    return col is not None and (blank(amount) or abs(number(amount) - total) <= tolerance(len(drs))) and (blank(vat) or abs(number(vat) - tax) <= tolerance(len(drs)))


def groups_reconcile(k, krs, d, drs):
    amount, ha = sum_field(k, krs, "amount")
    vat, hv = sum_field(k, krs, "vat")
    n, _ = sum_field(d, drs, "goods")
    p, _ = sum_field(d, drs, "amount")
    tax, _ = sum_field(d, drs, "vat")
    return ha and hv and (abs(amount - n) <= 1 or abs(amount - p) <= 1) and abs(vat - tax) <= 1.1


def confirmed(d, drs, s, sgroups, key):
    srs = sgroups.get(key, [])
    if len(srs) != 1:
        return False
    da = [sum_field(d, drs, f) for f in ("goods", "amount")]
    sa = [sum_field(s, srs, f) for f in ("goods", "amount")]
    tax, ht = sum_field(d, drs, "vat")
    stax, hst = sum_field(s, srs, "vat")
    return any(hd and hs and abs(dv - sv) <= 1 for dv, hd in da for sv, hs in sa) and ht and hst and abs(tax - stax) <= 1.1


def signature(layout, row):
    return (loose(layout.get(row, "item")), loose(layout.get(row, "unit")), canon(layout.get(row, "qty"), 2), canon(layout.get(row, "price"), 0), rate(layout.get(row, "rate")))


def materialized(k, krs, d, drs):
    remaining = Counter(signature(d, r) for r in drs)
    count = 0
    for r in krs:
        sig = signature(k, r)
        if remaining[sig] > 0:
            remaining[sig] -= 1
            count += 1
    return count


def build_assignments(k, krs, d, drs):
    local = {r: [] for r in krs}
    if len(krs) == 1:
        local[krs[0]] = list(drs)
    else:
        rates = {}
        for r in krs:
            key = rate(k.get(r, "rate"))
            if key in rates:
                return None
            rates[key] = r
        for r in drs:
            key = rate(d.get(r, "rate"))
            if key in rates:
                target = rates[key]
            elif key in ("", "KCT") and all(missing(d.get(r, f)) or abs(number(d.get(r, f))) <= 1e-7 for f in ("goods", "amount", "vat")):
                target = krs[0]
            else:
                return None
            local[target].append(r)
    for r, assigned in local.items():
        if not assigned or blank(k.get(r, "amount")) or blank(k.get(r, "vat")) or not assignment_reconciles(k, r, d, assigned):
            return None
    return local


def allocate(values, target, present=True):
    rounded = [xround(v) for v in values]
    if present and abs(target - sum(values)) <= tolerance(len(values)):
        delta = xround(target) - sum(rounded)
        if abs(delta) >= 1e-7:
            index = next((i for i in range(len(values) - 1, -1, -1) if abs(values[i]) > 1e-7), len(values) - 1)
            rounded[index] += delta
    return rounded


def description_compatible(k, kr, d, dr):
    constraints = False
    for f, normalize in (("rate", rate), ("unit", loose), ("qty", lambda v: canon(v, 2)), ("price", lambda v: canon(v, 0))):
        v = k.get(kr, f)
        if not missing(v):
            other = d.get(dr, f)
            if missing(other) or normalize(v) != normalize(other):
                return False
            constraints = True
    amount = k.get(kr, "amount")
    if not missing(amount):
        if not any(not missing(d.get(dr, f)) and abs(number(amount) - number(d.get(dr, f))) <= 2 for f in ("goods", "amount")):
            return False
        constraints = True
    vat = k.get(kr, "vat")
    if not missing(vat):
        if missing(d.get(dr, "vat")) or abs(number(vat) - number(d.get(dr, "vat"))) > 2:
            return False
        constraints = True
    return constraints


class Runner:
    def __init__(self, book, input_name, progress=lambda *_: None):
        self.book, self.input_name, self.progress = book, input_name, progress
        self.stats = Counter()
        self.issues = []
        self.now = dt.datetime.now()
        self.run_id = self.now.strftime("%Y%m%d_%H%M%S")
        normalized = {header(n).replace(" ", ""): s for n, s in book.sheets.items()}
        def find(names):
            for name in names:
                if name in normalized:
                    return normalized[name]
            raise WorkbookError("Tệp cần có đủ 3 sheet: KHAI THUE, chi tiet hoa don, tong hop hoa don.")
        self.k = Layout(find(["KHAITHUE"]), "K")
        self.d = Layout(find(["CHITIETHOADON", "HOADONCHITIET"]), "D")
        self.s = Layout(find(["TONGHOPHOADON"]), "S")
        self.k.sheet.ensure_tree()
        protection = self.k.sheet.tree.find(Q + "sheetProtection")
        if protection is not None and protection.get("sheet", "1") not in ("0", "false"):
            raise WorkbookError("Sheet KHAI THUE đang được bảo vệ. Hãy bỏ bảo vệ trước khi chạy.")

    def issue(self, key, detail, reason, kcount, dcount):
        if len(self.issues) < 500:
            self.issues.append([key, detail, "BO_QUA", reason, kcount, dcount])

    def total(self, field):
        return sum(number(self.k.get(r, field)) for r in self.k.data_rows() if numeric(self.k.get(r, field)))

    def fill(self, row, field, value):
        if not missing(value) and blank(self.k.get(row, field)) and not self.k.formula(row, field):
            self.k.set(row, field, value)
            self.stats["cells_filled"] += 1
            return True
        return False

    def description(self, row, value):
        if not missing(value) and not self.k.formula(row, "item") and loose(self.k.get(row, "item")) != loose(value):
            self.k.set(row, "item", value)
            return True
        return False

    def run(self):
        k, d, s, st = self.k, self.d, self.s, self.stats
        kg, dg, sg = groups(k), groups(d), groups(s, True)
        amount_before, vat_before = self.total("amount"), self.total("vat")
        core = d.prepare_index().candidates("core")
        alternative = d.prepare_index().candidates("fallback")
        self.progress("Đang đối chiếu hóa đơn…", 35)
        resolved, owners = {}, {}
        for key, krs in kg.items():
            can_exact = key in dg and k.valid(krs[0]) and d.valid(dg[key][0])
            if can_exact:
                if key not in owners:
                    resolved[key] = key
                    owners[key] = key
                    st["exact_matches"] += 1
                else:
                    st["ambiguous_groups"] += 1
                continue
            for kind, index in (("core", core), ("fallback", alternative)):
                if key in resolved or not k.valid(krs[0], kind):
                    continue
                candidates = index.get(k.key(krs[0], kind), [])
                if len(candidates) != 1:
                    continue
                candidate = candidates[0]
                drs = dg[candidate]
                kd, dd = datekey(k.get(krs[0], "date")), datekey(d.get(drs[0], "date"))
                if kind == "core" and kd and dd and kd[:4] != dd[:4]:
                    continue
                if groups_reconcile(k, krs, d, drs):
                    if candidate not in kg and candidate not in owners:
                        resolved[key] = candidate
                        owners[candidate] = key
                        st["fallback_matches"] += 1
                    else:
                        st["ambiguous_groups"] += 1
        st["unmatched_groups"] = len(kg) - len(resolved)
        for key, krs in kg.items():
            if key not in resolved:
                self.issue(key, "", "KHONG_CO_UNG_VIEN_DUY_NHAT_HOAC_TONG_KHONG_KHOP", len(krs), 0)
        assignments, singles, blocked = {}, {}, set()
        for key, dk in resolved.items():
            krs, drs = kg[key], dg[dk]
            reason = None
            if len(drs) == len(krs) == 1 and key == dk:
                singles[krs[0]] = drs[0]
                continue
            if not confirmed(d, drs, s, sg, dk):
                reason = "CHI_TIET_VA_TONG_HOP_KHONG_KHOP_HOAC_KHONG_DUY_NHAT"
            elif len(drs) == len(krs) == 1:
                if assignment_reconciles(k, krs[0], d, drs):
                    singles[krs[0]] = drs[0]
                else:
                    reason = "M_O_KHONG_KHOP_CHO_NHOM_1_1"
            else:
                count = materialized(k, krs, d, drs)
                if len(krs) == len(drs):
                    if count == len(drs) and groups_reconcile(k, krs, d, drs):
                        st["already_expanded"] += 1
                    else:
                        reason = "SO_DONG_BANG_NHAU_NHUNG_NOI_DUNG_HOAC_TONG_KHONG_KHOP"
                elif len(krs) > len(drs):
                    reason = "KHAI_THUE_CO_DONG_DU_HOAC_TRUNG"
                elif count > 0:
                    reason = "NHOM_DA_BUNG_DO_KHONG_APPEND_LAI"
                else:
                    plan = build_assignments(k, krs, d, drs)
                    if plan:
                        assignments.update(plan)
                        st["expanded_groups"] += 1
                    else:
                        reason = "KHONG_CHIA_DUOC_CHI_TIET_THEO_THUE_SUAT_VA_TONG"
            if reason:
                blocked.add(key)
                st["ambiguous_groups"] += 1
                self.issue(key, dk, reason, len(krs), len(drs))
        self.progress("Đang điền dữ liệu và tách chi tiết…", 50)
        # FillBlankIjklCertain: one destination row and one selected source row.
        for key, krs in kg.items():
            if key in blocked:
                st["BLOCKED"] += 1
                continue
            if len(krs) != 1:
                st["IJKL_AMBIGUOUS"] += 1
                continue
            dk = resolved.get(key, "")
            drs = dg.get(dk, [])
            if len(drs) > 1:
                st["IJKL_AMBIGUOUS"] += 1
                continue
            sk = dk if dk in sg else key
            srs = sg.get(sk, [])
            summary_allowed = k.valid(krs[0]) and len(srs) == 1 and s.valid(srs[0])
            if k.valid(krs[0]) and srs and not summary_allowed:
                st["IJKL_AMBIGUOUS"] += 1
            for field, decimals in (("item", -1), ("unit", -1), ("qty", 2), ("price", 0)):
                value, source = None, ""
                if len(drs) == 1 and d.cols[field]:
                    value, source = d.get(drs[0], field), "DETAIL"
                if missing(value) and summary_allowed and s.cols[field]:
                    value, source = s.get(srs[0], field), "SUMMARY"
                if not missing(value):
                    if decimals >= 0 and numeric(value):
                        value = xround(float(value), decimals)
                    if self.fill(krs[0], field, value):
                        st[field.upper() + "_" + source] += 1
        summary_items = {}
        for key, dk in resolved.items():
            if key in blocked:
                continue
            krs, drs = kg[key], dg[dk]
            summary_item = unique(s, sg.get(dk, []), "item")
            detail_item = unique(d, drs, "item")
            for kr in krs:
                value = detail_item
                if missing(value):
                    if len(drs) == 1:
                        value = d.get(drs[0], "item")
                    else:
                        compatible = [dr for dr in drs if description_compatible(k, kr, d, dr)]
                        if len(compatible) == 1:
                            value = d.get(compatible[0], "item")
                if missing(value) and len(krs) == 1:
                    value = summary_item
                self.description(kr, value)
                if not missing(summary_item):
                    summary_items[kr] = summary_item
        for key, krs in kg.items():
            srs = sg.get(key, [])
            if key not in resolved and len(krs) == 1 and k.valid(krs[0]) and len(srs) == 1 and s.valid(srs[0]):
                self.description(krs[0], unique(s, srs, "item"))
        # FillHeaderBlanksCertain preserves formulas and requires consistent sources.
        normalizers = {"model": compact, "series": compact, "number": invoice, "date": datekey, "vendor": loose, "tax_id": taxid}
        for key, dk in resolved.items():
            if key not in blocked:
                for field, normalize in normalizers.items():
                    value = unique(d, dg[dk], field, normalize)
                    for r in kg[key]:
                        self.fill(r, field, value)
        expected_amount = expected_vat = 0.0
        for row in sorted(set(assignments) | set(singles), reverse=True):
            if row in assignments:
                drs = assignments[row]
                amount, vat = k.get(row, "amount"), k.get(row, "vat")
                column, _ = select_amount(d, drs, number(amount), not blank(amount))
                amounts = allocate([number(d.get(r, column)) if not missing(d.get(r, column)) else 0 for r in drs], number(amount), not blank(amount))
                taxes = allocate([number(d.get(r, "vat")) if not missing(d.get(r, "vat")) else 0 for r in drs], number(vat), not blank(vat))
                k.sheet.insert_after(row, len(drs) - 1)
                st["added_rows"] += len(drs) - 1
                for offset, dr in enumerate(drs):
                    out = row + offset
                    item = d.get(dr, "item")
                    if missing(item):
                        item = summary_items.get(row)
                    self.description(out, item)
                    for field in ("unit", "qty", "price"):
                        value = d.get(dr, field)
                        if missing(value):
                            if offset > 0:
                                k.set(out, field, None)
                        else:
                            k.set(out, field, value)
                    k.set(out, "amount", amounts[offset])
                    value = d.get(dr, "rate")
                    if not missing(value):
                        k.set(out, "rate", rate_output(value))
                    elif offset > 0:
                        k.set(out, "rate", None)
                    k.set(out, "vat", taxes[offset])
            else:
                dr = singles[row]
                amount_was_blank, vat_was_blank = blank(k.get(row, "amount")), blank(k.get(row, "vat"))
                old_filled = st["cells_filled"]
                raw_n, raw_p = d.get(dr, "goods"), d.get(dr, "amount")
                target = k.get(row, "amount")
                if not missing(raw_n) and not missing(raw_p) and not blank(target) and abs(number(target) - number(raw_n)) <= abs(number(target) - number(raw_p)):
                    selected = raw_n
                else:
                    selected = raw_p if not missing(raw_p) else raw_n
                item = d.get(dr, "item")
                if missing(item):
                    item = summary_items.get(row)
                if self.description(row, item):
                    st["cells_filled"] += 1
                values = {f: d.get(dr, f) for f in ("unit", "qty", "price")}
                values["amount"] = xround(number(selected)) if not missing(selected) else None
                values["rate"] = rate_output(d.get(dr, "rate"))
                raw_vat = d.get(dr, "vat")
                values["vat"] = xround(number(raw_vat)) if not missing(raw_vat) else None
                for field, value in values.items():
                    self.fill(row, field, value)
                if amount_was_blank and not blank(k.get(row, "amount")):
                    expected_amount += number(k.get(row, "amount"))
                if vat_was_blank and not blank(k.get(row, "vat")):
                    expected_vat += number(k.get(row, "vat"))
                if st["cells_filled"] > old_filled:
                    st["single_rows_filled"] += 1
        k.last = max(k.sheet.last(k.cols[c]) for c in ("model", "series", "number", "tax_id"))
        self.progress("Đang làm tròn và kiểm tra tổng tiền…", 70)
        for r in k.data_rows():
            for field, decimals, stat in (("qty", 2, "quantity_rounded"), ("price", 0, "price_rounded")):
                value = k.get(r, field)
                if not k.formula(r, field) and not blank(value) and numeric(value):
                    rounded = xround(float(value), decimals)
                    if isinstance(value, str) or abs(float(value) - rounded) > 1e-9:
                        k.set(r, field, rounded)
                        st[stat] += 1
        # Set K/L formats for the complete data range, including rows with no number.
        for r in range(k.first, k.last + 1):
            for field, fmt in (("qty", 4), ("price", 3)):
                c = k.sheet.cell(r, k.cols[field], True)
                c.style = self.book.number_style(c.style, fmt)
        self.extend_validation()
        for field in ("amount", "vat"):
            r = self.total_formula_row(field)
            if r:
                formula = k.sheet.cell(r, k.cols[field]).formula.upper().replace(" ", "").replace("$", "")
                letter = colname(k.cols[field])
                match = re.fullmatch(r"SUM\(" + letter + r"(\d+):" + letter + r"(\d+)\)", formula)
                if match and int(match[1]) == k.first and int(match[2]) >= k.first:
                    k.sheet.set_formula(r, k.cols[field], f"SUM({letter}{k.first}:{letter}{k.last})")
        # wsK.Calculate in VBA: calculate every formula in KHAI THUE explicitly.
        for row in k.sheet.rows.values():
            for c in row.cells.values():
                if c.formula:
                    c.dirty = True
        for r, row in k.sheet.rows.items():
            for col, c in row.cells.items():
                if c.formula:
                    self.book.evaluate(k.sheet, r, col)
        amount_after, vat_after = self.total("amount"), self.total("vat")
        for field, before, after, expected in (("amount", amount_before, amount_after, expected_amount), ("vat", vat_before, vat_after, expected_vat)):
            r = self.total_formula_row(field)
            if r:
                value = k.get(r, field)
                if not numeric(value) or abs(number(value) - after) > 0.5:
                    raise WorkbookError(f"Công thức tổng {colname(k.cols[field])}{r} không khớp tổng dòng hóa đơn.")
            if abs((after - before) - expected) > 0.5:
                raise WorkbookError(f"Tổng {'trước thuế' if field == 'amount' else 'thuế'} thay đổi ngoài dự kiến: {before:,.0f} → {after:,.0f}. Không xuất kết quả.")
            if abs(before) >= 1e-7:
                for r in range(k.last + 1, min(k.last + 40, 1048576) + 1):
                    value = k.get(r, field)
                    if not k.formula(r, field) and numeric(value) and abs(float(value) - before) <= 0.5:
                        k.set(r, field, after)
        data_rows = k.data_rows()
        for r in data_rows:
            for field, decimals, stat in (("qty", 2, "quantity_issues"), ("price", 0, "price_issues")):
                value = k.get(r, field)
                if not blank(value) and (isinstance(value, str) or not numeric(value) or abs(float(value) - xround(float(value), decimals)) > 1e-9):
                    st[stat] += 1
            for col in range(k.cols["item"], k.cols["vat"] + 1):
                value = k.sheet.get(r, col)
                if isinstance(value, str) and value.strip() == "1916":
                    st["placeholder_count"] += 1
        st["data_rows"] = len(data_rows)
        st["formula_errors"] = self.book.formula_errors
        st["total_amount"], st["total_vat"] = amount_after, vat_after
        status = "HOAN_TAT_CO_CANH_BAO" if any(st[f] for f in ("quantity_issues", "price_issues", "formula_errors", "placeholder_count", "ambiguous_groups", "unmatched_groups")) else "THANH_CONG"
        self.write_logs(status)
        return {"version": VERSION, "run_id": self.run_id, "status": status, "input": self.input_name, "stats": dict(st), "logged_issues": len(self.issues), "issues_truncated": len(self.issues) >= 500, "amount_before": amount_before, "vat_before": vat_before, "expected_amount_increase": expected_amount, "expected_vat_increase": expected_vat}

    def total_formula_row(self, field):
        return next((r for r in range(self.k.last + 1, min(self.k.last + 30, 1048576) + 1) if self.k.formula(r, field)), 0)

    def extend_validation(self):
        k = self.k
        validations = k.sheet.tree.find(Q + "dataValidations")
        if validations is None:
            return
        col = colname(k.cols["rate"])
        from ooxml import colnum
        for dv in validations:
            for token in dv.get("sqref", "").split():
                endpoints = token.split(":")
                a = re.fullmatch(r"([A-Z]+)(\d+)", endpoints[0])
                b = re.fullmatch(r"([A-Z]+)(\d+)", endpoints[-1])
                if a and b and colnum(a[1]) <= k.cols["rate"] <= colnum(b[1]) and int(a[2]) <= k.first <= int(b[2]):
                    # Keep validation for unrelated columns and add the full target range.
                    target = f"{col}{k.first}:{col}{k.last}"
                    if target not in dv.get("sqref", "").split():
                        dv.set("sqref", dv.get("sqref", "") + " " + target)
                    return

    def write_logs(self, status):
        st = self.stats
        headers = ["Thoi_gian", "Workbook", "Hanh_dong", "Trang_thai", "Dong_du_lieu", "Khop_day_du", "Khop_fallback", "Hoa_don_bung", "Dong_them", "Dong_dien_o_trong", "O_da_dien", "SL_da_lam_tron", "Don_gia_da_lam_tron", "Nhom_khong_khop", "Nhom_mo_ho", "Loi_K", "Loi_L", "Loi_cong_thuc", "Placeholder_1916", "Tong_truoc_thue", "Tong_thue", "Duong_dan_backup"]
        timestamp = (self.now - dt.datetime(1899, 12, 30)).total_seconds() / 86400
        values = [timestamp, self.input_name, f"CHAY_TAT_CA|{self.run_id}|IJKL_MO_HO={st['IJKL_AMBIGUOUS']}", status] + [st[f] for f in ("data_rows", "exact_matches", "fallback_matches", "expanded_groups", "added_rows", "single_rows_filled", "cells_filled", "quantity_rounded", "price_rounded", "unmatched_groups", "ambiguous_groups", "quantity_issues", "price_issues", "formula_errors", "placeholder_count", "total_amount", "total_vat")] + ["Tệp đầu vào được giữ nguyên; kết quả ghi ra tệp riêng."]
        self.append_log("NHAT_KY_VBA", headers, [values], 32)
        if self.issues:
            self.append_log("NHAT_KY_DOI_CHIEU", ["Thoi_gian", "Run_ID", "Khoa_KHAI", "Khoa_CHI_TIET", "Hanh_dong", "Ly_do", "So_dong_KHAI", "So_dong_CHI_TIET"], [[timestamp, self.run_id] + v for v in self.issues], 42)

    def append_log(self, name, headers, values, width):
        sheet = self.book.add_sheet(name)
        sheet.ensure_tree()
        if blank(sheet.get(1, 1)):
            for i, value in enumerate(headers, 1):
                sheet.set(1, i, value)
                sheet.cell(1, i).style = self.book.header_style(name == "NHAT_KY_DOI_CHIEU")
        row = max(2, sheet.last(1) + 1)
        for record in values:
            for i, value in enumerate(record, 1):
                sheet.set(row, i, value)
            sheet.cell(row, 1).style = self.book.number_style(0, "yyyy-mm-dd hh:mm:ss")
            if name == "NHAT_KY_VBA":
                for col in (20, 21):
                    sheet.cell(row, col).style = self.book.number_style(0, 3)
            row += 1
        cols = sheet.tree.find(Q + "cols")
        if cols is None:
            cols = E.Element(Q + "cols")
            data = sheet.tree.find(Q + "sheetData")
            sheet.tree.insert(list(sheet.tree).index(data), cols)
        else:
            for child in list(cols):
                cols.remove(child)
        for i, title in enumerate(headers, 1):
            E.SubElement(cols, Q + "col", min=str(i), max=str(i), width=str(min(width, max(15, len(title) + 3))), customWidth="1")
        filt = sheet.tree.find(Q + "autoFilter")
        if filt is None:
            filt = E.SubElement(sheet.tree, Q + "autoFilter")
        filt.set("ref", f"A1:{colname(len(headers))}{row-1}")


def process_file(source, output, progress=lambda *_: None, *, only_khai=True):
    source, output = Path(source).resolve(), Path(output).resolve()
    if source.suffix.lower() not in (".xlsx", ".xlsm"):
        raise WorkbookError("Chỉ hỗ trợ tệp .xlsx và .xlsm. Hãy chuyển .xls/.xlsb sang .xlsx trước.")
    if source == output:
        raise WorkbookError("Chọn tên tệp kết quả khác tệp đầu vào.")
    if only_khai and output.suffix.lower() != ".xlsx":
        raise WorkbookError("Chọn tệp kết quả .xlsx; kết quả chỉ có một sheet KHAI THUE.")
    if not only_khai and output.suffix.lower() != source.suffix.lower():
        raise WorkbookError(f"Giữ đuôi {source.suffix} cho tệp kết quả để bảo toàn nội dung gốc.")
    if not source.is_file():
        raise WorkbookError("Không tìm thấy tệp đầu vào.")
    output.parent.mkdir(parents=True, exist_ok=True)
    progress("Đang tạo bản đọc tạm…", 2)
    with tempfile.TemporaryDirectory(prefix="bang_ke_") as temp:
        snapshot = Path(temp) / source.name
        before = source.stat()
        shutil.copyfile(source, snapshot)
        after = source.stat()
        if (before.st_size, before.st_mtime_ns) != (after.st_size, after.st_mtime_ns):
            raise WorkbookError("Tệp đầu vào vừa được thay đổi. Lưu tệp rồi chạy lại.")
        book = Book(snapshot, progress)
        try:
            runner = Runner(book, source.name, progress)
            result = runner.run()
            issue_fields = ("khai_key", "detail_key", "action", "reason", "khai_rows", "detail_rows")
            result["issues"] = [dict(zip(issue_fields, row)) for row in runner.issues]
            progress("Đang lưu và kiểm tra tệp kết quả…", 85)
            if only_khai:
                from single_sheet import save_khai_only
                result["export_notes"] = save_khai_only(book, runner.k.sheet, output)
                result["output_sheets"] = [runner.k.sheet.name]
            else:
                book.save(output)
                result["output_sheets"] = list(book.sheets)
        finally:
            book.close()
    result["output"] = str(output)
    progress("Đã hoàn tất.", 100)
    return result
