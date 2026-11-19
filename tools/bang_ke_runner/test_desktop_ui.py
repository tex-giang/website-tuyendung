import os
os.environ['QT_QPA_PLATFORM'] = 'offscreen'

from pathlib import Path
import tempfile
import time
import unittest
from unittest.mock import patch

from openpyxl import load_workbook
from PySide6.QtTest import QTest

from desktop_ui import MainWindow, create_application
from templates import template_path
from test_templates import fill_sample_template


class DesktopTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.app = create_application()

    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.root = Path(self.temp.name)
        self.window = MainWindow()
        self.window.show()
        self.app.processEvents()

    def tearDown(self):
        self.wait_finished()
        self.window.close()
        self.window.deleteLater()
        self.app.processEvents()
        self.temp.cleanup()

    def wait_finished(self):
        deadline = time.monotonic() + 30
        while self.window.busy and time.monotonic() < deadline:
            QTest.qWait(10)
        self.assertFalse(self.window.busy, 'Background job did not finish')

    def source(self):
        src = self.root / 'Dữ liệu thử.xlsx'
        fill_sample_template(src)
        self.assertTrue(self.window.select_source(src))
        return src

    def test_real_processing_remains_responsive_and_exports_one_sheet(self):
        src = self.source()
        before = src.read_bytes()
        dest = self.root / 'Kết quả.xlsx'
        reports = []
        self.window.completed.connect(reports.append)
        self.assertTrue(self.window.start_job(dest))
        self.assertFalse(self.window.run_btn.isEnabled())
        self.assertFalse(self.window.start_job(self.root / 'duplicate.xlsx'))
        # Main-thread events can still toggle details while the worker runs.
        self.window.details_btn.click()
        self.assertTrue(self.window.log.isVisible())
        self.window.close()
        self.assertTrue(self.window.isVisible())
        self.wait_finished()
        self.assertEqual(len(reports), 1)
        self.assertEqual(reports[0]['stats']['data_rows'], 2)
        self.assertEqual(reports[0]['stats']['total_amount'], 100)
        self.assertTrue(self.window.run_btn.isEnabled())
        self.assertTrue(self.window.open_btn.isEnabled())
        wb = load_workbook(dest, read_only=True, data_only=True)
        self.assertEqual(wb.sheetnames, ['KHAI THUE'])
        self.assertEqual(wb.active['E8'].value, '00012345')
        wb.close()
        self.assertEqual(src.read_bytes(), before)

    def test_processing_error_restores_controls_and_clears_previous_output(self):
        src = self.root / 'broken.xlsx'
        src.write_bytes(b'not a workbook')
        self.window.select_source(src)
        failures = []
        self.window.job_failed.connect(failures.append)
        dest = self.root / 'result.xlsx'
        self.window.start_job(dest)
        self.wait_finished()
        self.assertTrue(failures)
        self.assertFalse(dest.exists())
        self.assertFalse(self.window.open_btn.isEnabled())
        self.assertTrue(self.window.template_btn.isEnabled())
        self.assertTrue(self.window.run_btn.isEnabled())
        self.assertTrue(self.window.log.isVisible())

    def test_cancel_and_validation_do_not_start_or_overwrite_input(self):
        self.assertFalse(self.window.run_btn.isEnabled())
        self.assertFalse(self.window.select_source(self.root / 'missing.xlsx'))
        src = self.source()
        before = src.read_bytes()
        with patch('desktop_ui.QFileDialog.getSaveFileName', return_value=('', '')):
            self.window.choose_output()
        self.assertFalse(self.window.busy)
        self.assertIsNone(self.window.thread)
        self.assertFalse(self.window.start_job(src))
        self.assertFalse(self.window.start_job(self.root / 'wrong.xlsm'))
        self.assertEqual(src.read_bytes(), before)

    def test_download_template_and_protect_selected_source(self):
        dest = self.root / 'Mẫu.xlsx'
        with patch('desktop_ui.QFileDialog.getSaveFileName', return_value=(str(dest), '')) as dialog:
            self.window.download_template()
        self.assertEqual(Path(dialog.call_args.args[2]).name, 'template.xlsx')
        self.assertEqual(dest.read_bytes(), template_path().read_bytes())
        self.assertIn('dòng 8', self.window.log.toPlainText())
        src = self.source()
        before = src.read_bytes()
        with patch('desktop_ui.QFileDialog.getSaveFileName', return_value=(str(src), '')):
            self.window.download_template()
        self.assertEqual(src.read_bytes(), before)


if __name__ == '__main__':
    unittest.main()
