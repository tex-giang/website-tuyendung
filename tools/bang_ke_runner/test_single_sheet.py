"""The application output contract: exactly one macro-free KHAI THUE sheet."""
from pathlib import Path
import tempfile
import unittest
import zipfile

from lxml import etree as E
from openpyxl import load_workbook
from openpyxl.styles import PatternFill
from openpyxl.worksheet.datavalidation import DataValidation
from openpyxl.workbook.defined_name import DefinedName

from engine import process_file
from ooxml import Q, NS, REL, PKG
from test_engine import make_file, rec


class SingleSheetTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.root = Path(self.temp.name)
        self.source, self.output = self.root / "input.xlsx", self.root / "KHAI_THUE.xlsx"

    def tearDown(self):
        self.temp.cleanup()

    def fixture(self, formulas=False):
        make_file(self.source, [rec(item="Gốc", amount=101, vat=10, rate=10)], [rec(item="A", amount=50.4, vat=5.2, rate=10), rec(item="B", amount=50.4, vat=5.2, rate=10)], [rec(amount=100.8, vat=10.4)], formulas)

    def test_one_sheet_and_no_sidecar_files(self):
        self.fixture(True)
        original = self.source.read_bytes()
        result = process_file(self.source, self.output)
        wb = load_workbook(self.output, data_only=True)
        self.assertEqual(wb.sheetnames, ["KHAI THUE"])
        self.assertEqual(result["output_sheets"], ["KHAI THUE"])
        self.assertEqual(wb["KHAI THUE"]["M10"].value, 101)
        self.assertEqual(wb["KHAI THUE"]["O10"].value, 10)
        self.assertEqual(self.source.read_bytes(), original)
        self.assertEqual(set(self.root.iterdir()), {self.source, self.output})
        with zipfile.ZipFile(self.output) as z:
            self.assertEqual([n for n in z.namelist() if n.startswith("xl/worksheets/") and n.endswith(".xml")], ["xl/worksheets/sheet1.xml"])

    def test_formatting_merge_print_and_local_formulas_kept(self):
        self.fixture(True)
        wb = load_workbook(self.source)
        ws = wb["KHAI THUE"]
        ws.column_dimensions["I"].width = 37
        ws.row_dimensions[8].height = 28
        ws["I8"].fill = PatternFill("solid", fgColor="FFEEDD")
        ws.print_area = "B2:P10"
        ws.print_title_rows = "4:7"
        wb.save(self.source)
        process_file(self.source, self.output)
        wb = load_workbook(self.output, data_only=False)
        ws = wb["KHAI THUE"]
        self.assertEqual(ws["M10"].value, "=SUM(M8:M9)")
        self.assertEqual(ws.column_dimensions["I"].width, 37)
        self.assertEqual(ws.row_dimensions[8].height, 28)
        self.assertEqual(ws["I8"].fill.fgColor.rgb, "00FFEEDD")
        self.assertEqual(str(ws.merged_cells), "B2:P2")
        self.assertIn("$B$2:$P$11", ws.print_area)
        self.assertEqual(ws.print_title_rows, "$4:$7")

    def test_source_formulas_export_as_values(self):
        make_file(self.source, [rec(item="Gốc", vat=10)], [rec(item="A", amount=100, vat=10)], [rec(amount=100, vat=10)])
        wb = load_workbook(self.source)
        wb["KHAI THUE"]["M8"] = "='chi tiet hoa don'!AB2"
        wb.save(self.source)
        result = process_file(self.source, self.output)
        wb = load_workbook(self.output, data_only=False)
        self.assertEqual(wb["KHAI THUE"]["M8"].value, 100)
        self.assertTrue(any("công thức" in text for text in result["export_notes"]))
        self.assertEqual(wb.sheetnames, ["KHAI THUE"])

    def test_only_xlsx_output_allowed(self):
        self.fixture()
        with self.assertRaisesRegex(Exception, "xlsx"):
            process_file(self.source, self.root / "result.xlsm")
        self.assertFalse((self.root / "result.xlsm").exists())

    def test_source_validation_embedded_without_hidden_sheet(self):
        self.fixture()
        wb = load_workbook(self.source)
        header = wb.create_sheet("Header")
        for row, value in enumerate([0, 8, "KCT"], 1):
            header.cell(row, 1, value)
        header.sheet_state = "hidden"
        wb.defined_names.add(DefinedName("TaxRates", attr_text="'Header'!$A$1:$A$3"))
        dv = DataValidation(type="list", formula1="TaxRates")
        wb["KHAI THUE"].add_data_validation(dv)
        dv.add("N8")
        wb.save(self.source)
        process_file(self.source, self.output)
        wb = load_workbook(self.output)
        self.assertEqual(wb.sheetnames, ["KHAI THUE"])
        self.assertEqual(wb["KHAI THUE"].data_validations.dataValidation[0].formula1, '"0,8,KCT"')
        self.assertNotIn("TaxRates", wb.defined_names)

    def test_warning_details_remain_in_app_report(self):
        make_file(self.source, [rec(num="missing", amount=100, vat=10)], [rec(item="A", amount=100, vat=10)], [rec(amount=100, vat=10)])
        result = process_file(self.source, self.output)
        self.assertEqual(len(result["issues"]), 1)
        self.assertIn("KHONG_CO_UNG_VIEN", result["issues"][0]["reason"])
        self.assertEqual(load_workbook(self.output).sheetnames, ["KHAI THUE"])

    def test_khai_can_be_non_first_sheet(self):
        self.fixture()
        wb = load_workbook(self.source)
        wb.move_sheet("KHAI THUE", offset=2)
        wb["KHAI THUE"].print_area = "B2:P10"
        wb.save(self.source)
        process_file(self.source, self.output)
        wb = load_workbook(self.output)
        self.assertEqual(wb.sheetnames, ["KHAI THUE"])
        self.assertEqual(wb.active.title, "KHAI THUE")
        self.assertIn("$B$2:$P$11", wb.active.print_area)

    def test_image_resources_remain_valid(self):
        self.fixture()
        from PIL import Image as PILImage
        from openpyxl.drawing.image import Image
        image_path = self.root / "logo.png"
        PILImage.new("RGB", (8, 8), "blue").save(image_path)
        wb = load_workbook(self.source)
        wb["KHAI THUE"].add_image(Image(str(image_path)), "B1")
        wb.save(self.source)
        process_file(self.source, self.output)
        wb = load_workbook(self.output)
        self.assertEqual(len(wb["KHAI THUE"]._images), 1)
        self.assertEqual(wb.sheetnames, ["KHAI THUE"])

    def test_macro_source_sheets_and_unused_shared_strings_removed(self):
        self.fixture()
        with zipfile.ZipFile(self.source) as z:
            members = {name: z.read(name) for name in z.namelist()}
        k = E.fromstring(members["xl/worksheets/sheet1.xml"])
        cell = k.find(".//" + Q + "c[@r='A1']")
        for child in list(cell):
            cell.remove(child)
        cell.set("t", "s")
        E.SubElement(cell, Q + "v").text = "0"
        E.SubElement(k, Q + "drawing", {"{" + REL + "}id": "rIdButton"})
        members["xl/worksheets/sheet1.xml"] = E.tostring(k)
        sst = E.Element(Q + "sst", nsmap={None: NS}, count="2", uniqueCount="2")
        for text in ["Tiêu đề khai thuế", "ONLY_SOURCE_SECRET_93749"]:
            E.SubElement(E.SubElement(sst, Q + "si"), Q + "t").text = text
        members["xl/sharedStrings.xml"] = E.tostring(sst)
        d = E.fromstring(members["xl/worksheets/sheet2.xml"])
        row = d.find("./" + Q + "sheetData/" + Q + "row[@r='2']")
        E.SubElement(E.SubElement(row, Q + "c", r="AZ2", t="s"), Q + "v").text = "1"
        members["xl/worksheets/sheet2.xml"] = E.tostring(d)
        members["xl/vbaProject.bin"] = b"MACRO_SECRET_84736"
        members["xl/drawings/drawing1.xml"] = b'<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing"><xdr:twoCellAnchor><xdr:sp macro="RunMacro"/></xdr:twoCellAnchor></xdr:wsDr>'
        members["xl/worksheets/_rels/sheet1.xml.rels"] = f'<Relationships xmlns="{PKG}"><Relationship Id="rIdButton" Type="{REL}/drawing" Target="../drawings/drawing1.xml"/></Relationships>'.encode()
        self.source = self.root / "input.xlsm"
        with zipfile.ZipFile(self.source, "w", zipfile.ZIP_DEFLATED) as z:
            for name, blob in members.items():
                z.writestr(name, blob)
        process_file(self.source, self.output)
        wb = load_workbook(self.output)
        self.assertEqual(wb.sheetnames, ["KHAI THUE"])
        self.assertEqual(wb["KHAI THUE"]["A1"].value, "Tiêu đề khai thuế")
        with zipfile.ZipFile(self.output) as z:
            self.assertNotIn("xl/vbaProject.bin", z.namelist())
            self.assertNotIn("xl/drawings/drawing1.xml", z.namelist())
            for name in z.namelist():
                self.assertNotIn(b"ONLY_SOURCE_SECRET_93749", z.read(name))
                self.assertNotIn(b"MACRO_SECRET_84736", z.read(name))
            self.assertNotIn(b"macroEnabled", z.read("[Content_Types].xml"))
            self.assertNotIn(b"RunMacro", z.read("xl/worksheets/sheet1.xml"))


if __name__ == "__main__":
    unittest.main(verbosity=2)
