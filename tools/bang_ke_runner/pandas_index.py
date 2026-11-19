"""Pandas indexes and grouped financial totals for the V71 calculation engine.

Raw values stay object-typed, so IDs, blanks, string '1916' and numeric 1916
remain distinct. Normalizers and rounding are supplied by the VBA port.
"""
from __future__ import annotations

import pandas as pd


class InvoiceIndex:
    KEY_FIELDS = {
        "full": ("series", "number", "date", "tax_id"),
        "core": ("model", "series", "number", "tax_id"),
        "fallback": ("model", "number", "date", "tax_id"),
    }

    def __init__(self, layout, normalizers, numeric_value, source_missing):
        self.revision = layout.sheet.revision
        rows = layout.data_rows()
        fields = tuple(dict.fromkeys((*normalizers, "goods", "amount", "vat")))
        self.frame = pd.DataFrame(
            [[layout.get(r, f) for f in fields] for r in rows],
            index=pd.Index(rows, name="excel_row"), columns=fields, dtype=object,
        )
        normal = pd.DataFrame(index=self.frame.index)
        for field, normalize in normalizers.items():
            # Repeated identifiers are normalized once per distinct typed value.
            # Typed keys avoid treating True/1, or text/number 1916 as identical.
            memo = {}
            def apply(value, fn=normalize, cache=memo):
                key = (type(value), value)
                try:
                    return cache[key]
                except KeyError:
                    result = fn(value)
                    cache[key] = result
                    return result
            normal[field] = self.frame[field].map(apply)
        self.keys, self.valid, self.parts = {}, {}, {}
        self.key_series, self.valid_series = {}, {}
        for kind, columns in self.KEY_FIELDS.items():
            first, *rest = columns
            keys = normal[first].str.cat([normal[c] for c in rest], sep="|")
            valid = normal[list(columns)].ne("").all(axis=1)
            self.key_series[kind], self.valid_series[kind] = keys, valid
            self.keys[kind], self.valid[kind] = keys.to_dict(), valid.to_dict()
            self.parts[kind] = dict(zip(rows, normal[list(columns)].itertuples(index=False, name=None)))
        positions = pd.Series(rows, index=self.frame.index, name="row", dtype="int64")
        self.group_rows = positions.groupby(self.key_series["full"], sort=False).agg(list).to_dict()
        self.row_groups = {tuple(rs): key for key, rs in self.group_rows.items()}
        self.groups_valid = positions.loc[self.valid_series["full"]].groupby(
            self.key_series["full"], sort=False,
        ).agg(list).to_dict()
        financial = pd.DataFrame(index=self.frame.index)
        for field in ("goods", "amount", "vat"):
            present = ~self.frame[field].map(source_missing)
            financial[field + "_value"] = self.frame[field].map(numeric_value).where(present, 0.0)
            financial[field + "_present"] = present
        # Use the same ordered Python sum as the original port at tolerance
        # boundaries instead of substituting pandas' group-sum algorithm.
        aggregations = {f + "_value": lambda values: sum(values.tolist()) for f in ("goods", "amount", "vat")}
        aggregations.update({f + "_present": "any" for f in ("goods", "amount", "vat")})
        self.totals = financial.groupby(self.key_series["full"], sort=False).agg(aggregations).to_dict("index")

    def candidates(self, kind):
        pairs = pd.DataFrame({"candidate": self.key_series[kind], "full": self.key_series["full"]})
        pairs = pairs.loc[self.valid_series[kind]].drop_duplicates()
        return pairs.groupby("candidate", sort=False)["full"].agg(list).to_dict()

    def sum_for(self, rows, field):
        key = self.row_groups.get(tuple(rows))
        if key is None or field not in ("goods", "amount", "vat"):
            return None
        values = self.totals[key]
        return float(values[field + "_value"]), bool(values[field + "_present"])
