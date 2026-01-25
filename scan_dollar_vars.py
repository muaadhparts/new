#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import argparse
import csv
import json
import os
import re
from dataclasses import dataclass, asdict
from typing import Dict, List, Optional, Tuple


# PHP style variables start with $
VAR_RE = re.compile(r"(?<!\\)\$[A-Za-z_][A-Za-z0-9_]*")

# Heuristics for contexts
ASSIGN_RE_TPL = r"(?<!\\)\{var}\s*=\s*(?P<rhs>.+?)\s*;"
ECHO_RE = re.compile(r"\b(echo|print)\b")
RETURN_RE = re.compile(r"\breturn\b")


@dataclass
class Occurrence:
    variable: str
    file: str
    line: int
    column: int
    line_text: str
    context_type: str
    context_snippet: str
    last_assignment_line: Optional[int] = None
    last_assignment_expr: Optional[str] = None


def is_probably_text_file(path: str) -> bool:
    try:
        with open(path, "rb") as f:
            chunk = f.read(4096)
        if b"\x00" in chunk:
            return False
        return True
    except Exception:
        return False


def detect_context(line: str, var: str) -> Tuple[str, str, Optional[str]]:
    """
    returns context_type context_snippet assignment_rhs_if_this_is_assignment
    """
    # Assignment
    assign_re = re.compile(ASSIGN_RE_TPL.format(var=re.escape(var)))
    m = assign_re.search(line)
    if m:
        rhs = m.group("rhs").strip()
        snippet = f"{var} = {rhs}"
        return ("assignment", snippet[:500], rhs)

    # Function or method call argument
    # Heuristic capture nearest identifier before (
    if "(" in line and var in line:
        # find a plausible function token before first (
        # supports foo( , obj->bar( , Class::baz(
        before_paren = line.split("(", 1)[0]
        fn = before_paren.strip().split()[-1] if before_paren.strip() else ""
        if any(tok in fn for tok in ["->", "::"]) or re.match(r"^[A-Za-z_\\][A-Za-z0-9_\\:>\-]*$", fn):
            # capture inside parentheses roughly
            inside = line.split("(", 1)[1]
            inside = inside.rsplit(")", 1)[0] if ")" in inside else inside
            snippet = f"{fn}({inside.strip()})"
            return ("function_arg", snippet[:500], None)

    # Echo print
    if ECHO_RE.search(line) and var in line:
        return ("output", line.strip()[:500], None)

    # Return
    if RETURN_RE.search(line) and var in line:
        return ("return", line.strip()[:500], None)

    # Array access
    if re.search(re.escape(var) + r"\s*\[", line):
        return ("array_access", line.strip()[:500], None)

    # String interpolation
    if '"' in line and var in line:
        return ("string_interpolation", line.strip()[:500], None)

    return ("usage", line.strip()[:500], None)


def iter_files(root: str, exts: List[str], exclude_dirs: List[str]) -> List[str]:
    files = []
    for dirpath, dirnames, filenames in os.walk(root):
        # prune excluded dirs
        dirnames[:] = [d for d in dirnames if d not in exclude_dirs and not d.startswith(".")]
        for fn in filenames:
            if fn.startswith("."):
                continue
            path = os.path.join(dirpath, fn)
            low = fn.lower()
            if any(low.endswith(e) for e in exts):
                files.append(path)
    return files


def scan_file(path: str) -> List[Occurrence]:
    occs: List[Occurrence] = []
    if not is_probably_text_file(path):
        return occs

    last_assign: Dict[str, Tuple[int, str]] = {}

    try:
        with open(path, "r", encoding="utf-8", errors="replace") as f:
            lines = f.readlines()
    except Exception:
        return occs

    for i, line in enumerate(lines, start=1):
        for m in VAR_RE.finditer(line):
            var = m.group(0)
            col = m.start() + 1

            ctx_type, ctx_snip, assign_rhs = detect_context(line, var)

            last_line = None
            last_expr = None
            if var in last_assign:
                last_line, last_expr = last_assign[var]

            occs.append(
                Occurrence(
                    variable=var,
                    file=path,
                    line=i,
                    column=col,
                    line_text=line.rstrip("\n"),
                    context_type=ctx_type,
                    context_snippet=ctx_snip,
                    last_assignment_line=last_line,
                    last_assignment_expr=last_expr,
                )
            )

            # If current line is assignment update last_assign
            if ctx_type == "assignment" and assign_rhs is not None:
                last_assign[var] = (i, assign_rhs)

    return occs


def main():
    ap = argparse.ArgumentParser(description="Scan project for $variables and report locations and heuristics about passed data")
    ap.add_argument("root", help="project root path")
    ap.add_argument("--ext", action="append", default=None, help="file extension to include like .php can repeat")
    ap.add_argument("--exclude", action="append", default=None, help="exclude dir name can repeat")
    ap.add_argument("--out-json", default="dollar_vars_report.json", help="output json file")
    ap.add_argument("--out-csv", default="dollar_vars_report.csv", help="output csv file")
    args = ap.parse_args()

    exts = args.ext or [".php", ".phtml", ".inc", ".module", ".tpl", ".twig", ".blade.php"]
    exclude = args.exclude or ["vendor", "node_modules", "storage", "cache", "dist", "build", ".git"]

    paths = iter_files(args.root, exts, exclude)

    all_occs: List[Occurrence] = []
    for p in paths:
        all_occs.extend(scan_file(p))

    # sort for readability
    all_occs.sort(key=lambda o: (o.variable, o.file, o.line, o.column))

    # write json
    with open(args.out_json, "w", encoding="utf-8") as jf:
        json.dump([asdict(o) for o in all_occs], jf, ensure_ascii=False, indent=2)

    # write csv
    with open(args.out_csv, "w", encoding="utf-8", newline="") as cf:
        w = csv.writer(cf)
        w.writerow([
            "variable",
            "file",
            "line",
            "column",
            "context_type",
            "context_snippet",
            "last_assignment_line",
            "last_assignment_expr",
            "line_text",
        ])
        for o in all_occs:
            w.writerow([
                o.variable,
                o.file,
                o.line,
                o.column,
                o.context_type,
                o.context_snippet,
                o.last_assignment_line or "",
                o.last_assignment_expr or "",
                o.line_text,
            ])

    # also print summary
    uniq = {}
    for o in all_occs:
        uniq.setdefault(o.variable, 0)
        uniq[o.variable] += 1

    print("done")
    print(f"files scanned {len(paths)}")
    print(f"occurrences {len(all_occs)}")
    print(f"unique variables {len(uniq)}")
    top = sorted(uniq.items(), key=lambda kv: kv[1], reverse=True)[:20]
    print("top 20 by occurrences")
    for v, c in top:
        print(f"{v} {c}")


if __name__ == "__main__":
    main()
