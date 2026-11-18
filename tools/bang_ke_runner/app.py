"""Bang Ke Runner — local Windows GUI and command-line entry point."""
from __future__ import annotations

import argparse
import datetime as dt
import json
import os
from pathlib import Path
import queue
import sys
import threading
import traceback

from engine import VERSION, process_file


def main():
    parser = argparse.ArgumentParser(description="Chạy bảng kê V71, không cần Excel.")
    parser.add_argument("--input", help="Tệp .xlsx/.xlsm gồm đủ 3 sheet")
    parser.add_argument("--output", help="Tệp kết quả, cùng đuôi tệp đầu vào")
    parser.add_argument("--report", help="Ghi báo cáo JSON để tự động hóa/kiểm tra")
    args = parser.parse_args()
    if args.input:
        if not args.output:
            parser.error("--input cần có --output")
        try:
            result = process_file(args.input, args.output)
            code = 0
        except Exception as exc:
            result = {"status": "LOI", "error": str(exc)}
            code = 1
        if args.report:
            Path(args.report).write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")
        elif sys.stdout is not None:
            print(json.dumps(result, ensure_ascii=False, indent=2))
        return code
    run_gui()
    return 0


def run_gui():
    import tkinter as tk
    from tkinter import filedialog, messagebox, ttk
    try:
        import ctypes
        ctypes.windll.shcore.SetProcessDpiAwareness(1)
    except Exception:
        pass
    root = tk.Tk()
    root.title("Bảng Kê Runner — V71")
    root.geometry("820x660")
    root.minsize(730, 620)
    root.configure(bg="#f3f5f9")
    style = ttk.Style(root)
    style.theme_use("clam")
    style.configure("TFrame", background="#f3f5f9")
    style.configure("Card.TFrame", background="white")
    style.configure("TLabel", background="#f3f5f9", foreground="#1b2941", font=("Segoe UI", 10))
    style.configure("Title.TLabel", font=("Segoe UI Semibold", 24))
    style.configure("Sub.TLabel", foreground="#5f6d82")
    style.configure("Card.TLabel", background="white")
    style.configure("TButton", font=("Segoe UI", 10), padding=(14, 9))
    style.configure("Run.TButton", background="#215ad5", foreground="white", font=("Segoe UI Semibold", 12), padding=(20, 12))
    style.map("Run.TButton", background=[("active", "#174ab7"), ("disabled", "#aebbd7")], foreground=[("disabled", "white")])
    style.configure("TProgressbar", background="#215ad5", troughcolor="#e3e9f2", borderwidth=0)
    frame = ttk.Frame(root, padding=28)
    frame.pack(fill="both", expand=True)
    ttk.Label(frame, text="Bảng kê hóa đơn", style="Title.TLabel").pack(anchor="w")
    ttk.Label(frame, text="Chọn Excel · Chạy đối chiếu V71 · Lưu kết quả", style="Sub.TLabel").pack(anchor="w", pady=(5, 22))
    card = ttk.Frame(frame, style="Card.TFrame", padding=20)
    card.pack(fill="x")
    ttk.Label(card, text="1. Chọn tệp Excel", style="Card.TLabel", font=("Segoe UI Semibold", 12)).pack(anchor="w")
    file_row = ttk.Frame(card, style="Card.TFrame")
    file_row.pack(fill="x", pady=(12, 10))
    selected = tk.StringVar()
    entry = ttk.Entry(file_row, textvariable=selected, font=("Segoe UI", 10))
    entry.pack(side="left", fill="x", expand=True, ipady=9, padx=(0, 10))
    output = [None]
    running = [False]
    messages = queue.Queue()
    status = tk.StringVar(value="Sẵn sàng. Chọn tệp .xlsx hoặc .xlsm để bắt đầu.")
    progress = tk.DoubleVar(value=0)
    def browse():
        path = filedialog.askopenfilename(title="Chọn tệp chứa đủ 3 sheet", filetypes=[("Excel", "*.xlsx *.xlsm"), ("Excel có macro", "*.xlsm"), ("Excel", "*.xlsx")])
        if path:
            selected.set(path)
            status.set("Đã chọn tệp. Nhấn RUN để chọn nơi lưu và bắt đầu.")
            progress.set(0)
    browse_btn = ttk.Button(file_row, text="Chọn tệp…", command=browse)
    browse_btn.pack(side="right")
    ttk.Label(card, text="Tệp cần có đủ 3 sheet:", style="Card.TLabel", foreground="#5f6d82").pack(anchor="w")
    ttk.Label(card, text="KHAI THUE  •  chi tiet hoa don  •  tong hop hoa don", style="Card.TLabel").pack(anchor="w", pady=(4, 0))
    ttk.Label(frame, text="2. Chạy và lưu kết quả", font=("Segoe UI Semibold", 12)).pack(anchor="w", pady=(22, 10))
    ttk.Label(frame, text="Chạy hoàn toàn trên máy, không cần cài Excel. Tệp đầu vào được giữ nguyên.", style="Sub.TLabel").pack(anchor="w")
    runrow = ttk.Frame(frame)
    runrow.pack(fill="x", pady=(16, 14))
    def show_log(text):
        log.configure(state="normal")
        log.insert("end", text + "\n")
        log.see("end")
        log.configure(state="disabled")
    def worker(src, dest):
        try:
            result = process_file(src, dest, lambda text, value: messages.put(("progress", (text, value))))
            messages.put(("done", result))
        except Exception as exc:
            messages.put(("error", str(exc)))
    def start():
        src = Path(selected.get().strip().strip('"'))
        if not src.is_file():
            messagebox.showerror("Chưa chọn tệp", "Hãy chọn tệp .xlsx hoặc .xlsm hợp lệ.")
            return
        if src.suffix.lower() not in (".xlsx", ".xlsm"):
            messagebox.showerror("Định dạng chưa hỗ trợ", "Ứng dụng nhận tệp .xlsx hoặc .xlsm.")
            return
        name = f"{src.stem}_KET_QUA_{dt.datetime.now():%Y%m%d_%H%M%S}{src.suffix.lower()}"
        dest = filedialog.asksaveasfilename(title="Lưu kết quả", initialdir=str(src.parent), initialfile=name, defaultextension=src.suffix, filetypes=[("Excel", "*" + src.suffix.lower())])
        if not dest:
            return
        if Path(dest).resolve() == src.resolve():
            messagebox.showerror("Trùng tệp đầu vào", "Chọn tên khác để giữ nguyên dữ liệu gốc.")
            return
        running[0] = True
        output[0] = None
        run_btn.configure(state="disabled")
        browse_btn.configure(state="disabled")
        entry.configure(state="disabled")
        open_btn.configure(state="disabled")
        progress.set(0)
        log.configure(state="normal")
        log.delete("1.0", "end")
        log.configure(state="disabled")
        show_log("Bắt đầu xử lý: " + src.name)
        threading.Thread(target=worker, args=(str(src), dest), daemon=True).start()
    run_btn = ttk.Button(runrow, text="RUN — Xử lý bảng kê", style="Run.TButton", command=start)
    run_btn.pack(side="left")
    def open_folder():
        if output[0]:
            import subprocess
            subprocess.Popen(["explorer.exe", "/select,", str(Path(output[0]))])
    open_btn = ttk.Button(runrow, text="Mở nơi lưu kết quả", state="disabled", command=open_folder)
    open_btn.pack(side="right")
    ttk.Progressbar(frame, variable=progress, maximum=100).pack(fill="x")
    ttk.Label(frame, textvariable=status, wraplength=740).pack(anchor="w", pady=(9, 12))
    log = tk.Text(frame, height=8, bg="white", fg="#22314a", relief="flat", font=("Segoe UI", 10), padx=12, pady=10, wrap="word", state="disabled")
    log.pack(fill="both", expand=True)
    ttk.Label(frame, text=f"V71 · Phiên bản {VERSION} · Nhật ký nằm trong tệp kết quả", style="Sub.TLabel", font=("Segoe UI", 9)).pack(anchor="w", pady=(10, 0))
    def poll():
        try:
            while True:
                kind, payload = messages.get_nowait()
                if kind == "progress":
                    text, value = payload
                    status.set(text)
                    progress.set(value)
                    show_log(text)
                else:
                    running[0] = False
                    run_btn.configure(state="normal")
                    browse_btn.configure(state="normal")
                    entry.configure(state="normal")
                    if kind == "error":
                        progress.set(0)
                        status.set("Không hoàn tất. Xem thông báo lỗi.")
                        show_log("LỖI: " + payload)
                        messagebox.showerror("Không thể xử lý", payload)
                    else:
                        output[0] = payload["output"]
                        open_btn.configure(state="normal")
                        stats = payload["stats"]
                        warning = payload["status"] != "THANH_CONG"
                        title = "Hoàn tất, có cảnh báo" if warning else "Hoàn tất"
                        status.set(title + ". Đã lưu tệp kết quả.")
                        summary = (f"{title}\n"
                                   f"Dòng dữ liệu: {stats.get('data_rows', 0):,} · Dòng thêm: {stats.get('added_rows', 0):,}\n"
                                   f"Khớp đầy đủ: {stats.get('exact_matches', 0):,} · Khớp bổ sung: {stats.get('fallback_matches', 0):,}\n"
                                   f"Nhóm không khớp: {stats.get('unmatched_groups', 0):,} · Nhóm mơ hồ: {stats.get('ambiguous_groups', 0):,}\n"
                                   f"Tổng trước thuế: {stats.get('total_amount', 0):,.0f} · Thuế: {stats.get('total_vat', 0):,.0f}\n\n"
                                   f"Đã lưu: {payload['output']}")
                        show_log(summary)
                        if warning:
                            messagebox.showwarning(title, summary + "\n\nXem NHAT_KY_VBA và NHAT_KY_DOI_CHIEU trong kết quả.")
                        else:
                            messagebox.showinfo(title, summary)
        except queue.Empty:
            pass
        root.after(120, poll)
    def close():
        if running[0]:
            messagebox.showinfo("Đang xử lý", "Hãy đợi ứng dụng hoàn tất trước khi đóng.")
        else:
            root.destroy()
    root.protocol("WM_DELETE_WINDOW", close)
    root.after(120, poll)
    root.mainloop()


if __name__ == "__main__":
    raise SystemExit(main())
