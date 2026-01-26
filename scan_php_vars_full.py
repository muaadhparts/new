#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import argparse
import csv
import json
import os
import re
from dataclasses import dataclass, asdict
from typing import Dict, List, Optional, Tuple

# $var
VAR_RE = re.compile(r"(?<!\\)\$[A-Za-z_][A-Za-z0-9_]*")

# naive assignment: $x = ...;
ASSIGN_RE = re.compile(
    r"(?P<lhs>\$[A-Za-z_][A-Za-z0-9_]*)\s*=\s*(?P<rhs>.+?)\s*;"
)

# sources in Laravel/PHP (heuristics)
SOURCE_PATTERNS = [
    (re.compile(r"\$(?:_GET|_POST|_REQUEST|_COOKIE|_FILES)\b"), "superglobal"),
    (re.compile(r"\brequest\(\s*\)"), "laravel_request_helper"),
    (re.compile(r"\$request->(?:input|get|query|post|header|cookie|route)\s*\("), "laravel_request_object"),
    (re.compile(r"\bRoute::(?:current|input)\b"), "laravel_route"),
    (re.compile(r"\bDB::"), "laravel_db"),
    (re.compile(r"\bconfig\s*\("), "config"),
    (re.compile(r"\benv\s*\("), "env"),
    (re.compile(r"\bAuth::user\s*\("), "auth"),
]

# sinks (optional, for context)
SINK_PATTERNS = [
    (re.compile(r"\b(echo|print)\b"), "output"),
    (re.compile(r"\breturn\b"), "return"),
    (re.compile(r"\bjson_encode\s*\("), "json_encode"),
    (re.compile(r"\bheader\s*\("), "header"),
    (re.compile(r"\bsetcookie\s*\("), "setcookie"),
    (re.compile(r"\b(exec|shell_exec|system|passthru|popen)\s*\("), "command_exec"),
    (re.compile(r"\bfile_put_contents\s*\("), "file_write"),
]

# literal quick checks
LIT_STRING_RE = re.compile(r"^[\s]*(['\"]).*?\1[\s]*$")
LIT_NUMBER_RE = re.compile(r"^[\s]*-?\d+(?:\.\d+)?[\s]*$")
LIT_BOOL_NULL_RE = re.compile(r"^[\s]*(true|false|null)[\s]*$", re.IGNORECASE)
ARRAY_RE = re.compile(r"^[\s]*(\[|array\s*\().*", re.IGNORECASE)

TEXT_EXTS = (
    ".php", ".phtml", ".inc", ".module",
    ".blade.php", ".twig", ".tpl"
)

EXCLUDE_DEFAULT = {"vendor", "node_modules", "storage", "cache", "dist", "build", ".git", "bootstrap/cache"}


@dataclass
class VarEvent:
    variable: str
    file: str
    line: int
    column: int
    event: str  # declare_first, assign, use
    snippet: str
    rhs: Optional[str] = None
    rhs_kind: Optional[str] = None
    rhs_source: Optional[str] = None
    first_decl_line: Optional[int] = None
    first_decl_rhs: Optional[str] = None
    last_assign_line: Optional[int] = None
    last_assign_rhs: Optional[str] = None


def is_text_file(path: str) -> bool:
    low = path.lower()
    if not any(low.endswith(e) for e in TEXT_EXTS):
        return False
    try:
        with open(path, "rb") as f:
            chunk = f.read(4096)
        return b"\x00" not in chunk
    except Exception:
        return False


def iter_files(root: str, exclude_dirs: List[str]) -> List[str]:
    files = []
    for dirpath, dirnames, filenames in os.walk(root):
        # prune
        pruned = []
        for d in list(dirnames):
            if d.startswith("."):
                pruned.append(d)
                continue
            if d in exclude_dirs:
                pruned.append(d)
                continue
        for d in pruned:
            if d in dirnames:
                dirnames.remove(d)

        for fn in filenames:
            if fn.startswith("."):
                continue
            p = os.path.join(dirpath, fn)
            if is_text_file(p):
                files.append(p)
    return files


def classify_rhs(rhs: str) -> Tuple[str, Optional[str]]:
    r = rhs.strip()

    # detect source patterns
    for rx, name in SOURCE_PATTERNS:
        if rx.search(r):
            return ("expression", name)

    # literals
    if LIT_STRING_RE.match(r):
        return ("literal_string", None)
    if LIT_NUMBER_RE.match(r):
        return ("literal_number", None)
    if LIT_BOOL_NULL_RE.match(r):
        return ("literal_bool_null", None)
    if ARRAY_RE.match(r):
        return ("literal_array", None)

    # object instantiation
    if re.search(r"\bnew\s+[A-Za-z_\\][A-Za-z0-9_\\]*\b", r):
        return ("new_object", None)

    # function call
    if re.search(r"[A-Za-z_\\][A-Za-z0-9_\\]*\s*\(", r):
        return ("function_call_or_expression", None)

    return ("expression", None)


def detect_sinks(line: str) -> List[str]:
    out = []
    for rx, name in SINK_PATTERNS:
        if rx.search(line):
            out.append(name)
    return out


