"""Compact Qt desktop UI. All workbook processing runs outside the UI thread."""
from __future__ import annotations

import datetime as dt
import os
from pathlib import Path
import subprocess
import sys

from PySide6.QtCore import QObject, Qt, QThread, QTimer, Signal, Slot
from PySide6.QtGui import QColor, QFont, QFontDatabase, QIcon, QPainter, QPixmap
from PySide6.QtWidgets import (
    QApplication, QFileDialog, QFrame, QHBoxLayout, QLabel, QMainWindow,
    QPlainTextEdit, QProgressBar, QPushButton, QSizePolicy, QVBoxLayout, QWidget,
)

from engine import VERSION, process_file
from templates import save_template, TEMPLATE_NAME


STYLE = """
QWidget { font-family: 'Segoe UI'; font-size: 12px; color: #24324b; }
QMainWindow, QWidget#surface { background: #f5f7fb; }
QLabel { background: transparent; }
QLabel#brand { background: #2563eb; color: white; border-radius: 12px;
    font-size: 18px; font-weight: 700; }
QLabel#title { font-size: 21px; font-weight: 700; color: #15223b; }
QLabel#subtitle, QLabel#hint, QLabel#footer { color: #778399; font-size: 11px; }
QLabel#filename { font-size: 14px; font-weight: 600; }
QFrame#fileCard { background: white; border: 1px dashed #c3cfe3; border-radius: 14px; }
QFrame#fileCard[chosen="true"] { border: 1px solid #b8ccef; background: #ffffff; }
QLabel#fileIcon { background: #eaf1ff; color: #2563eb; border-radius: 10px;
    font-size: 11px; font-weight: 700; }
QLabel#result { background: #eaf7f0; color: #18734a; border-radius: 10px;
    padding: 10px 12px; font-weight: 600; }
QLabel#result[warning="true"] { background: #fff4df; color: #926015; }
QPushButton { background: white; border: 1px solid #dce3ef; border-radius: 8px;
    padding: 8px 12px; font-weight: 600; }
QPushButton:hover { background: #edf3ff; border-color: #b8cbef; }
QPushButton:pressed { background: #dfeaff; }
QPushButton:disabled { color: #a9b1bf; border-color: #e3e8f0; background: #f4f6fa; }
QPushButton#primary { background: #2563eb; color: white; border: none;
    border-radius: 10px; font-size: 14px; padding: 13px; }
QPushButton#primary:hover { background: #1d54d5; }
QPushButton#primary:pressed { background: #1947b5; }
QPushButton#primary:disabled { background: #b8c9e9; color: white; }
QPushButton#details { border: none; background: transparent; color: #64748b;
    padding-left: 0; text-align: left; font-weight: 400; }
QPushButton#details:hover { color: #2563eb; }
QProgressBar { background: #e4eaf4; border: none; border-radius: 3px; }
QProgressBar::chunk { background: #2563eb; border-radius: 3px; }
QPlainTextEdit { background: white; border: 1px solid #dce3ef; border-radius: 9px;
    padding: 9px; font-size: 11px; selection-background-color: #dbe8ff; }
"""


def app_icon():
    pix = QPixmap(64, 64)
    pix.fill(Qt.GlobalColor.transparent)
    painter = QPainter(pix)
    painter.setRenderHint(QPainter.RenderHint.Antialiasing)
    painter.setPen(Qt.PenStyle.NoPen)
    painter.setBrush(QColor('#2563eb'))
    painter.drawRoundedRect(0, 0, 64, 64, 15, 15)
    painter.setPen(QColor('white'))
    painter.setFont(QFont('Segoe UI', 24, QFont.Weight.Bold))
    painter.drawText(pix.rect(), Qt.AlignmentFlag.AlignCenter, 'BK')
    painter.end()
    return QIcon(pix)


class Worker(QObject):
    progress = Signal(str, int)
    succeeded = Signal(dict)
    failed = Signal(str)
    finished = Signal()

    def __init__(self, source, destination):
        super().__init__()
        self.source, self.destination = source, destination

    @Slot()
    def run(self):
        try:
            result = process_file(self.source, self.destination, self.progress.emit)
            self.succeeded.emit(result)
        except Exception as exc:
            self.failed.emit(str(exc))
        finally:
            self.finished.emit()


