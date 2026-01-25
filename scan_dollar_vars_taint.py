#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import argparse
import csv
import json
import os
import re
from dataclasses import dataclass, asdict
from typing import Dict, List, Optional, Tuple, Set


# match $var
VAR_RE = re.compile(r"(?<!\\)\$[A-Za-z_][A-Za-z0-9_]*")

# match superglobals as sources
SUPERGLOBAL_RE = re.compile(
    r"(?<!\\)\$(?:_GET|_POST|_REQUEST|_COOKIE|_FILES)\b"
)

# naive assignment: $x = ... ;
ASSIGN_RE = re.compile(r"(?P<lhs>\$[A-Za-z_][A-Za-z0-9_]*)\s*=\s*(?P<rhs>.+?)\s*;")

# detect echo print etc
ECHO_PRINT_RE = re.compile(r"\b(echo|print)\b")
RETURN_RE = re.compile(r"\breturn\b")

# function call pattern name( ... )
CALL_RE = re.compile(r"(?P<name>[A-Za-z_\\][A-Za-z0-9_\\]*)\s*\(")

# method call pattern obj->method( ... ) or Class::method( ... )
METHOD_CALL_RE = re.compile(r"(?P<name>[A-Za-z_\\][A-Za-z0-9_\\]*)\s*(?:->|::)\s*(?P<method>[A-Za-z_][A-Za-z0-9_]*)\s*\(")

# split arguments rough
ARG_SPLIT_RE = re.compile(r",(?![^\(\)]*\))")  # split on commas not inside parentheses


SINK_FUNCTIONS = {
    # output
    "json_encode",
    "printf",
    "sprintf",
    "vprintf",
    "vsprintf",
    "print_r",
    "var_dump",
    "header",
    "setcookie",
    # file
    "file_put_contents",
    "fwrite",
    "fprintf",
    # command execution
    "exec",
    "shell_exec",
    "system",
    "passthru",
    "popen",
    # eval
    "eval",
}

SINK_METHODS = {
    # db like
    ("PDO", "query"),
    ("PDO", "exec"),
    ("mysqli", "query"),
    ("mysqli", "real_query"),
    # frameworks common
    ("Response", "json"),
    ("JsonResponse", "__construct"),
}


@dataclass
class Occurrence:
    variable: str
    file: str
    line: int
    column: int
    line_text: str

    kind: str  # usage assignment source sink
    context: str

    is_source: bool = False
    source_type: Optional[str] = None
    source_expr: Optional[str] = None

    is_sink: bool = False
    sink_type: Optional[str] = None
    sink_target: Optional[str] = None

    tainted: bool = False
    taint_reason: Optional[str] = None
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


def iter_files(root: str, exts: List[str], exclude_dirs: List[str]) -> List[str]:
    files = []
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = [d for d in dirnames if d not in exclude_dirs and not d.startswith(".")]
        for fn in filenames:
            if fn.startswith("."):
                continue
            low = fn.lower()
            if any(low.endswith(e) for e in exts):
                files.append(os.path.join(dirpath, fn))
    return files


def extract_vars(expr: str) -> Set[str]:
    return set(VAR_RE.findall(expr))


def extract_sources(expr: str) -> List[str]:
    return SUPERGLOBAL_RE.findall(expr)


def detect_sink_in_line(line: str) -> List[Tuple[str, str]]:
    """
    returns list of (sink_type, sink_target)
    sink_type example output function method return
    """
    sinks = []

    if ECHO_PRINT_RE.search(line):
        sinks.append(("output", "echo_or_print"))

    if RETURN_RE.search(line):
        sinks.append(("return", "return"))

    # function calls
    for m in CALL_RE.finditer(line):
        name = m.group("name")
        if name in SINK_FUNCTIONS:
            sinks.append(("function", name))

    # method calls
    for m in METHOD_CALL_RE.finditer(line):
        obj = m.group("name")
        method = m.group("method")
        for klass, meth in SINK_METHODS:
            if method == meth and obj.lower().endswith(klass.lower()):
                sinks.append(("method", f"{obj}->{method}"))
    return sinks


