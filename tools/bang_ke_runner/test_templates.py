from pathlib import Path
import json
import hashlib
import datetime as dt
import shutil
import sys
import tempfile
import unittest
from unittest.mock import patch
import zipfile
from lxml import etree as E

from openpyxl import load_workbook, Workbook

from engine import process_file
from templates import save_template, template_path, TEMPLATE_SHEETS, TEMPLATE_KHAI_START_ROW, TEMPLATE_NAME


def blank_reference_fixture(path):
    """Small test fixture with the supplied headers; don't load millions of styled cells."""
    source = load_workbook(template_path(), read_only=True, data_only=False)
    wb = Workbook()
    wb.remove(wb.active)
    for name in TEMPLATE_SHEETS:
        ws = wb.create_sheet(name)
        last = TEMPLATE_KHAI_START_ROW - 1 if name == 'KHAI THUE' else 1
        width = 16 if name == 'KHAI THUE' else (56 if name == 'chi tiet hoa don' else 53)
        for row in source[name].iter_rows(max_row=last, max_col=width):
            for cell in row:
                if cell.value is not None:
                    ws.cell(cell.row, cell.column, cell.value)
    source.close()
    wb.save(path)
    wb.close()


def fill_sample_template(path):
    shutil.copyfile(template_path(), path)
    wb = load_workbook(path)
    khai = wb['KHAI THUE']
    for col, value in {'C':'1','D':'C26ABC','E':'00012345','F':dt.datetime(2026,11,19),'G':'NCC Mẫu','H':'0012345678','I':'Tổng','M':100,'N':8,'O':8}.items():
        khai[f'{col}{TEMPLATE_KHAI_START_ROW}'] = value
    detail = wb['chi tiet hoa don']
    for row, item, amount, tax in [(2,'Mặt hàng A',40,3.2),(3,'Mặt hàng B',60,4.8)]:
        for col, value in {'K':'1','L':'C26ABC','M':'12345','N':'19/11/2026','S':'NCC Mẫu','U':'12345678','W':item,'X':'Cái','Y':1,'Z':amount,'AA':amount,'AC':amount,'AD':8,'AE':tax}.items():
            detail[f'{col}{row}'] = value
    summary = wb['tong hop hoa don']
    for col, value in {'K':'1','L':'C26ABC','M':'00012345','N':dt.datetime(2026,11,19),'T':'NCC Mẫu','V':'0012345678','W':'Tổng','X':100,'Z':100,'AB':8,'AC':8}.items():
        summary[f'{col}2'] = value
    wb.save(path)
    wb.close()


class TemplateTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.root = Path(self.temp.name)

    def tearDown(self):
        self.temp.cleanup()

    def test_save_template_has_user_layout_and_empty_data(self):
        dest = self.root / 'Mẫu dữ liệu.xlsx'
        save_template(dest)
        self.assertEqual(dest.read_bytes(), template_path().read_bytes())
        wb = load_workbook(dest, read_only=True)
        self.assertEqual(wb.sheetnames, list(TEMPLATE_SHEETS))
        self.assertEqual(wb['chi tiet hoa don']['W1'].value,'Diễn giải')
        self.assertEqual(wb['KHAI THUE']['C5'].value,'Mẫu số')
        self.assertEqual(wb['KHAI THUE']['E5'].value,'Số')
        self.assertEqual(TEMPLATE_KHAI_START_ROW,8)
        self.assertIsNone(wb['KHAI THUE']['E8'].value)
        wb.close()
        with zipfile.ZipFile(dest) as archive:
            self.assertFalse(any('vba' in n.lower() for n in archive.namelist()))

    def test_template_is_the_verbatim_supplied_file(self):
        info = json.loads((Path(__file__).parent / 'assets/template_info.json').read_text(encoding='utf-8'))
        self.assertEqual(info['preservation'], 'verbatim')
        self.assertEqual(info['source_filename'], 'template2.xlsx')
        expected = 'c7d9386ed14081754358d11c8152f853bbb929eea1ae4b1bdf4f216ff58d724c'
        self.assertEqual(info['source_sha256'], expected)
        self.assertEqual(hashlib.sha256(template_path().read_bytes()).hexdigest(), expected)
        dest = self.root / 'template.xlsx'
        save_template(dest)
        self.assertEqual(hashlib.sha256(dest.read_bytes()).hexdigest(), expected)

    def test_filled_template_processes_and_exports_one_sheet(self):
        dest = self.root / 'input.xlsx'
        save_template(dest)
        fill_sample_template(dest)
        output = self.root / 'result.xlsx'
        report = process_file(dest, output)
        self.assertEqual(report['stats']['expanded_groups'],1)
        self.assertEqual(report['stats']['added_rows'],1)
        self.assertEqual(report['stats']['data_rows'],2)
        self.assertEqual(report['stats']['total_amount'],100)
        self.assertEqual(report['stats']['total_vat'],8)
        wb = load_workbook(output,data_only=True)
        self.assertEqual(wb.sheetnames,['KHAI THUE'])
        rows=(TEMPLATE_KHAI_START_ROW,TEMPLATE_KHAI_START_ROW+1)
        self.assertEqual([wb.active[f'I{r}'].value for r in rows],['Mặt hàng A','Mặt hàng B'])
        self.assertEqual([wb.active[f'E{r}'].value for r in rows],['00012345','00012345'])
        wb.close()

    def test_blank_template_requires_data(self):
        src = self.root / 'blank.xlsx'
        blank_reference_fixture(src)
        out = self.root / 'result.xlsx'
        with self.assertRaisesRegex(Exception,'không có dòng hóa đơn'):
            process_file(src,out)
        self.assertFalse(out.exists())

    def test_frozen_resource_directory(self):
        assets = self.root / 'frozen' / 'assets'
        assets.mkdir(parents=True)
        shutil.copyfile(template_path(),assets/TEMPLATE_NAME)
        with patch.object(sys,'_MEIPASS',str(assets.parent),create=True):
            dest=self.root/'copied.xlsx'
            save_template(dest)
            self.assertEqual(dest.read_bytes(),(assets/TEMPLATE_NAME).read_bytes())

    def test_refuses_wrong_extension_and_internal_asset_overwrite(self):
        with self.assertRaisesRegex(Exception,'xlsx'):
            save_template(self.root/'wrong.xlsm')
        with self.assertRaisesRegex(Exception,'khác'):
            save_template(template_path())


if __name__=='__main__':
    unittest.main(verbosity=2)
