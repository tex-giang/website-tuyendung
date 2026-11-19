"""Regression checks specific to pandas grouping and compact XML reads."""
from pathlib import Path
import tempfile
import unittest
import zipfile

from lxml import etree as E
from openpyxl import load_workbook

from engine import Layout, groups, sum_field, missing, number
from ooxml import Book, Q
from test_engine import make_file, rec, process_file


class OptimizationTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.root = Path(self.temp.name)
        self.src, self.out = self.root / "input.xlsx", self.root / "output.xlsx"

    def tearDown(self):
        self.temp.cleanup()

    def fixture(self, details=None):
        make_file(self.src, [rec(item="Gốc", amount=100, vat=10)], details or [rec(item="Chi tiết", amount=100, vat=10)], [rec(amount=100, vat=10)])

    def patch_detail(self, callback):
        with zipfile.ZipFile(self.src) as z:
            members = {n: z.read(n) for n in z.namelist()}
        tree = E.fromstring(members["xl/worksheets/sheet2.xml"])
        callback(tree)
        members["xl/worksheets/sheet2.xml"] = E.tostring(tree, encoding="utf-8")
        with zipfile.ZipFile(self.src, "w", zipfile.ZIP_DEFLATED) as z:
            for name, blob in members.items():
                z.writestr(name, blob)

    def test_blank_style_xml_unchanged_in_output(self):
        self.fixture()
        def patch(tree):
            row = tree.find("./" + Q + "sheetData/" + Q + "row[@r='2']")
            E.SubElement(row, Q + "c", r="ZZ2", s="0")
            E.SubElement(row, Q + "c", r="AAA2", s="0").text = "  "
        self.patch_detail(patch)
        process_file(self.src, self.out)
        with zipfile.ZipFile(self.src) as a, zipfile.ZipFile(self.out) as b:
            self.assertEqual(a.read("xl/worksheets/sheet2.xml"), b.read("xl/worksheets/sheet2.xml"))

    def test_cdata_that_looks_like_blank_cell_retained(self):
        self.fixture()
        text = '<c r="A999" s="0"/> literal nội dung'
        def patch(tree):
            e = tree.find(".//" + Q + "c[@r='W2']/" + Q + "is/" + Q + "t")
            e.text = E.CDATA(text)
        self.patch_detail(patch)
        process_file(self.src, self.out)
        wb = load_workbook(self.out, read_only=True, data_only=True)
        self.assertEqual(wb["KHAI THUE"]["I8"].value, text)
        wb.close()

    def test_comments_and_namespace_prefixes(self):
        self.fixture()
        def patch(tree):
            tree.insert(0, E.Comment('<c r="A999"/> keep comment'))
        self.patch_detail(patch)
        process_file(self.src, self.out)
        with zipfile.ZipFile(self.out) as z:
            self.assertIn(b'<!--<c r="A999"/> keep comment-->', z.read("xl/worksheets/sheet2.xml"))

    def test_pandas_retains_typed_identifiers_and_order(self):
        details = []
        for value in (True, 1, "0001", "other"):
            r = rec(item="A", amount=25, vat=2.5)
            r["number"] = value
            details.append(r)
        self.fixture(details)
        book = Book(self.src)
        try:
            d = Layout(book.sheets["chi tiet hoa don"], "D")
            result = groups(d)
            self.assertEqual(list(result.values()), [[2], [3, 4], [5]])
            self.assertIn("|TRUE|", list(result)[0])
            self.assertIn("|1|", list(result)[1])
        finally:
            book.close()

    def test_group_totals_preserve_vba_order_and_placeholder_type(self):
        details = [rec(item="A", amount=v, vat=0) for v in (1e16, 1, 1, -1e16, "1916", 1916)]
        self.fixture(details)
        book = Book(self.src)
        try:
            d = Layout(book.sheets["chi tiet hoa don"], "D")
            rs = next(iter(groups(d).values()))
            values = [d.get(r, "amount") for r in rs]
            expected = sum(number(v) for v in values if not missing(v))
            self.assertEqual(sum_field(d, rs, "amount"), (expected, True))
            self.assertEqual(sum_field(d, rs[:4], "amount"), (sum(number(v) for v in values[:4]), True))
        finally:
            book.close()

    def test_group_cache_invalidated_on_cell_change(self):
        self.fixture()
        book = Book(self.src)
        try:
            d = Layout(book.sheets["chi tiet hoa don"], "D")
            rs = next(iter(groups(d).values()))
            self.assertEqual(sum_field(d, rs, "amount"), (100.0, True))
            d.set(rs[0], "amount", 250)
            self.assertEqual(sum_field(d, rs, "amount"), (250.0, True))
            d.set(rs[0], "number", "new")
            self.assertIn("|NEW|", next(iter(groups(d))))
        finally:
            book.close()

    def test_many_split_groups(self):
        ks, ds, ss = [], [], []
        for i in range(120):
            num = str(i + 1)
            ks.append(rec(num=num, item="Gốc", amount=100, vat=10, rate=10))
            ss.append(rec(num=num, amount=100, vat=10))
            for j in range(5):
                ds.append(rec(num=num, item=f"Dòng {j+1}", amount=20, vat=2, rate=10, qty=1, price=20))
        make_file(self.src, ks, ds, ss, True)
        result = process_file(self.src, self.out)
        self.assertEqual(result["stats"]["expanded_groups"], 120)
        self.assertEqual(result["stats"]["added_rows"], 480)
        self.assertEqual(result["stats"]["data_rows"], 600)
        self.assertEqual(result["stats"]["total_amount"], 12000)
        self.assertEqual(result["stats"]["total_vat"], 1200)


if __name__ == "__main__":
    unittest.main(verbosity=2)