def detect_assignment(line: str) -> Optional[Tuple[str, str]]:
    m = ASSIGN_RE.search(line)
    if not m:
        return None
    return (m.group("lhs"), m.group("rhs").strip())


def scan_file(path: str) -> Tuple[List[Occurrence], Dict[str, dict]]:
    occs: List[Occurrence] = []
    per_file_summary = {
        "file": path,
        "sources": [],
        "sinks": [],
        "tainted_sinks": [],
        "tainted_variables": [],
    }

    if not is_probably_text_file(path):
        return occs, per_file_summary

    try:
        with open(path, "r", encoding="utf-8", errors="replace") as f:
            lines = f.readlines()
    except Exception:
        return occs, per_file_summary

    # last assignment info
    last_assign: Dict[str, Tuple[int, str]] = {}
    # taint map within file
    taint: Dict[str, Tuple[bool, Optional[str]]] = {}

    # helper to add occurrence
    def add_occ(var: str, i: int, col: int, line_text: str, **kwargs):
        last_line = last_assign.get(var, (None, None))[0]
        last_expr = last_assign.get(var, (None, None))[1]
        tainted = taint.get(var, (False, None))[0]
        reason = taint.get(var, (False, None))[1]
        occs.append(
            Occurrence(
                variable=var,
                file=path,
                line=i,
                column=col,
                line_text=line_text.rstrip("\n"),
                last_assignment_line=last_line,
                last_assignment_expr=last_expr,
                tainted=tainted,
                taint_reason=reason,
                **kwargs,
            )
        )

    for i, line in enumerate(lines, start=1):
        line_stripped = line.rstrip("\n")

        # assignment first for taint propagation
        asg = detect_assignment(line)
        if asg:
            lhs, rhs = asg
            last_assign[lhs] = (i, rhs)

            rhs_sources = extract_sources(rhs)
            rhs_vars = extract_vars(rhs)

            is_source = len(rhs_sources) > 0
            derived_from_tainted = any(taint.get(v, (False, None))[0] for v in rhs_vars if v != lhs)

            if is_source:
                taint[lhs] = (True, f"assigned_from_{rhs_sources[0]}")
                per_file_summary["sources"].append(
                    {"line": i, "lhs": lhs, "source": rhs_sources[0], "expr": rhs[:400]}
                )
            elif derived_from_tainted:
                # capture first tainted var as reason
                first_tv = next((v for v in rhs_vars if taint.get(v, (False, None))[0]), None)
                taint[lhs] = (True, f"derived_from_{first_tv}")
            else:
                # do not auto untaint if already tainted by earlier flows
                if lhs not in taint:
                    taint[lhs] = (False, None)

            # occurrence for lhs assignment
            col = line.find(lhs) + 1 if lhs in line else 1
            add_occ(
                lhs, i, col, line_stripped,
                kind="assignment",
                context=f"{lhs} = {rhs}"[:500],
                is_source=is_source,
                source_type=rhs_sources[0] if is_source else None,
                source_expr=rhs[:500] if is_source else None,
            )

        # record occurrences for any vars on the line
        vars_on_line = list(VAR_RE.finditer(line))
        for m in vars_on_line:
            var = m.group(0)
            col = m.start() + 1

            # skip duplicate for lhs assignment already added if same position roughly
            if asg and var == asg[0] and col == (line.find(var) + 1):
                continue

            # determine if this line is a source usage (superglobal itself)
            if SUPERGLOBAL_RE.search(var):
                continue

            # sink detection
            sinks = detect_sink_in_line(line)
            if sinks:
                for sink_type, sink_target in sinks:
                    is_sink = True
                    add_occ(
                        var, i, col, line_stripped,
                        kind="sink",
                        context=line_stripped[:500],
                        is_sink=is_sink,
                        sink_type=sink_type,
                        sink_target=sink_target,
                    )
                    per_file_summary["sinks"].append(
                        {"line": i, "sink_type": sink_type, "sink_target": sink_target, "line_text": line_stripped[:400]}
                    )
                    if taint.get(var, (False, None))[0]:
                        per_file_summary["tainted_sinks"].append(
                            {
                                "line": i,
                                "variable": var,
                                "sink_type": sink_type,
                                "sink_target": sink_target,
                                "taint_reason": taint.get(var, (False, None))[1],
                                "line_text": line_stripped[:400],
                            }
                        )
            else:
                add_occ(
                    var, i, col, line_stripped,
                    kind="usage",
                    context=line_stripped[:500],
                )

    # summarize tainted variables
    for v, (t, reason) in taint.items():
        if t:
            per_file_summary["tainted_variables"].append({"variable": v, "reason": reason})

    return occs, per_file_summary