class MainWindow(QMainWindow):
    completed = Signal(dict)
    job_failed = Signal(str)

    def __init__(self):
        super().__init__()
        self.source = None
        self.output = None
        self.busy = False
        self.thread = None
        self.worker = None
        self.setWindowTitle('Bảng kê hóa đơn')
        self.setWindowIcon(app_icon())
        self.setAcceptDrops(True)
        self.resize(560, 480)
        self.setMinimumSize(520, 480)
        surface = QWidget(objectName='surface')
        self.setCentralWidget(surface)
        layout = QVBoxLayout(surface)
        layout.setContentsMargins(24, 22, 24, 16)
        layout.setSpacing(14)

        header = QHBoxLayout()
        header.setSpacing(11)
        brand = QLabel('BK', objectName='brand')
        brand.setAlignment(Qt.AlignmentFlag.AlignCenter)
        brand.setFixedSize(44, 44)
        header.addWidget(brand)
        heading = QVBoxLayout()
        heading.setSpacing(2)
        heading.addWidget(QLabel('Bảng kê hóa đơn', objectName='title'))
        heading.addWidget(QLabel('Đối chiếu & xuất bảng khai thuế', objectName='subtitle'))
        header.addLayout(heading, 1)
        self.template_btn = QPushButton('Tải template')
        self.template_btn.setToolTip('Lưu template.xlsx nguyên bản có đủ 3 sheet')
        self.template_btn.clicked.connect(self.download_template)
        header.addWidget(self.template_btn)
        layout.addLayout(header)

        self.card = QFrame(objectName='fileCard')
        card_layout = QVBoxLayout(self.card)
        card_layout.setContentsMargins(18, 16, 18, 16)
        card_layout.setSpacing(12)
        file_row = QHBoxLayout()
        file_row.setSpacing(12)
        file_icon = QLabel('XLSX', objectName='fileIcon')
        file_icon.setFixedSize(44, 44)
        file_icon.setAlignment(Qt.AlignmentFlag.AlignCenter)
        file_row.addWidget(file_icon)
        text = QVBoxLayout()
        text.setSpacing(3)
        self.filename = QLabel('Kéo tệp Excel vào đây', objectName='filename')
        self.filename.setMinimumWidth(0)
        self.filename.setSizePolicy(QSizePolicy.Policy.Ignored, QSizePolicy.Policy.Preferred)
        self.file_hint = QLabel('Hỗ trợ .xlsx và .xlsm', objectName='hint')
        text.addWidget(self.filename)
        text.addWidget(self.file_hint)
        file_row.addLayout(text, 1)
        self.browse_btn = QPushButton('Chọn tệp')
        self.browse_btn.clicked.connect(self.browse)
        file_row.addWidget(self.browse_btn)
        card_layout.addLayout(file_row)
        sheet_hint = QLabel('3 sheet: KHAI THUE · chi tiet hoa don · tong hop hoa don', objectName='hint')
        sheet_hint.setWordWrap(True)
        card_layout.addWidget(sheet_hint)
        layout.addWidget(self.card)

        output_hint = QLabel('Kết quả: 1 tệp Excel chỉ gồm sheet KHAI THUE', objectName='hint')
        layout.addWidget(output_hint)
        self.run_btn = QPushButton('RUN  ·  Xử lý bảng kê', objectName='primary')
        self.run_btn.setEnabled(False)
        self.run_btn.clicked.connect(self.choose_output)
        layout.addWidget(self.run_btn)
        status_box = QVBoxLayout()
        status_box.setSpacing(8)
        self.progress = QProgressBar()
        self.progress.setRange(0, 100)
        self.progress.setValue(0)
        self.progress.setTextVisible(False)
        self.progress.setFixedHeight(6)
        status_box.addWidget(self.progress)
        self.status = QLabel('Chọn tệp Excel để bắt đầu.')
        self.status.setWordWrap(True)
        self.status.setMinimumHeight(32)
        status_box.addWidget(self.status)
        layout.addLayout(status_box)
        self.result = QLabel(objectName='result')
        self.result.setWordWrap(True)
        self.result.hide()
        layout.addWidget(self.result)
        layout.addStretch(1)
        actions = QHBoxLayout()
        self.details_btn = QPushButton('+  Chi tiết xử lý', objectName='details')
        self.details_btn.setCheckable(True)
        self.details_btn.toggled.connect(self.toggle_details)
        actions.addWidget(self.details_btn)
        actions.addStretch(1)
        self.open_btn = QPushButton('Mở nơi lưu kết quả')
        self.open_btn.setEnabled(False)
        self.open_btn.clicked.connect(self.open_folder)
        actions.addWidget(self.open_btn)
        layout.addLayout(actions)
        self.log = QPlainTextEdit()
        self.log.setReadOnly(True)
        self.log.setFixedHeight(160)
        self.log.setMaximumBlockCount(1500)
        self.log.setPlaceholderText('Các bước xử lý và cảnh báo sẽ hiển thị ở đây.')
        self.log.hide()
        layout.addWidget(self.log)
        footer = QHBoxLayout()
        footer.addWidget(QLabel('Chạy trên máy · Không cần cài Excel', objectName='footer'))
        footer.addStretch(1)
        footer.addWidget(QLabel(f'v{VERSION}', objectName='footer'))
        layout.addLayout(footer)

    def notify(self, text, error=False):
        self.status.setText(text)
        self.status.setStyleSheet('color: #b42318;' if error else '')
        self.log.appendPlainText(('LỖI: ' if error else '') + text)

    def select_source(self, filename):
        if self.busy:
            return False
        path = Path(filename)
        if not path.is_file() or path.suffix.lower() not in ('.xlsx', '.xlsm'):
            self.notify('Hãy chọn một tệp .xlsx hoặc .xlsm hợp lệ.', error=True)
            return False
        self.source = path.resolve()
        self.output = None
        self.open_btn.setEnabled(False)
        self.result.hide()
        self.progress.setValue(0)
        self.filename.setText(path.name)
        self.filename.setToolTip(str(self.source))
        size = path.stat().st_size / 1048576
        self.file_hint.setText(f'{size:.1f} MB · Đã chọn tệp')
        self.file_hint.setToolTip(str(self.source))
        self.browse_btn.setText('Đổi tệp')
        self.card.setProperty('chosen', True)
        self.card.style().unpolish(self.card)
        self.card.style().polish(self.card)
        self.run_btn.setEnabled(True)
        self.notify('Sẵn sàng. Nhấn RUN để chọn nơi lưu kết quả.')
        return True

    @Slot()
    def browse(self):
        filename, _ = QFileDialog.getOpenFileName(self, 'Chọn Excel chứa đủ 3 sheet',
            str(self.source.parent) if self.source else '', 'Excel (*.xlsx *.xlsm)')
        if filename:
            self.select_source(filename)

    @Slot()
    def download_template(self):
        if self.busy:
            return
        folder = Path.home() / 'Downloads'
        if not folder.is_dir():
            folder = Path.home()
        filename, _ = QFileDialog.getSaveFileName(self, 'Lưu template.xlsx nguyên bản',
            str(folder / TEMPLATE_NAME), 'Excel (*.xlsx)')
        if not filename:
            return
        if not Path(filename).suffix:
            filename += '.xlsx'
        if self.source and Path(filename).resolve() == self.source:
            self.notify('Chọn nơi lưu mẫu khác tệp dữ liệu đang chọn.', error=True)
            return
        try:
            saved = save_template(filename)
            self.notify('Đã lưu mẫu nguyên bản. Nhập dữ liệu vào 3 sheet rồi chọn tệp.')
            self.log.appendPlainText(f'Mẫu: {saved}\nKHAI THUE: nhập từ dòng 8. Hai sheet nguồn: nhập từ dòng 2.')
        except Exception as exc:
            self.notify(str(exc), error=True)

    @Slot()
    def choose_output(self):
        if self.busy or not self.source:
            return
        name = f'{self.source.stem}_KHAI_THUE_{dt.datetime.now():%Y%m%d_%H%M%S}.xlsx'
        filename, _ = QFileDialog.getSaveFileName(self, 'Lưu bảng KHAI THUE',
            str(self.source.parent / name), 'Excel (*.xlsx)')
        if filename:
            if not Path(filename).suffix:
                filename += '.xlsx'
            self.start_job(filename)

    def start_job(self, destination):
        if self.busy or not self.source:
            return False
        dest = Path(destination).resolve()
        if dest == self.source or dest.suffix.lower() != '.xlsx':
            self.notify('Lưu kết quả thành .xlsx với tên khác tệp đầu vào.', error=True)
            return False
        self.busy = True
        self.output = None
        self.result.hide()
        self.open_btn.setEnabled(False)
        self.set_controls(False)
        self.run_btn.setText('Đang xử lý…')
        self.progress.setValue(0)
        self.log.clear()
        self.notify(f'Bắt đầu xử lý: {self.source.name}')
        self.thread = QThread(self)
        self.worker = Worker(str(self.source), str(dest))
        self.worker.moveToThread(self.thread)
        self.thread.started.connect(self.worker.run)
        self.worker.progress.connect(self.on_progress)
        self.worker.succeeded.connect(self.on_success)
        self.worker.failed.connect(self.on_error)
        self.worker.finished.connect(self.thread.quit)
        self.worker.finished.connect(self.worker.deleteLater)
        self.thread.finished.connect(self.on_thread_finished)
        self.thread.finished.connect(self.thread.deleteLater)
        self.thread.start()
        return True

    def set_controls(self, enabled):
        self.run_btn.setEnabled(enabled and self.source is not None)
        self.template_btn.setEnabled(enabled)
        self.browse_btn.setEnabled(enabled)

    @Slot(str, int)
    def on_progress(self, text, value):
        self.progress.setValue(value)
        self.notify(text)

    @Slot(dict)
    def on_success(self, report):
        self.output = Path(report['output'])
        stats = report['stats']
        warning = report['status'] != 'THANH_CONG'
        self.result.setProperty('warning', warning)
        self.result.style().unpolish(self.result)
        self.result.style().polish(self.result)
        self.result.setText(f"{stats.get('data_rows', 0):,} dòng kết quả  ·  "
            f"{stats.get('added_rows', 0):,} dòng được thêm" + ('\nCó cảnh báo — xem Chi tiết xử lý.' if warning else ''))
        self.result.show()
        self.notify('Hoàn tất, có cảnh báo. Đã lưu kết quả.' if warning else 'Hoàn tất. Đã lưu bảng khai thuế.')
        self.log.appendPlainText(
            f"Khớp đầy đủ: {stats.get('exact_matches', 0):,} · Khớp bổ sung: {stats.get('fallback_matches', 0):,}\n"
            f"Nhóm không khớp: {stats.get('unmatched_groups', 0):,} · Nhóm mơ hồ: {stats.get('ambiguous_groups', 0):,}\n"
            f"Tổng trước thuế: {stats.get('total_amount', 0):,.0f} · Thuế: {stats.get('total_vat', 0):,.0f}\n"
            f"Đã lưu: {self.output}")
        for note in report.get('export_notes', []):
            self.log.appendPlainText(str(note))
        for i, issue in enumerate(report.get('issues', []), 1):
            self.log.appendPlainText(f"{i}. {issue['khai_key']} — {issue['reason']}")
        if report.get('issues_truncated'):
            self.log.appendPlainText('Danh sách chi tiết đã đạt giới hạn 500 vấn đề.')
        self.completed.emit(report)

    @Slot(str)
    def on_error(self, message):
        self.progress.setValue(0)
        self.notify(message, error=True)
        self.details_btn.setChecked(True)
        self.job_failed.emit(message)

    @Slot()
    def on_thread_finished(self):
        # finished is emitted before the native thread finishes its deferred
        # QObject deletion. Join before releasing the worker's Python wrapper.
        self.thread.wait()
        self.worker = None
        self.thread = None
        self.busy = False
        self.set_controls(True)
        self.run_btn.setText('RUN  ·  Xử lý bảng kê')
        self.open_btn.setEnabled(self.output is not None)

    @Slot(bool)
    def toggle_details(self, expanded):
        self.log.setVisible(expanded)
        self.details_btn.setText(('−' if expanded else '+') + '  Chi tiết xử lý')
        self.resize(self.width(), max(self.minimumHeight(), self.height() + (174 if expanded else -174)))

    @Slot()
    def open_folder(self):
        if self.output:
            subprocess.Popen(['explorer.exe', '/select,', str(self.output)])

    def dragEnterEvent(self, event):
        urls = event.mimeData().urls()
        if not self.busy and len(urls) == 1 and urls[0].isLocalFile() and Path(urls[0].toLocalFile()).suffix.lower() in ('.xlsx', '.xlsm'):
            event.acceptProposedAction()

    def dropEvent(self, event):
        if not self.busy and self.select_source(event.mimeData().urls()[0].toLocalFile()):
            event.acceptProposedAction()

    def closeEvent(self, event):
        if self.busy:
            self.notify('Đang xử lý. Vui lòng đợi hoàn tất trước khi đóng.')
            event.ignore()
        else:
            event.accept()


def create_application():
    app = QApplication.instance() or QApplication(sys.argv[:1])
    # The headless Qt platform doesn't enumerate Windows fonts. Use fonts from
    # the OS for preview tests; no system font is copied into the release.
    if not QFontDatabase.families():
        fonts = Path(os.environ.get('WINDIR', 'C:/Windows')) / 'Fonts'
        for name in ('segoeui.ttf', 'segoeuib.ttf'):
            QFontDatabase.addApplicationFont(str(fonts / name))
    app.setStyle('Fusion')
    app.setStyleSheet(STYLE)
    app.setFont(QFont('Segoe UI', 10))
    app.setApplicationName('Bảng kê hóa đơn')
    return app


def run_gui(preview=None):
    app = create_application()
    window = MainWindow()
    window.show()
    if preview:
        # Headless packaging smoke test: exercises Qt widgets and platform plugins.
        def capture():
            ok = window.grab().save(str(preview))
            window.close()
            app.exit(0 if ok else 1)
        QTimer.singleShot(250, capture)
    return app.exec()
