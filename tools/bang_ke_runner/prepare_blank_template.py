"""Build a blank embedded template, preserving the supplied OOXML formatting.

Build-time utility; never edits the user's original. Streaming rows avoids
loading millions of styled source cells as Python workbook objects.
"""
from __future__ import annotations

import argparse
import copy
import hashlib
import io
import json
import os
from pathlib import Path
import re
import tempfile
import zipfile

from lxml import etree as E

NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
Q = '{' + NS + '}'
REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
PKG = 'http://schemas.openxmlformats.org/package/2006/relationships'
CT = 'http://schemas.openxmlformats.org/package/2006/content-types'
PARSER = E.XMLParser(resolve_entities=False, no_network=True, huge_tree=True)
DATA_BLOCK = re.compile(rb'(<(?P<p>(?:[A-Za-z_][\w.-]*:)?)sheetData\b[^>]*>).*?(</(?P=p)sheetData\s*>)', re.S)
VALUE_CELLS = E.XPath('./m:c[m:v or m:is or m:f]', namespaces={'m': NS})
DECL = dict(xml_declaration=True, encoding='UTF-8', standalone=True)


def build_blank(source, destination, metadata):
    source, destination = Path(source).resolve(), Path(destination).resolve()
    if source == destination:
        raise ValueError('The original workbook must not be overwritten.')
    destination.parent.mkdir(parents=True, exist_ok=True)
    digest = hashlib.sha256(source.read_bytes()).hexdigest()
    with tempfile.TemporaryDirectory(prefix='blank_template_') as tmp:
        tmp = Path(tmp)
        with zipfile.ZipFile(source) as original:
            workbook = E.fromstring(original.read('xl/workbook.xml'), PARSER)
            rels = E.fromstring(original.read('xl/_rels/workbook.xml.rels'), PARSER)
            targets = {e.get('Id'): e.get('Target') for e in rels}
            old_strings = E.fromstring(original.read('xl/sharedStrings.xml'), PARSER)
            strings = E.Element(Q + 'sst', nsmap={None: NS})
            indices = {}
            string_count = 0
            parts = {}
            removed_cells = {}
            sheet_rows = {}
            for sheet in workbook.find(Q + 'sheets'):
                name = sheet.get('name')
                first = 8 if name == 'KHAI THUE' else 2
                sheet_rows[name] = first
                target = targets[sheet.get('{' + REL + '}id')]
                part = target.lstrip('/') if target.startswith('/') else 'xl/' + target
                payload = original.read(part)
                shallow, count = DATA_BLOCK.subn(lambda m: m[1] + m[3], payload, count=1)
                if count != 1:
                    raise ValueError(f'Cannot locate sheetData in {name}.')
                skeleton = E.fromstring(shallow, PARSER)
                for links in skeleton.findall(Q + 'hyperlinks'):
                    skeleton.remove(links)
                for sort in skeleton.findall('.//' + Q + 'sortState'):
                    sort.getparent().remove(sort)
                for filters in skeleton.findall('.//' + Q + 'filterColumn'):
                    filters.getparent().remove(filters)
                for view in skeleton.findall('./' + Q + 'sheetViews/' + Q + 'sheetView'):
                    view.set('topLeftCell', 'A1')
                    for selection in view.findall(Q + 'selection'):
                        selection.set('activeCell', ('B' if name == 'KHAI THUE' else 'A') + str(first))
                        selection.set('sqref', selection.get('activeCell'))
                output = tmp / Path(part).name
                cleared = 0
                with E.xmlfile(str(output), encoding='UTF-8') as writer:
                    writer.write_declaration(standalone=True)
                    with writer.element(skeleton.tag, skeleton.attrib, nsmap=skeleton.nsmap):
                        for child in skeleton:
                            if child.tag != Q + 'sheetData':
                                writer.write(child)
                                continue
                            with writer.element(child.tag, child.attrib):
                                for _, row in E.iterparse(io.BytesIO(payload), events=('end',), tag=Q + 'row', resolve_entities=False, no_network=True, huge_tree=True):
                                    keep = int(row.get('r')) < first
                                    for cell in VALUE_CELLS(row):
                                        if not keep:
                                            for value in list(cell):
                                                if value.tag in (Q + 'v', Q + 'f', Q + 'is'):
                                                    cell.remove(value)
                                            cell.attrib.pop('t', None)
                                            cleared += 1
                                        elif cell.get('t') == 's':
                                            value = cell.find(Q + 'v')
                                            if value is not None and value.text is not None:
                                                index = int(value.text)
                                                if index not in indices:
                                                    indices[index] = len(indices)
                                                    strings.append(copy.deepcopy(old_strings[index]))
                                                value.text = str(indices[index])
                                                string_count += 1
                                    writer.write(row)
                                    row.clear()
                                    while row.getprevious() is not None:
                                        del row.getparent()[0]
                parts[part] = output
                removed_cells[name] = cleared
            strings.set('count', str(string_count))
            strings.set('uniqueCount', str(len(indices)))
            replacements = {'xl/sharedStrings.xml': E.tostring(strings, **DECL)}
            removed = {'docProps/core.xml', 'xl/calcChain.xml'}
            for part in ('_rels/.rels', 'xl/_rels/workbook.xml.rels'):
                tree = E.fromstring(original.read(part), PARSER)
                for rel in list(tree):
                    if rel.get('Type', '').endswith(('/metadata/core-properties', '/calcChain', '/hyperlink')):
                        tree.remove(rel)
                replacements[part] = E.tostring(tree, **DECL)
            content_types = E.fromstring(original.read('[Content_Types].xml'), PARSER)
            for item in list(content_types):
                if item.get('PartName', '').lstrip('/') in removed:
                    content_types.remove(item)
            replacements['[Content_Types].xml'] = E.tostring(content_types, **DECL)
            if 'docProps/app.xml' in original.namelist():
                app = E.fromstring(original.read('docProps/app.xml'), PARSER)
                for e in app.iter():
                    if E.QName(e).localname in ('Company', 'Manager', 'HyperlinkBase'):
                        e.text = None
                replacements['docProps/app.xml'] = E.tostring(app, **DECL)
            fd, temporary = tempfile.mkstemp(prefix='.blank_', suffix='.xlsx', dir=destination.parent)
            os.close(fd)
            try:
                with zipfile.ZipFile(temporary, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as output:
                    for info in original.infolist():
                        if info.filename in removed:
                            continue
                        if info.filename in parts:
                            output.write(parts[info.filename], info.filename)
                        elif info.filename in replacements:
                            output.writestr(info, replacements[info.filename])
                        elif info.filename.startswith('xl/worksheets/_rels/'):
                            tree = E.fromstring(original.read(info.filename), PARSER)
                            for rel in list(tree):
                                if rel.get('Type', '').endswith('/hyperlink'):
                                    tree.remove(rel)
                            output.writestr(info, E.tostring(tree, **DECL))
                        else:
                            with original.open(info) as src, output.open(info, 'w') as dst:
                                import shutil
                                shutil.copyfileobj(src, dst, 1024 * 1024)
                with zipfile.ZipFile(temporary) as check:
                    if check.testzip() is not None:
                        raise ValueError('Invalid generated template.')
                os.replace(temporary, destination)
            finally:
                if os.path.exists(temporary):
                    os.unlink(temporary)
    record = {
        'source_filename': source.name,
        'source_sha256': digest,
        'sha256': hashlib.sha256(destination.read_bytes()).hexdigest(),
        'format_preserved': True,
        'contains_existing_data': False,
        'first_data_rows': sheet_rows,
        'cleared_value_cells': removed_cells,
        'retained_header_shared_strings': len(indices),
    }
    Path(metadata).write_text(json.dumps(record, ensure_ascii=False, indent=2), encoding='UTF-8')
    return record


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--source', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--metadata', required=True)
    args = parser.parse_args()
    print(json.dumps(build_blank(args.source, args.output, args.metadata), ensure_ascii=False, indent=2))
