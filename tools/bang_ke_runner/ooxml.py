"""Sparse OOXML reader/editor. Preserves unmodified ZIP members, including VBA.

Only lxml is required at runtime. No Office, COM, network, or macro execution.
"""
from __future__ import annotations

import ast
import bisect
import copy
import datetime as dt
import math
import io
import operator
import os
from pathlib import Path
import re
import tempfile
import zipfile
from dataclasses import dataclass, field
from decimal import Decimal, ROUND_HALF_UP

from lxml import etree as E

NS = "http://schemas.openxmlformats.org/spreadsheetml/2006/main"
REL = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"
PKG = "http://schemas.openxmlformats.org/package/2006/relationships"
Q = "{" + NS + "}"
XML = "{http://www.w3.org/XML/1998/namespace}"
PARSER = E.XMLParser(resolve_entities=False, no_network=True, huge_tree=True)
VALUE_CELLS = E.XPath("./m:c[m:v or m:is or m:f]", namespaces={"m": NS})
EMPTY_XML_CELLS = re.compile(rb"<(?P<p>(?:[A-Za-z_][\w.-]*:)?)c(?=[\s/>])[^<>]*?(?:/>|>\s*</(?P=p)c\s*>)")
ROW_REFERENCE_TAGS = tuple(Q + name for name in ("mergeCell", "conditionalFormatting", "dataValidation", "hyperlink", "autoFilter", "selection", "pane"))


class WorkbookError(Exception):
    pass


class FormulaError(WorkbookError):
    pass


def colnum(s):
    n = 0
    for ch in s.upper():
        n = n * 26 + ord(ch) - 64
    return n


def colname(n):
    s = ""
    while n:
        n, r = divmod(n - 1, 26)
        s = chr(65 + r) + s
    return s


def xround(value, places=0):
    return float(Decimal(str(float(value))).quantize(Decimal(1).scaleb(-places), rounding=ROUND_HALF_UP))


@dataclass(slots=True)
class Cell:
    value: object = None
    formula: str | None = None
    kind: str | None = None
    style: int = 0
    element: object = None
    dirty: bool = False


@dataclass
class Row:
    cells: dict = field(default_factory=dict)
    element: object = None


def text_value(e):
    # Exclude phonetic annotations, whose text is not part of the cell value.
    return "".join(e.xpath("./m:t/text() | ./m:r/m:t/text()", namespaces={"m": NS}))


def parse_cell(e, strings, keep=False):
    f, v = e.find(Q + "f"), e.find(Q + "v")
    inline = e.find(Q + "is")
    t = e.get("t")
    raw = v.text if v is not None else None
    value = None
    if t == "s" and raw is not None:
        value = strings[int(raw)]
    elif t == "inlineStr" and inline is not None:
        value = text_value(inline)
    elif t in ("str", "e", "d"):
        value = raw
    elif t == "b":
        value = raw == "1"
    elif raw not in (None, ""):
        try:
            value = float(raw)
            if value.is_integer():
                value = int(value)
        except ValueError:
            value = raw
    return Cell(value, f.text if f is not None else None, t, int(e.get("s", "0")), e if keep else None)


# Matches references outside Excel string literals; row/column anchors are kept.
REFERENCE = re.compile(r"(?<![A-Za-z0-9_\[])((?:'(?:[^']|'')+'|[A-Za-z_][\w .]*)!)?(\$?[A-Z]{1,3})(\$?)([1-9][0-9]*)(?::((?:'(?:[^']|'')+'|[A-Za-z_][\w .]*)!)?(\$?[A-Z]{1,3})(\$?)([1-9][0-9]*))?(?![\w(])")