def main():
    ap = argparse.ArgumentParser(description="Scan $variables, sources from superglobals, sinks, and basic taint flow per file")
    ap.add_argument("root", help="project root path")
    ap.add_argument("--ext", action="append", default=None, help="file extension to include like .php can repeat")
    ap.add_argument("--exclude", action="append", default=None, help="exclude dir name can repeat")
    ap.add_argument("--out-json", default="dollar_vars_taint_report.json", help="output json file")
    ap.add_argument("--out-csv", default="dollar_vars_taint_report.csv", help="output csv file")
    ap.add_argument("--out-summary", default="sources_sinks_summary.json", help="per file sources and sinks summary json")
    args = ap.parse_args()

    exts = args.ext or [".php", ".phtml", ".inc", ".module", ".tpl", ".twig", ".blade.php"]
    exclude = args.exclude or ["vendor", "node_modules", "storage", "cache", "dist", "build", ".git"]

    paths = iter_files(args.root, exts, exclude)

    all_occs: List[Occurrence] = []
    summaries: List[dict] = []

    for p in paths:
        occs, summary = scan_file(p)
        if occs:
            all_occs.extend(occs)
        # keep summary even if empty to know it was scanned
        summaries.append(summary)

    # sort
    all_occs.sort(key=lambda o: (o.file, o.line, o.column, o.variable))

    # write json
    with open(args.out_json, "w", encoding="utf-8") as jf:
        json.dump([asdict(o) for o in all_occs], jf, ensure_ascii=False, indent=2)

    # write csv
    with open(args.out_csv, "w", encoding="utf-8", newline="") as cf:
        w = csv.writer(cf)
        w.writerow([
            "file","line","column","variable","kind","context",
            "is_source","source_type","source_expr",
            "is_sink","sink_type","sink_target",
            "tainted","taint_reason",
            "last_assignment_line","last_assignment_expr",
            "line_text",
        ])
        for o in all_occs:
            w.writerow([
                o.file, o.line, o.column, o.variable, o.kind, o.context,
                "1" if o.is_source else "0",
                o.source_type or "",
                o.source_expr or "",
                "1" if o.is_sink else "0",
                o.sink_type or "",
                o.sink_target or "",
                "1" if o.tainted else "0",
                o.taint_reason or "",
                o.last_assignment_line or "",
                o.last_assignment_expr or "",
                o.line_text,
            ])

    # write summary
    with open(args.out_summary, "w", encoding="utf-8") as sf:
        json.dump(summaries, sf, ensure_ascii=False, indent=2)

    # console stats
    uniq_vars = set(o.variable for o in all_occs)
    tainted_hits = sum(1 for o in all_occs if o.is_sink and o.tainted)
    print("done")
    print(f"files_scanned {len(paths)}")
    print(f"occurrences {len(all_occs)}")
    print(f"unique_variables {len(uniq_vars)}")
    print(f"tainted_sink_occurrences {tainted_hits}")
    print(f"outputs {args.out_csv} {args.out_json} {args.out_summary}")


if __name__ == "__main__":
    main()
