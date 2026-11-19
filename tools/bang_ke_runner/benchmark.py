"""Stage timings and profile against a frozen input, using selected code path."""
import argparse
import cProfile
import io
import json
from pathlib import Path
import pstats
import sys
import time

parser = argparse.ArgumentParser()
parser.add_argument("--code", required=True)
parser.add_argument("--input", required=True)
parser.add_argument("--output", required=True)
parser.add_argument("--report", required=True)
args = parser.parse_args()
sys.path.insert(0, str(Path(args.code).resolve()))
from ooxml import Book
from engine import Runner

profile = cProfile.Profile()
timings = {}
profile.enable()
t = time.perf_counter()
book = Book(args.input)
timings["read_seconds"] = time.perf_counter() - t
t = time.perf_counter()
result = Runner(book, Path(args.input).name).run()
timings["compute_seconds"] = time.perf_counter() - t
t = time.perf_counter()
book.save(args.output)
timings["write_seconds"] = time.perf_counter() - t
book.close()
profile.disable()
timings["total_seconds"] = sum(timings.values())
stream = io.StringIO()
pstats.Stats(profile, stream=stream).sort_stats("cumtime").print_stats(20)
report = {"timings": timings, "result": result, "profile": stream.getvalue()}
Path(args.report).write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
print(json.dumps(timings, indent=2), flush=True)
print(stream.getvalue(), flush=True)
