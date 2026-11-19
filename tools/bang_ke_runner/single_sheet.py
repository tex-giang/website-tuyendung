"""Export one processed worksheet as a clean, macro-free XLSX package.

Build a new package so removed sheets cannot remain as ZIP members, shared
strings, embedded macros, external workbook links or workbook metadata.
"""
from __future__ import annotations

import copy
import os
from pathlib import Path
import posixpath
import re
import tempfile
import zipfile

from ooxml import E, Q, NS, REL, PKG, PARSER, REFERENCE, Formula, WorkbookError

CT = "http://schemas.openxmlformats.org/package/2006/content-types"
CQ = "{" + CT + "}"
RID = "{" + REL + "}id"
XML_DECL = dict(xml_declaration=True, encoding="UTF-8", standalone=True)


def xml(e):
    return E.tostring(e, **XML_DECL)


def local_formula(expression, sheet_name):
    pieces = re.split(r'("(?:[^"]|"")*")', expression or "")
    for piece in pieces[::2]:
        if re.search(r"\[[^\]]+\][^!]*!", piece):
            return False
        for match in REFERENCE.finditer(piece):
            for qualifier in (match[1], match[5]):
                if qualifier and qualifier[:-1].strip("'").replace("''", "'").casefold() != sheet_name.casefold():
                    return False
    return True


def relationship_path(part):
    return posixpath.join(posixpath.dirname(part), "_rels", posixpath.basename(part) + ".rels")


def resolve_part(owner, target):
    if target.startswith("/"):
        return target.lstrip("/")
    return posixpath.normpath(posixpath.join(posixpath.dirname(owner), target))


def drop_relationship_elements(root, removed, drawing=False):
    for e in list(root.iter()):
        if not any(k.startswith("{" + REL + "}") and v in removed for k, v in e.attrib.items()):
            continue
        item = e
        if drawing:
            while item.getparent() is not None and item.getparent() is not root:
                item = item.getparent()
        parent = item.getparent()
        if parent is not None:
            parent.remove(item)
    for container in list(root.iter(Q + "tableParts")):
        container.set("count", str(len(container)))
        if not len(container):
            container.getparent().remove(container)