def rewrite_formula(formula, owner, target, anchor, amount, relative=False):
    parts = re.split(r'("(?:[^"]|"")*")', formula)
    def replace(m):
        sheet, column, absolute, number, end_sheet, end_column, end_absolute, end_number = m.groups()
        def endpoint(qualifier, col, absolute, row, inherited=None):
            effective = qualifier or inherited
            sheetname = effective[:-1].strip("'").replace("''", "'") if effective else owner
            if relative:
                move = amount if not absolute else 0
            else:
                move = amount if sheetname.casefold() == target.casefold() and int(row) > anchor else 0
            return (qualifier or "") + col + absolute + str(int(row) + move)
        result = endpoint(sheet, column, absolute, number)
        if end_column:
            result += ":" + endpoint(end_sheet, end_column, end_absolute, end_number, sheet)
        return result
    for i in range(0, len(parts), 2):
        parts[i] = REFERENCE.sub(replace, parts[i])
    return "".join(parts)


def shift_range(text, anchor, count):
    return re.sub(r"(\$?[A-Z]+\$?)([1-9][0-9]*)", lambda m: m[1] + str(int(m[2]) + (count if int(m[2]) > anchor else 0)), text)


class Sheet:
    def __init__(self, book, name, path, tree=None):
        self.book, self.name, self.path, self.tree = book, name, path, tree
        self.rows = {}
        self.changed = False
        self.original = True
        self.revision = 0
        self.has_formulas = False
        self._reference_elements = None

    def cell(self, row, col, create=False):
        if not row or not col:
            return Cell()
        r = self.rows.get(row)
        if r is None:
            if not create:
                return Cell()
            self.rows[row] = r = Row()
        c = r.cells.get(col)
        if c is None:
            if not create:
                return Cell()
            r.cells[col] = c = Cell()
        return c

    def get(self, row, col):
        c = self.cell(row, col)
        if c.formula is not None and (c.dirty or c.value is None):
            return self.book.evaluate(self, row, col)
        return c.value

    def set(self, row, col, value):
        c = self.cell(row, col, True)
        c.value, c.formula, c.kind, c.dirty = value, None, None, True
        self.changed = True
        self.revision += 1

    def set_formula(self, row, col, formula):
        c = self.cell(row, col, True)
        c.formula, c.dirty = formula.lstrip("="), True
        self.changed = True
        self.has_formulas = True
        self.revision += 1

    def last(self, col):
        return max((r for r, row in self.rows.items() if row.cells.get(col) and row.cells[col].value not in (None, "")), default=0)

    def insert_after(self, anchor, count):
        if count <= 0:
            return
        if max(self.rows, default=0) + count > 1048576:
            raise WorkbookError("Tách chi tiết vượt giới hạn 1.048.576 dòng của Excel.")
        self.ensure_tree()
        for rowno in sorted(self.rows, reverse=True):
            if rowno > anchor:
                self.rows[rowno + count] = self.rows.pop(rowno)
        for other in self.book.sheets.values():
            if not other.has_formulas:
                continue
            for row in other.rows.values():
                for c in row.cells.values():
                    if c.formula:
                        updated = rewrite_formula(c.formula, other.name, self.name, anchor, count)
                        if updated != c.formula:
                            c.formula, c.dirty = updated, True
                            other.changed = True
        source = self.rows[anchor]
        for offset in range(1, count + 1):
            xml = E.Element(Q + "row", dict(source.element.attrib)) if source.element is not None else None
            cells = {}
            for col, cell in source.cells.items():
                if col > 17:
                    continue
                c = copy.copy(cell)
                c.element = copy.deepcopy(cell.element) if cell.element is not None else None
                if c.formula:
                    c.formula = rewrite_formula(c.formula, self.name, self.name, 0, offset, relative=True)
                    c.dirty = True
                cells[col] = c
            self.rows[anchor + offset] = Row(cells, xml)
        if self._reference_elements is None:
            self._reference_elements = tuple(self.tree.iter(*ROW_REFERENCE_TAGS))
        for e in self._reference_elements:
            for attr in ("ref", "sqref", "activeCell", "topLeftCell"):
                if e.get(attr):
                    e.set(attr, shift_range(e.get(attr), anchor, count))
        for e in self.book.workbook.findall(".//" + Q + "definedName"):
            if e.text:
                owner = ""
                if e.get("localSheetId") is not None:
                    owner = list(self.book.sheets)[int(e.get("localSheetId"))]
                e.text = rewrite_formula(e.text, owner, self.name, anchor, count)
        # Relationships that use row anchors must move with Excel's row insertions.
        self.book.shift_related(self, anchor, count)
        self.changed = True
        self.revision += 1

    def ensure_tree(self):
        if self.tree is not None:
            return
        self.tree = E.fromstring(self.book.zip.read(self.path), PARSER)
        for e in self.tree.findall("./" + Q + "sheetData/" + Q + "row"):
            r = int(e.get("r"))
            if r not in self.rows:
                self.rows[r] = Row()
            self.rows[r].element = e
            for ce in e:
                if ce.tag != Q + "c":
                    continue
                col = colnum(re.match(r"[A-Z]+", ce.get("r")).group())
                c = self.rows[r].cells.get(col)
                if c is None:
                    self.rows[r].cells[col] = parse_cell(ce, self.book.strings, True)
                else:
                    c.element = ce

    def serialize(self):
        self.ensure_tree()
        data = self.tree.find(Q + "sheetData")
        for child in list(data):
            data.remove(child)
        max_col = 1
        for r, row in sorted(self.rows.items()):
            e = row.element if row.element is not None else E.Element(Q + "row")
            e.set("r", str(r))
            for child in list(e):
                if child.tag == Q + "c":
                    e.remove(child)
            for col, c in sorted(row.cells.items()):
                ce = copy.deepcopy(c.element) if c.element is not None else E.Element(Q + "c")
                ce.set("r", f"{colname(col)}{r}")
                if c.style:
                    ce.set("s", str(c.style))
                if c.dirty or c.element is None:
                    for tag in ("v", "is", "f"):
                        for child in ce.findall(Q + tag):
                            ce.remove(child)
                    ce.attrib.pop("t", None)
                    value = c.value
                    if c.formula is not None:
                        E.SubElement(ce, Q + "f").text = c.formula
                        value = self.book.evaluate(self, r, col)
                    if isinstance(value, bool):
                        ce.set("t", "b")
                        E.SubElement(ce, Q + "v").text = "1" if value else "0"
                    elif isinstance(value, (int, float)) and math.isfinite(value):
                        E.SubElement(ce, Q + "v").text = format(value, ".15g")
                    elif value is not None:
                        if c.formula:
                            ce.set("t", "str")
                            E.SubElement(ce, Q + "v").text = str(value)
                        else:
                            ce.set("t", "inlineStr")
                            te = E.SubElement(E.SubElement(ce, Q + "is"), Q + "t")
                            te.set(XML + "space", "preserve")
                            te.text = str(value)
                e.append(ce)
                max_col = max(max_col, col)
            data.append(e)
        dim = self.tree.find(Q + "dimension")
        if dim is not None:
            dim.set("ref", f"A1:{colname(max_col)}{max(self.rows, default=1)}")
        return E.tostring(self.tree, xml_declaration=True, encoding="UTF-8", standalone=True)