def scan_file(path: str) -> List[VarEvent]:
    events: List[VarEvent] = []

    try:
        with open(path, "r", encoding="utf-8", errors="replace") as f:
            lines = f.readlines()
    except Exception:
        return events

    first_decl: Dict[str, Tuple[int, str]] = {}
    last_assign: Dict[str, Tuple[int, str]] = {}

    for i, line in enumerate(lines, start=1):
        line_text = line.rstrip("\n")

        # assignments (may be multiple per line; handle all)
        for m in ASSIGN_RE.finditer(line):
            lhs = m.group("lhs")
            rhs = m.group("rhs").strip()
            col = m.start("lhs") + 1

            rhs_kind, rhs_source = classify_rhs(rhs)

            if lhs not in first_decl:
                first_decl[lhs] = (i, rhs)
                events.append(VarEvent(
                    variable=lhs,
                    file=path,
                    line=i,
                    column=col,
                    event="declare_first",
                    snippet=f"{lhs} = {rhs}"[:500],
                    rhs=rhs[:800],
                    rhs_kind=rhs_kind,
                    rhs_source=rhs_source,
                ))

            last_assign[lhs] = (i, rhs)
            events.append(VarEvent(
                variable=lhs,
                file=path,
                line=i,
                column=col,
                event="assign",
                snippet=f"{lhs} = {rhs}"[:500],
                rhs=rhs[:800],
                rhs_kind=rhs_kind,
                rhs_source=rhs_source,
                first_decl_line=first_decl.get(lhs, (None, None))[0],
                first_decl_rhs=first_decl.get(lhs, (None, None))[1],
                last_assign_line=i,
                last_assign_rhs=rhs,
            ))

        # usages
        for vm in VAR_RE.finditer(line):
            var = vm.group(0)
            col = vm.start() + 1

            # if this usage is part of an assignment LHS already handled, skip double count (best-effort)
            if ASSIGN_RE.search(line):
                am = ASSIGN_RE.search(line)
                if am and am.start("lhs") <= vm.start() <= am.end("lhs"):
                    continue

            sinks = detect_sinks(line_text)
            extra = f" sinks={','.join(sinks)}" if sinks else ""

            events.append(VarEvent(
                variable=var,
                file=path,
                line=i,
                column=col,
                event="use",
                snippet=(line_text[:500] + extra)[:520],
                first_decl_line=first_decl.get(var, (None, None))[0],
                first_decl_rhs=first_decl.get(var, (None, None))[1],
                last_assign_line=last_assign.get(var, (None, None))[0],
                last_assign_rhs=last_assign.get(var, (None, None))[1],
            ))

    return events


def main():
    ap = argparse.ArgumentParser(description="Extract all $variables with creation (first assignment), usage locations, and estimated contents (RHS).")
    ap.add_argument("root", help="project root")
    ap.add_argument("--exclude", action="append", default=[], help="exclude dir name (repeatable)")
    ap.add_argument("--out-json", default="all_vars_report.json")
    ap.add_argument("--out-csv", default="all_vars_report.csv")
    ap.add_argument("--out-summary", default="vars_summary.json")
    args = ap.parse_args()

    exclude = set(EXCLUDE_DEFAULT)
    exclude.update(args.exclude)

    paths = iter_files(args.root, list(exclude))

    all_events: List[VarEvent] = []
    for p in paths:
        all_events.extend(scan_file(p))

    # sort for readability
    all_events.sort(key=lambda e: (e.variable, e.file, e.line, e.column))

    # build per-variable summary
    summary: Dict[str, dict] = {}
    for ev in all_events:
        s = summary.setdefault(ev.variable, {
            "occurrences": 0,
            "declared_in": None,
            "first_decl_rhs": None,
            "assignments": [],
            "usages": [],
        })
        s["occurrences"] += 1

        if ev.event == "declare_first" and s["declared_in"] is None:
            s["declared_in"] = {"file": ev.file, "line": ev.line, "snippet": ev.snippet}
            s["first_decl_rhs"] = {"rhs": ev.rhs, "rhs_kind": ev.rhs_kind, "rhs_source": ev.rhs_source}

        if ev.event == "assign":
            s["assignments"].append({
                "file": ev.file,
                "line": ev.line,
                "rhs": ev.rhs,
                "rhs_kind": ev.rhs_kind,
                "rhs_source": ev.rhs_source,
                "snippet": ev.snippet,
            })
        if ev.event == "use":
            s["usages"].append({
                "file": ev.file,
                "line": ev.line,
                "snippet": ev.snippet,
                "last_assign_line": ev.last_assign_line,
                "last_assign_rhs": ev.last_assign_rhs,
            })

    with open(args.out_json, "w", encoding="utf-8") as f:
        json.dump([asdict(e) for e in all_events], f, ensure_ascii=False, indent=2)

    with open(args.out_csv, "w", encoding="utf-8", newline="") as f:
        w = csv.writer(f)
        w.writerow([
            "variable","file","line","column","event",
            "snippet","rhs","rhs_kind","rhs_source",
            "first_decl_line","first_decl_rhs",
            "last_assign_line","last_assign_rhs"
        ])
        for e in all_events:
            w.writerow([
                e.variable, e.file, e.line, e.column, e.event,
                e.snippet,
                e.rhs or "",
                e.rhs_kind or "",
                e.rhs_source or "",
                e.first_decl_line or "",
                e.first_decl_rhs or "",
                e.last_assign_line or "",
                e.last_assign_rhs or "",
            ])

    with open(args.out_summary, "w", encoding="utf-8") as f:
        json.dump(summary, f, ensure_ascii=False, indent=2)

    print("done")
    print(f"files_scanned {len(paths)}")
    print(f"events {len(all_events)}")
    print(f"unique_vars {len(summary)}")
    print(f"outputs {args.out_csv} {args.out_json} {args.out_summary}")


if __name__ == "__main__":
    main()