def save_khai_only(book, sheet, output):
    output = Path(output)
    if output.suffix.lower() != ".xlsx":
        raise WorkbookError("Kết quả chỉ có sheet KHAI THUE phải được lưu với đuôi .xlsx.")
    notes = []
    # Evaluate source-dependent formulas while the input sheets still exist,
    # then store those results as values so the standalone file has no #REF!.
    converted = 0
    for rowno, row in sheet.rows.items():
        for col, cell in row.cells.items():
            if cell.formula and not local_formula(cell.formula, sheet.name):
                value = book.evaluate(sheet, rowno, col)
                sheet.set(rowno, col, value)
                converted += 1
    if converted:
        notes.append(f"Đã chuyển {converted} công thức tham chiếu sheet nguồn thành giá trị đã tính.")
    root = E.fromstring(sheet.serialize(), PARSER)
    for props in root.findall(Q + "sheetPr"):
        props.attrib.pop("codeName", None)
    views = root.find(Q + "sheetViews")
    if views is not None:
        for view in views:
            view.set("workbookViewId", "0")
            view.set("tabSelected", "1")
    # Keep only defined names whose entire meaning belongs to this worksheet.
    all_names = book.workbook.find(Q + "definedNames")
    original_index = list(book.sheets).index(sheet.name)
    kept_names = E.Element(Q + "definedNames")
    names = {}
    if all_names is not None:
        for name in all_names:
            scope = name.get("localSheetId")
            if scope is None or int(scope) == original_index:
                names[name.get("name", "").casefold()] = name.text or ""
                if local_formula(name.text, sheet.name):
                    item = copy.deepcopy(name)
                    if scope is not None:
                        item.set("localSheetId", "0")
                    kept_names.append(item)
    validation = root.find(Q + "dataValidations")
    if validation is not None:
        for rule in list(validation):
            for formula in list(rule):
                if E.QName(formula).localname not in ("formula1", "formula2"):
                    continue
                text = formula.text or ""
                expression = names.get(text.lstrip("=").casefold(), text)
                if local_formula(expression, sheet.name):
                    continue
                try:
                    if rule.get("type") != "list":
                        raise ValueError("not a list")
                    values = Formula(book, sheet).run(expression)
                    values = values if isinstance(values, list) else [values]
                    values = [str(v) for v in values if v not in (None, "")]
                    inline = ",".join(values)
                    if not values or len(inline) > 253 or any("," in v or '"' in v for v in values):
                        raise ValueError("list cannot be embedded")
                    formula.text = '"' + inline + '"'
                except Exception:
                    validation.remove(rule)
                    notes.append("Đã bỏ một danh sách/điều kiện nhập liệu phụ thuộc sheet không xuất.")
                    break
        validation.set("count", str(len(validation)))
        if not len(validation):
            root.remove(validation)
    for cf in list(root.findall(Q + "conditionalFormatting")):
        for rule in list(cf):
            if any(not local_formula(f.text, sheet.name) for f in rule.findall(Q + "formula")):
                cf.remove(rule)
                notes.append("Đã bỏ một quy tắc màu phụ thuộc sheet không xuất.")
        if not len(cf):
            root.remove(cf)
    hyperlinks = root.find(Q + "hyperlinks")
    if hyperlinks is not None:
        for link in list(hyperlinks):
            if link.get("location") and not local_formula(link.get("location"), sheet.name):
                hyperlinks.remove(link)
        if not len(hyperlinks):
            root.remove(hyperlinks)

    # Prune shared strings to only those actually used by KHAI THUE.
    payload = {}
    shared_cells = root.findall(".//" + Q + "c[@t='s']")
    if shared_cells:
        old_strings = E.fromstring(book.zip.read("xl/sharedStrings.xml"), PARSER)
        strings = E.Element(Q + "sst", nsmap={None: NS})
        indices = {}
        for cell in shared_cells:
            value = cell.find(Q + "v")
            if value is None or value.text is None:
                continue
            old = int(value.text)
            if old not in indices:
                indices[old] = len(indices)
                strings.append(copy.deepcopy(old_strings[old]))
            value.text = str(indices[old])
        strings.set("count", str(len(shared_cells)))
        strings.set("uniqueCount", str(len(indices)))
        payload["xl/sharedStrings.xml"] = xml(strings)

    available = set(book.zip.namelist())
    allowed = {"drawing", "image", "printerSettings", "table", "hyperlink"}
    visited = set()
    def copy_resource(part, kind):
        if part in visited:
            return part in payload
        visited.add(part)
        blob = book.patches[part] if part in book.patches else book.zip.read(part)
        tree = E.fromstring(blob, PARSER) if kind in ("drawing", "table") else None
        if kind == "drawing":
            for anchor in list(tree):
                if any(e.get("macro") for e in anchor.iter()):
                    tree.remove(anchor)
        relpart = relationship_path(part)
        if relpart in available:
            reltree = E.fromstring(book.zip.read(relpart), PARSER)
            removed = set()
            for rel in list(reltree):
                relkind = rel.get("Type", "").rsplit("/", 1)[-1]
                external = rel.get("TargetMode") == "External"
                if external:
                    keep = relkind == "hyperlink"
                else:
                    target = resolve_part(part, rel.get("Target", ""))
                    keep = relkind in allowed and target in available and copy_resource(target, relkind)
                if not keep:
                    removed.add(rel.get("Id"))
                    reltree.remove(rel)
            if tree is not None:
                drop_relationship_elements(tree, removed, kind == "drawing")
            if len(reltree):
                payload[relpart] = xml(reltree)
        if tree is not None:
            if kind == "drawing" and not len(tree):
                payload.pop(relpart, None)
                return False
            for e in tree.iter():
                e.attrib.pop("macro", None)
            blob = xml(tree)
        payload[part] = blob
        return True

    old_relpath = relationship_path(sheet.path)
    if old_relpath in available:
        reltree = E.fromstring(book.zip.read(old_relpath), PARSER)
        removed = set()
        for rel in list(reltree):
            kind = rel.get("Type", "").rsplit("/", 1)[-1]
            external = rel.get("TargetMode") == "External"
            target = resolve_part(sheet.path, rel.get("Target", ""))
            keep = (kind == "hyperlink") if external else (kind in allowed and target in available and copy_resource(target, kind))
            if not keep:
                removed.add(rel.get("Id"))
                reltree.remove(rel)
        drop_relationship_elements(root, removed)
        if len(reltree):
            payload["xl/worksheets/_rels/sheet1.xml.rels"] = xml(reltree)
    # Form controls and embedded OLE/ActiveX content are not part of the result.
    for tag in ("controls", "oleObjects", "legacyDrawing", "legacyDrawingHF"):
        for node in root.findall(Q + tag):
            root.remove(node)
    payload["xl/worksheets/sheet1.xml"] = xml(root)
    payload["xl/styles.xml"] = xml(book.styles) if hasattr(book, "styles") else book.zip.read("xl/styles.xml")
    if "xl/theme/theme1.xml" in available:
        payload["xl/theme/theme1.xml"] = book.zip.read("xl/theme/theme1.xml")
    workbook = E.Element(Q + "workbook", nsmap={None: NS, "r": REL})
    original_props = book.workbook.find(Q + "workbookPr")
    if original_props is not None and original_props.get("date1904"):
        E.SubElement(workbook, Q + "workbookPr", date1904=original_props.get("date1904"))
    book_views = E.SubElement(workbook, Q + "bookViews")
    E.SubElement(book_views, Q + "workbookView", activeTab="0", firstSheet="0")
    sheets = E.SubElement(workbook, Q + "sheets")
    E.SubElement(sheets, Q + "sheet", {"name": sheet.name, "sheetId": "1", RID: "rId1"})
    if len(kept_names):
        workbook.append(kept_names)
    E.SubElement(workbook, Q + "calcPr", calcMode="auto", fullCalcOnLoad="1", forceFullCalc="1")
    payload["xl/workbook.xml"] = xml(workbook)
    relationships = E.Element("{" + PKG + "}Relationships", nsmap={None: PKG})
    targets = [("worksheet", "worksheets/sheet1.xml"), ("styles", "styles.xml")]
    if "xl/theme/theme1.xml" in payload:
        targets.append(("theme", "theme/theme1.xml"))
    if "xl/sharedStrings.xml" in payload:
        targets.append(("sharedStrings", "sharedStrings.xml"))
    for i, (kind, target) in enumerate(targets, 1):
        E.SubElement(relationships, "{" + PKG + "}Relationship", Id=f"rId{i}", Type=REL + "/" + kind, Target=target)
    payload["xl/_rels/workbook.xml.rels"] = xml(relationships)
    package_rels = E.Element("{" + PKG + "}Relationships", nsmap={None: PKG})
    E.SubElement(package_rels, "{" + PKG + "}Relationship", Id="rId1", Type=REL + "/officeDocument", Target="xl/workbook.xml")
    payload["_rels/.rels"] = xml(package_rels)
    # Build content types from the exact retained parts, without macro types.
    content = E.Element(CQ + "Types", nsmap={None: CT})
    originals = {e.get("PartName", "").lstrip("/"): e.get("ContentType") for e in book.content_types if E.QName(e).localname == "Override"}
    defaults = {e.get("Extension"): e.get("ContentType") for e in book.content_types if E.QName(e).localname == "Default"}
    defaults.update({"rels": "application/vnd.openxmlformats-package.relationships+xml", "xml": "application/xml"})
    types = {
        "xl/workbook.xml": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml",
        "xl/worksheets/sheet1.xml": "application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml",
        "xl/styles.xml": "application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml",
        "xl/sharedStrings.xml": "application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml",
        "xl/theme/theme1.xml": "application/vnd.openxmlformats-officedocument.theme+xml",
    }
    for part in payload:
        if part in types or part in originals:
            E.SubElement(content, CQ + "Override", PartName="/" + part, ContentType=types.get(part, originals.get(part)))
    for extension in sorted({Path(part).suffix.lstrip(".") for part in payload}):
        if extension in defaults:
            E.SubElement(content, CQ + "Default", Extension=extension, ContentType=defaults[extension])
    payload["[Content_Types].xml"] = xml(content)

    fd, temp = tempfile.mkstemp(prefix=".khai_thue_", suffix=".xlsx", dir=output.parent)
    os.close(fd)
    try:
        with zipfile.ZipFile(temp, "w", zipfile.ZIP_DEFLATED, compresslevel=6) as z:
            for part, blob in payload.items():
                z.writestr(part, blob)
        with zipfile.ZipFile(temp) as z:
            if z.testzip() is not None:
                raise WorkbookError("Tệp KHAI THUE xuất ra không hợp lệ.")
        os.replace(temp, output)
    finally:
        if os.path.exists(temp):
            os.unlink(temp)
    return notes