class Book:
    def __init__(self, path, progress=lambda *_: None):
        self.path = Path(path)
        try:
            self.zip = zipfile.ZipFile(path)
        except (zipfile.BadZipFile, OSError) as exc:
            raise WorkbookError("Không đọc được tệp. Chọn .xlsx/.xlsm không mã hóa bằng mật khẩu.") from exc
        self.patches = {}
        self.evaluating = set()
        self.workbook = E.fromstring(self.zip.read("xl/workbook.xml"), PARSER)
        self.relationships = E.fromstring(self.zip.read("xl/_rels/workbook.xml.rels"), PARSER)
        self.content_types = E.fromstring(self.zip.read("[Content_Types].xml"), PARSER)
        self.sheets = {}
        self.strings = []
        self.formula_errors = 0
        if "xl/sharedStrings.xml" in self.zip.namelist():
            root = E.fromstring(self.zip.read("xl/sharedStrings.xml"), PARSER)
            self.strings = [text_value(e) for e in root]
        rels = {e.get("Id"): e.get("Target") for e in self.relationships}
        column_cache = {}
        for se in self.workbook.find(Q + "sheets"):
            target = rels[se.get("{" + REL + "}id")]
            path = target.lstrip("/") if target.startswith("/") else "xl/" + target
            name = se.get("name")
            progress(f"Đang đọc sheet {name}…", 10)
            sheet = Sheet(self, name, path)
            shared = {}
            # Compact the read-only view of styled blank cells before XML parsing.
            # The original ZIP member is retained byte-for-byte for source export.
            # Use the general parser unchanged for XML containing CDATA/comments
            # so literal text that resembles markup can never be removed.
            payload = self.zip.read(path)
            if b"<![CDATA[" not in payload and b"<!--" not in payload:
                payload = EMPTY_XML_CELLS.sub(b"", payload)
            with io.BytesIO(payload) as stream:
                for _, rowe in E.iterparse(stream, events=("end",), tag=Q + "row", resolve_entities=False, no_network=True, huge_tree=True):
                    r = int(rowe.get("r"))
                    cells = {}
                    # Excel often stores thousands of styled, empty cells per row.
                    # Filter these in libxml2 rather than doing Python .find calls
                    # for millions of blank cells. Preserve their XML at export.
                    for e in VALUE_CELLS(rowe):
                        f = e.find(Q + "f")
                        letters = e.get("r").rstrip("0123456789")
                        col = column_cache.get(letters)
                        if col is None:
                            col = column_cache[letters] = colnum(letters)
                        c = parse_cell(e, self.strings)
                        if f is not None and f.get("t") == "shared":
                            si = f.get("si")
                            if f.text:
                                shared[si] = (r, col, f.text)
                            elif si in shared:
                                oldr, oldc, form = shared[si]
                                if oldc != col:
                                    raise WorkbookError("Công thức shared nhiều cột chưa được hỗ trợ; lưu lại tệp bằng công thức thường.")
                                c.formula = rewrite_formula(form, name, name, 0, r - oldr, relative=True)
                        if f is not None and c.kind == "e":
                            self.formula_errors += 1
                        if f is not None:
                            sheet.has_formulas = True
                        cells[col] = c
                    if cells:
                        sheet.rows[r] = Row(cells)
                    rowe.clear()
                    while rowe.getprevious() is not None:
                        del rowe.getparent()[0]
            self.sheets[name] = sheet

    def close(self):
        self.zip.close()

    def add_sheet(self, name):
        if name in self.sheets:
            sheet = self.sheets[name]
            # Make processing logs directly accessible in the output file.
            for se in self.workbook.find(Q + "sheets"):
                if se.get("name") == name:
                    se.attrib.pop("state", None)
            return sheet
        sheet_id = max(int(e.get("sheetId")) for e in self.workbook.find(Q + "sheets")) + 1
        index = 1
        paths = set(self.zip.namelist()) | {s.path for s in self.sheets.values()}
        while f"xl/worksheets/sheet{index}.xml" in paths:
            index += 1
        path = f"xl/worksheets/sheet{index}.xml"
        ids = {e.get("Id") for e in self.relationships}
        index = 1
        while f"rId{index}" in ids:
            index += 1
        rid = f"rId{index}"
        E.SubElement(self.relationships, "{" + PKG + "}Relationship", Id=rid, Type=REL + "/worksheet", Target=path[3:])
        E.SubElement(self.workbook.find(Q + "sheets"), Q + "sheet", {"name": name, "sheetId": str(sheet_id), "{" + REL + "}id": rid})
        E.SubElement(self.content_types, "{http://schemas.openxmlformats.org/package/2006/content-types}Override", PartName="/" + path, ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml")
        tree = E.Element(Q + "worksheet", nsmap={None: NS})
        E.SubElement(tree, Q + "dimension", ref="A1")
        views = E.SubElement(tree, Q + "sheetViews")
        view = E.SubElement(views, Q + "sheetView", workbookViewId="0")
        E.SubElement(view, Q + "pane", ySplit="1", topLeftCell="A2", activePane="bottomLeft", state="frozen")
        E.SubElement(tree, Q + "sheetData")
        sheet = Sheet(self, name, path, tree)
        sheet.original = False
        self.sheets[name] = sheet
        return sheet

    def number_style(self, old_style, numfmt):
        if not hasattr(self, "styles"):
            self.styles = E.fromstring(self.zip.read("xl/styles.xml"), PARSER)
            self.style_cache = {}
        key = (old_style, numfmt)
        if key in self.style_cache:
            return self.style_cache[key]
        if isinstance(numfmt, str):
            formats = self.styles.find(Q + "numFmts")
            if formats is None:
                formats = E.Element(Q + "numFmts", count="0")
                self.styles.insert(0, formats)
            existing = next((int(e.get("numFmtId")) for e in formats if e.get("formatCode") == numfmt), None)
            if existing is None:
                existing = max([163] + [int(e.get("numFmtId")) for e in formats]) + 1
                E.SubElement(formats, Q + "numFmt", numFmtId=str(existing), formatCode=numfmt)
                formats.set("count", str(len(formats)))
            numfmt = existing
        xfs = self.styles.find(Q + "cellXfs")
        xf = copy.deepcopy(xfs[old_style])
        xf.set("numFmtId", str(numfmt))
        xf.set("applyNumberFormat", "1")
        index = len(xfs)
        xfs.append(xf)
        xfs.set("count", str(len(xfs)))
        self.style_cache[key] = index
        return index

    def header_style(self, issue=False):
        # Initialize stylesheet through the common style path.
        self.number_style(0, 0)
        key = ("header", issue)
        if key in self.style_cache:
            return self.style_cache[key]
        fonts, fills = self.styles.find(Q + "fonts"), self.styles.find(Q + "fills")
        font = copy.deepcopy(fonts[0])
        E.SubElement(font, Q + "b")
        font_id = len(fonts)
        fonts.append(font)
        fonts.set("count", str(len(fonts)))
        fill = E.SubElement(fills, Q + "fill")
        pattern = E.SubElement(fill, Q + "patternFill", patternType="solid")
        E.SubElement(pattern, Q + "fgColor", rgb="FFFFEB9C" if issue else "FFD9E1F2")
        E.SubElement(pattern, Q + "bgColor", indexed="64")
        fills.set("count", str(len(fills)))
        xfs = self.styles.find(Q + "cellXfs")
        xf = copy.deepcopy(xfs[0])
        xf.set("fontId", str(font_id))
        xf.set("fillId", str(len(fills) - 1))
        xf.set("applyFont", "1")
        xf.set("applyFill", "1")
        index = len(xfs)
        xfs.append(xf)
        xfs.set("count", str(len(xfs)))
        self.style_cache[key] = index
        return index

    def shift_related(self, sheet, anchor, count):
        path = str(Path(sheet.path).parent).replace("\\", "/") + "/_rels/" + Path(sheet.path).name + ".rels"
        if path not in self.zip.namelist():
            return
        rels = E.fromstring(self.zip.read(path), PARSER)
        import posixpath
        for rel in rels:
            if rel.get("TargetMode") == "External":
                continue
            target = posixpath.normpath(posixpath.join(posixpath.dirname(sheet.path), rel.get("Target")))
            if target.startswith("/"):
                target = target[1:]
            kind = rel.get("Type", "").rsplit("/", 1)[-1]
            if kind not in ("drawing", "table", "comments") or target not in self.zip.namelist():
                continue
            tree = E.fromstring(self.patches.get(target, self.zip.read(target)), PARSER)
            for e in tree.iter():
                local = E.QName(e).localname
                if kind == "drawing" and local == "row" and e.text and int(e.text) + 1 > anchor:
                    e.text = str(int(e.text) + count)
                elif kind == "comments" and e.get("ref"):
                    e.set("ref", shift_range(e.get("ref"), anchor, count))
                elif kind == "table" and e.get("ref"):
                    e.set("ref", shift_range(e.get("ref"), anchor, count))
            self.patches[target] = E.tostring(tree, xml_declaration=True, encoding="UTF-8", standalone=True)

    def evaluate(self, sheet, row, col):
        key = (sheet.name, row, col)
        if key in self.evaluating:
            raise FormulaError(f"Công thức vòng tại {sheet.name}!{colname(col)}{row}.")
        c = sheet.cell(row, col)
        if not c.formula:
            return c.value
        self.evaluating.add(key)
        try:
            result = Formula(self, sheet).run(c.formula)
            c.value = result
            return result
        except Exception as exc:
            raise FormulaError(f"Không thể tính công thức {sheet.name}!{colname(col)}{row}: ={c.formula}. {exc}") from exc
        finally:
            self.evaluating.remove(key)

    def save(self, path):
        path = Path(path)
        if path.resolve() == self.path.resolve():
            raise WorkbookError("Không được ghi đè tệp đầu vào. Chọn tên kết quả khác.")
        patches = dict(self.patches)
        for sheet in self.sheets.values():
            if sheet.changed or not sheet.original:
                patches[sheet.path] = sheet.serialize()
        calc = self.workbook.find(Q + "calcPr")
        if calc is None:
            calc = E.SubElement(self.workbook, Q + "calcPr")
        calc.set("fullCalcOnLoad", "1")
        calc.set("forceFullCalc", "1")
        calc.set("calcMode", "auto")
        if hasattr(self, "styles"):
            patches["xl/styles.xml"] = E.tostring(self.styles, xml_declaration=True, encoding="UTF-8", standalone=True)
        # Remove stale calculation chain; native Excel rebuilds it on opening.
        for e in list(self.relationships):
            if e.get("Type", "").endswith("/calcChain"):
                self.relationships.remove(e)
        for e in list(self.content_types):
            if e.get("PartName", "").endswith("/calcChain.xml"):
                self.content_types.remove(e)
        patches["xl/workbook.xml"] = E.tostring(self.workbook, xml_declaration=True, encoding="UTF-8", standalone=True)
        patches["xl/_rels/workbook.xml.rels"] = E.tostring(self.relationships, xml_declaration=True, encoding="UTF-8", standalone=True)
        patches["[Content_Types].xml"] = E.tostring(self.content_types, xml_declaration=True, encoding="UTF-8", standalone=True)
        fd, temporary = tempfile.mkstemp(prefix=".bang_ke_", suffix=path.suffix, dir=path.parent)
        os.close(fd)
        try:
            with zipfile.ZipFile(temporary, "w", zipfile.ZIP_DEFLATED, compresslevel=6) as out:
                for info in self.zip.infolist():
                    if info.filename == "xl/calcChain.xml":
                        continue
                    if info.filename in patches:
                        out.writestr(info, patches.pop(info.filename))
                    else:
                        with self.zip.open(info) as source, out.open(info, "w") as dest:
                            import shutil
                            shutil.copyfileobj(source, dest, 1024 * 1024)
                for name, blob in patches.items():
                    out.writestr(name, blob)
            with zipfile.ZipFile(temporary) as check:
                bad = check.testzip()
                if bad:
                    raise WorkbookError(f"Tệp kết quả không hợp lệ: {bad}")
            os.replace(temporary, path)
        finally:
            if os.path.exists(temporary):
                os.unlink(temporary)


class Formula:
    """Small explicit evaluator for affected arithmetic and SUM/SUBTOTAL totals.

    Unsupported formulas fail with a cell address instead of exporting stale totals.
    Unmodified source formulas retain their saved values and original XML.
    """
    def __init__(self, book, sheet):
        self.book, self.sheet = book, sheet

    def run(self, expression):
        def ref(sheetname, address):
            sh = self.sheet if not sheetname else self.book.sheets[sheetname.strip("'").replace("''", "'")]
            result = []
            for part in address.split(":"):
                match = re.fullmatch(r"\$?([A-Z]+)\$?(\d+)", part)
                result.append((int(match[2]), colnum(match[1])))
            if len(result) == 1:
                c = sh.cell(*result[0])
                if c.kind == "e":
                    raise FormulaError(str(c.value))
                return sh.get(*result[0])
            (r1, c1), (r2, c2) = result
            return [sh.get(r, c) for r in range(r1, r2 + 1) for c in range(c1, c2 + 1)]
        refs = []
        pattern = re.compile(r"(?<![\w])((?:'(?:[^']|'')+'|[A-Za-z_][\w .]*)!)?(\$?[A-Z]{1,3}\$?[1-9]\d*(?::\$?[A-Z]{1,3}\$?[1-9]\d*)?)(?![\w(])")
        pieces = re.split(r'("(?:[^"]|"")*")', expression.lstrip("="))
        def substitute(m):
            refs.append(ref(m[1][:-1] if m[1] else None, m[2]))
            return f"REF({len(refs)-1})"
        for i in range(0, len(pieces), 2):
            pieces[i] = pattern.sub(substitute, pieces[i]).replace("^", "**").replace(";", ",")
        expression = "".join(pieces)
        tree = ast.parse(expression, mode="eval")
        def flat(args):
            for a in args:
                yield from a if isinstance(a, list) else [a]
        def numbers(args):
            return [a for a in flat(args) if isinstance(a, (int, float)) and not isinstance(a, bool)]
        funcs = {
            "REF": lambda i: refs[i], "SUM": lambda *a: sum(numbers(a)),
            "ROUND": lambda a, n=0: xround(a, int(n)), "ABS": abs,
            "MIN": lambda *a: min(numbers(a), default=0), "MAX": lambda *a: max(numbers(a), default=0),
            "COUNT": lambda *a: len(numbers(a)), "COUNTA": lambda *a: sum(v not in (None, "") for v in flat(a)),
            "PRODUCT": lambda *a: math.prod(numbers(a)),
            "AVERAGE": lambda *a: sum(numbers(a)) / len(numbers(a)),
        }
        binary = {ast.Add: operator.add, ast.Sub: operator.sub, ast.Mult: operator.mul, ast.Div: operator.truediv, ast.Pow: operator.pow}
        def visit(n):
            if isinstance(n, ast.Expression):
                return visit(n.body)
            if isinstance(n, ast.Constant):
                return n.value
            if isinstance(n, ast.UnaryOp) and isinstance(n.op, (ast.USub, ast.UAdd)):
                v = visit(n.operand) or 0
                return -v if isinstance(n.op, ast.USub) else v
            if isinstance(n, ast.BinOp) and type(n.op) in binary:
                return binary[type(n.op)](visit(n.left) or 0, visit(n.right) or 0)
            if isinstance(n, ast.Call) and isinstance(n.func, ast.Name):
                name = n.func.id.upper()
                args = [visit(a) for a in n.args]
                if name == "SUBTOTAL":
                    code = int(args.pop(0)) % 100
                    if code not in (1, 2, 3, 4, 5, 6, 9):
                        raise FormulaError("SUBTOTAL chưa hỗ trợ hàm này")
                    if any(row.element is not None and row.element.get("hidden") == "1" for row in self.sheet.rows.values()):
                        raise FormulaError("SUBTOTAL với dòng ẩn cần được tính bằng trình bảng tính")
                    name = {1: "AVERAGE", 2: "COUNT", 3: "COUNTA", 4: "MAX", 5: "MIN", 6: "PRODUCT", 9: "SUM"}[code]
                if name in funcs:
                    return funcs[name](*args)
            raise FormulaError("Hàm chưa được hỗ trợ trong chế độ không cài Excel")
        return visit(tree)
