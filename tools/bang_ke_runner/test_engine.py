"""Independent workbook fixtures and reconciliation tests (not shipped in exe)."""
from pathlib import Path
import tempfile
import unittest
import zipfile

from openpyxl import Workbook, load_workbook

from engine import process_file, datekey, invoice, taxid, rate, allocate, missing


KCOLS = dict(model=3, series=4, number=5, date=6, vendor=7, tax_id=8, item=9, unit=10, qty=11, price=12, amount=13, rate=14, vat=15)
DCOLS = dict(model=11, series=12, number=13, date=14, vendor=19, tax_id=20, item=23, unit=24, qty=25, price=26, goods=27, amount=28, rate=29, vat=30)
HEADERS = dict(model="Mẫu số", series="Ký hiệu", number="Số hóa đơn", date="Ngày hóa đơn", vendor="Tên người bán", tax_id="MST người bán", item="Diễn giải", unit="Đơn vị tính", qty="Số lượng", price="Đơn giá", goods="Tổng tiền hàng", amount="Doanh số bán chưa thuế", rate="Thuế suất", vat="Thuế GTGT")


def rec(num="0001", **kwargs):
    return dict(model=1, series="C26ABC", number=num, date="18/11/2026", vendor="Nhà cung cấp", tax_id="0012345678", **kwargs)


def make_file(path, khai, detail, summary, formulas=False):
    wb = Workbook()
    wb.remove(wb.active)
    for name, cols, records, start in (("KHAI THUE", KCOLS, khai, 8), ("chi tiet hoa don", DCOLS, detail, 2), ("tong hop hoa don", DCOLS, summary, 2)):
        ws = wb.create_sheet(name)
        for f, col in cols.items():
            label = HEADERS[f]
            if name == "KHAI THUE" and f == "amount":
                label = "Giá trị HHDV mua vào chưa có thuế GTGT"
            ws.cell(4 if name == "KHAI THUE" else 1, col, label)
        for r, record in enumerate(records, start):
            for f, value in record.items():
                if f in cols:
                    ws.cell(r, cols[f], value)
        if name == "KHAI THUE":
            ws["A1"] = "Mẫu kiểm thử"
            ws.merge_cells("B2:P2")
            if formulas:
                r = start + len(records)
                ws.cell(r, 13, f"=SUM(M8:M{r-1})")
                ws.cell(r, 15, f"=SUM(O8:O{r-1})")
    wb.save(path)


class EngineTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.root = Path(self.temp.name)

    def tearDown(self):
        self.temp.cleanup()

    def run_file(self, khai, detail, summary, formulas=False):
        src, out = self.root / "vào.xlsx", self.root / "kết quả.xlsx"
        make_file(src, khai, detail, summary, formulas)
        original = src.read_bytes()
        report = process_file(src, out)
        self.assertEqual(src.read_bytes(), original)
        return load_workbook(out, data_only=True), report, out

    def test_exact_fill_and_source_priority(self):
        k = rec(item="Gốc", amount=100, vat=10)
        d = rec(item="Chi tiết", unit="Cái", qty="1.235", price="80.5", amount=100, vat=10, rate="10%")
        s = rec(item="Tổng hợp", amount=100, vat=10)
        wb, report, _ = self.run_file([k], [d], [s])
        ws = wb["KHAI THUE"]
        self.assertEqual(ws["I8"].value, "Chi tiết")
        self.assertEqual(ws["K8"].value, 1.24)
        self.assertEqual(ws["L8"].value, 81)
        self.assertEqual(ws["M8"].value, 100)
        self.assertEqual(ws["N8"].value, 10)
        self.assertEqual(report["stats"]["exact_matches"], 1)
        self.assertEqual(report["status"], "THANH_CONG")

    def test_split_and_preserve_totals_and_cached_sum(self):
        k = rec(item="Tổng", amount=101, vat=10, rate=10)
        ds = [rec(item="A", qty=1, price=50, amount=50.4, vat=5.2, rate=10), rec(item="B", qty=1, price=50, amount=50.4, vat=5.2, rate=10)]
        wb, report, out = self.run_file([k], ds, [rec(item="Tổng", amount=100.8, vat=10.4)], True)
        ws = wb["KHAI THUE"]
        self.assertEqual([ws[f"I{r}"].value for r in (8, 9)], ["A", "B"])
        self.assertEqual([ws[f"M{r}"].value for r in (8, 9)], [50, 51])
        self.assertEqual([ws[f"O{r}"].value for r in (8, 9)], [5, 5])
        self.assertEqual(ws["M10"].value, 101)
        self.assertEqual(ws["O10"].value, 10)
        self.assertEqual(report["stats"]["added_rows"], 1)
        fw = load_workbook(out, data_only=False)
        self.assertEqual(fw["KHAI THUE"]["M10"].value, "=SUM(M8:M9)")
        self.assertEqual(fw["KHAI THUE"]["K9"].number_format, "#,##0.00")
        self.assertEqual(str(fw["KHAI THUE"].merged_cells), "B2:P2")

    def test_no_duplicate_on_second_run(self):
        ds = [rec(item="A", qty=1, price=50, amount=50, vat=5, rate=10), rec(item="B", qty=1, price=50, amount=50, vat=5, rate=10)]
        _, first, out = self.run_file([rec(item="Tổng", amount=100, vat=10, rate=10)], ds, [rec(amount=100, vat=10)])
        again = self.root / "again.xlsx"
        second = process_file(out, again)
        self.assertEqual(second["stats"].get("added_rows", 0), 0)
        self.assertEqual(second["stats"]["already_expanded"], 1)
        self.assertEqual(second["stats"]["data_rows"], 2)

    def test_partial_expansion_is_blocked(self):
        ds = [rec(item="A", unit="Cái", qty=1, price=50, amount=50, vat=5, rate=10), rec(item="B", unit="Cái", qty=1, price=50, amount=50, vat=5, rate=10)]
        k = rec(item="A", unit="Cái", qty=1, price=50, amount=100, vat=10, rate=10)
        wb, report, _ = self.run_file([k], ds, [rec(amount=100, vat=10)])
        self.assertEqual(report["stats"].get("added_rows", 0), 0)
        self.assertEqual(report["stats"]["ambiguous_groups"], 1)
        self.assertEqual(wb["NHAT_KY_DOI_CHIEU"]["F2"].value, "NHOM_DA_BUNG_DO_KHONG_APPEND_LAI")

    def test_date_fallback_same_year_and_reject_cross_year(self):
        for day, match in (("17/11/2026", 1), ("18/11/2025", 0)):
            k = rec(item="Gốc", amount=100, vat=10)
            k["date"] = day
            wb, report, _ = self.run_file([k], [rec(item="Mới", amount=100, vat=10)], [rec(amount=100, vat=10)])
            self.assertEqual(report["stats"].get("fallback_matches", 0), match)
            self.assertEqual(wb["KHAI THUE"]["I8"].value, "Mới" if match else "Gốc")

    def test_series_fallback(self):
        k = rec(amount=100, vat=10)
        k["series"] = "SAI"
        _, report, _ = self.run_file([k], [rec(item="Mới", amount=100, vat=10)], [rec(amount=100, vat=10)])
        self.assertEqual(report["stats"]["fallback_matches"], 1)

    def test_summary_only_and_placeholder(self):
        k = rec(item="Gốc", amount=100, vat=0)
        ds = [rec(num="other", amount=5, vat=0)]
        summary = [rec(item="Từ tổng hợp", unit="1916", qty=1916, price=2, amount=100, vat=0)]
        wb, report, _ = self.run_file([k], ds, summary)
        self.assertEqual(wb["KHAI THUE"]["I8"].value, "Từ tổng hợp")
        self.assertIsNone(wb["KHAI THUE"]["J8"].value)
        self.assertEqual(wb["KHAI THUE"]["K8"].value, 1916)
        self.assertEqual(report["status"], "HOAN_TAT_CO_CANH_BAO")

    def test_multiple_rates_assignment(self):
        ks = [rec(item="Tổng 8", amount=100, vat=8, rate=8), rec(item="Tổng 10", amount=200, vat=20, rate=10)]
        ds = [rec(item="A", amount=40, vat=3.2, rate=8), rec(item="B", amount=60, vat=4.8, rate=8), rec(item="C", amount=200, vat=20, rate=10)]
        wb, report, _ = self.run_file(ks, ds, [rec(amount=300, vat=28)])
        self.assertEqual(report["stats"]["data_rows"], 3)
        self.assertEqual(report["stats"]["total_amount"], 300)
        self.assertEqual(report["stats"]["total_vat"], 28)
        self.assertEqual([wb["KHAI THUE"][f"N{r}"].value for r in (8, 9, 10)], [8, 8, 10])

    def test_same_rate_duplicate_and_missing_amount_blocked(self):
        for ks in ([rec(amount=50, vat=5, rate=10), rec(amount=50, vat=5, rate=10)], [rec(amount=None, vat=10, rate=10)]):
            ds = [rec(item=str(i), amount=100/3, vat=10/3, rate=10) for i in range(3)]
            _, report, _ = self.run_file(ks, ds, [rec(amount=100, vat=10)])
            self.assertEqual(report["stats"].get("added_rows", 0), 0)
            self.assertEqual(report["stats"]["ambiguous_groups"], 1)

    def test_missing_m_o_in_exact_one_to_one_filled(self):
        wb, report, _ = self.run_file([rec(item="Gốc")], [rec(item="Mới", amount=100.5, vat=10.5)], [rec(amount=100.5, vat=10.5)])
        self.assertEqual(wb["KHAI THUE"]["M8"].value, 101)
        self.assertEqual(wb["KHAI THUE"]["O8"].value, 11)
        self.assertEqual(report["expected_amount_increase"], 101)

    def test_negative_values_and_rounding(self):
        ds = [rec(item="A", amount=-50.5, vat=-5.5, qty=-1.235, price=-50.5), rec(item="B", amount=-49.5, vat=-4.5)]
        wb, report, _ = self.run_file([rec(amount=-100, vat=-10)], ds, [rec(amount=-100, vat=-10)])
        self.assertEqual(report["stats"]["total_amount"], -100)
        self.assertEqual(report["stats"]["total_vat"], -10)
        self.assertEqual(wb["KHAI THUE"]["K8"].value, -1.24)
        self.assertEqual(wb["KHAI THUE"]["L8"].value, -51)

    def test_source_and_macro_members_unchanged(self):
        src = self.root / "source.xlsm"
        make_file(src, [rec(amount=100, vat=10)], [rec(item="Mới", amount=100, vat=10)], [rec(amount=100, vat=10)])
        with zipfile.ZipFile(src, "a") as z:
            z.writestr("xl/vbaProject.bin", b"fixture_macro_bytes")
            z.writestr("customXml/item1.xml", b"<original>keep</original>")
        out = self.root / "out.xlsm"
        process_file(src, out)
        with zipfile.ZipFile(src) as a, zipfile.ZipFile(out) as b:
            for name in ("xl/vbaProject.bin", "customXml/item1.xml", "xl/worksheets/sheet2.xml", "xl/worksheets/sheet3.xml"):
                self.assertEqual(a.read(name), b.read(name))

    def test_ambiguous_date_candidates_not_matched(self):
        d1, d2 = rec(item="A", amount=100, vat=10), rec(item="B", amount=100, vat=10)
        d1["date"], d2["date"] = "17/11/2026", "19/11/2026"
        wb, report, _ = self.run_file([rec(item="Gốc", amount=100, vat=10)], [d1, d2], [rec(amount=100, vat=10)])
        self.assertEqual(report["stats"].get("fallback_matches", 0), 0)
        self.assertEqual(report["stats"]["unmatched_groups"], 1)
        self.assertEqual(wb["KHAI THUE"]["I8"].value, "Gốc")

    def test_source_goods_selected_when_closer(self):
        ds = [rec(item="A", goods=40, amount=39, vat=4), rec(item="B", goods=60, amount=59, vat=6)]
        wb, report, _ = self.run_file([rec(amount=100, vat=10)], ds, [rec(goods=100, amount=98, vat=10)])
        self.assertEqual([wb["KHAI THUE"][f"M{r}"].value for r in (8, 9)], [40, 60])
        self.assertEqual(report["stats"]["total_amount"], 100)

    def test_two_insertions_move_footer_and_external_sheet_reference(self):
        src, out = self.root / "in.xlsx", self.root / "out.xlsx"
        ks = [rec(num="1", amount=100, vat=10), rec(num="2", amount=200, vat=20)]
        ds = [rec(num="1", item="A", amount=50, vat=5), rec(num="1", item="B", amount=50, vat=5), rec(num="2", item="C", amount=100, vat=10), rec(num="2", item="D", amount=100, vat=10)]
        ss = [rec(num="1", amount=100, vat=10), rec(num="2", amount=200, vat=20)]
        make_file(src, ks, ds, ss, True)
        wb = load_workbook(src)
        ws = wb.create_sheet("Phu")
        ws["A1"] = "='KHAI THUE'!M9"
        ws["A2"] = "=SUM('KHAI THUE'!M8:M9)"
        wb.save(src)
        report = process_file(src, out)
        wb = load_workbook(out, data_only=False)
        self.assertEqual(wb["KHAI THUE"]["M12"].value, "=SUM(M8:M11)")
        self.assertEqual(wb["Phu"]["A1"].value, "='KHAI THUE'!M10")
        self.assertEqual(wb["Phu"]["A2"].value, "=SUM('KHAI THUE'!M8:M10)")
        values = load_workbook(out, data_only=True)
        self.assertEqual(values["Phu"]["A1"].value, 100)
        self.assertEqual(report["stats"]["data_rows"], 4)

    def test_formula_quantity_preserved_and_logs_are_typed(self):
        src, out = self.root / "in.xlsx", self.root / "out.xlsx"
        make_file(src, [rec(amount=100, vat=10)], [rec(qty=7, price=50, amount=100, vat=10)], [rec(amount=100, vat=10)])
        wb = load_workbook(src)
        wb["KHAI THUE"]["K8"] = "=ROUND(1.235,2)"
        wb.save(src)
        process_file(src, out)
        wb = load_workbook(out, data_only=False)
        self.assertEqual(wb["KHAI THUE"]["K8"].value, "=ROUND(1.235,2)")
        wb = load_workbook(out, data_only=True)
        self.assertEqual(wb["KHAI THUE"]["K8"].value, 1.24)
        import datetime
        self.assertIsInstance(wb["NHAT_KY_VBA"]["A2"].value, datetime.datetime)
        self.assertEqual(wb["NHAT_KY_VBA"].sheet_state, "visible")

    def test_protected_sheet_and_output_extension_rejected(self):
        src, out = self.root / "in.xlsx", self.root / "out.xlsx"
        make_file(src, [rec(amount=100, vat=10)], [rec(amount=100, vat=10)], [rec(amount=100, vat=10)])
        with self.assertRaisesRegex(Exception, "đuôi"):
            process_file(src, self.root / "out.xlsm")
        wb = load_workbook(src)
        wb["KHAI THUE"].protection.sheet = True
        wb.save(src)
        with self.assertRaisesRegex(Exception, "bảo vệ"):
            process_file(src, out)
        self.assertFalse(out.exists())

    def test_invalid_inputs_do_not_publish_output(self):
        src, out = self.root / "in.xlsx", self.root / "out.xlsx"
        wb = Workbook()
        wb.save(src)
        with self.assertRaises(Exception):
            process_file(src, out)
        self.assertFalse(out.exists())

    def test_unsupported_total_fails_explicitly(self):
        src, out = self.root / "in.xlsx", self.root / "out.xlsx"
        make_file(src, [rec(amount=100, vat=10)], [rec(item="Mới", amount=100, vat=10)], [rec(amount=100, vat=10)])
        wb = load_workbook(src)
        wb["KHAI THUE"]["M9"] = "=SUMIFS(M8:M8,E8:E8,E8)"
        wb.save(src)
        with self.assertRaisesRegex(Exception, "công thức"):
            process_file(src, out)
        self.assertFalse(out.exists())

    def test_normalization_and_allocation(self):
        self.assertEqual(invoice("000001"), "1")
        self.assertEqual(taxid("00123-456"), "123456")
        self.assertEqual(datekey("20261118"), "20261118")
        self.assertEqual(datekey("2026-11-18"), "20261118")
        self.assertEqual(datekey("31/02/2026"), "")
        self.assertEqual(rate(0.08), "8")
        self.assertEqual(rate("8%"), "8")
        self.assertTrue(missing("1916"))
        self.assertFalse(missing(1916))
        self.assertEqual(sum(allocate([33.4, 33.4, 33.4], 100)), 100)


if __name__ == "__main__":
    unittest.main(verbosity=2)
