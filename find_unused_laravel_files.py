#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import argparse
import json
import os
import re
import csv
from typing import Dict, List, Set, Tuple

EXCLUDE_DIRS_DEFAULT = {
    "vendor", "node_modules", "storage", "bootstrap/cache", ".git", "public/build", "dist", "build"
}

TEXT_EXTS = (".php", ".blade.php", ".js", ".ts", ".jsx", ".tsx", ".css", ".scss", ".vue", ".json", ".env", ".md")

def norm(p: str) -> str:
    return os.path.normpath(p)

def is_text_file(path: str) -> bool:
    if not path.lower().endswith(TEXT_EXTS):
        return False
    try:
        with open(path, "rb") as f:
            chunk = f.read(4096)
        return b"\x00" not in chunk
    except Exception:
        return False

def read_text(path: str) -> str:
    with open(path, "r", encoding="utf-8", errors="replace") as f:
        return f.read()

def walk_files(root: str, exclude_dirs: Set[str]) -> List[str]:
    out = []
    root = norm(root)
    for dirpath, dirnames, filenames in os.walk(root):
        rel = norm(os.path.relpath(dirpath, root))
        # prune excluded dirs (support nested like bootstrap/cache)
        pruned = []
        for d in list(dirnames):
            full_rel = norm(os.path.join(rel, d))
            if d.startswith("."):
                pruned.append(d); continue
            if d in exclude_dirs:
                pruned.append(d); continue
            # nested exclude match
            if any(full_rel == e or full_rel.startswith(e + os.sep) for e in exclude_dirs):
                pruned.append(d); continue
        for d in pruned:
            if d in dirnames:
                dirnames.remove(d)

        for fn in filenames:
            if fn.startswith("."):
                continue
            out.append(norm(os.path.join(dirpath, fn)))
    return out

def build_corpus_text(files: List[str]) -> str:
    parts = []
    for p in files:
        if is_text_file(p):
            try:
                parts.append(read_text(p))
            except Exception:
                pass
    return "\n".join(parts)

def view_key_from_path(root: str, view_path: str) -> str:
    # resources/views/foo/bar.blade.php => foo.bar
    root = norm(root)
    view_path = norm(view_path)
    marker = norm(os.path.join(root, "resources", "views")) + os.sep
    if view_path.startswith(marker):
        rel = view_path[len(marker):]
    else:
        rel = os.path.basename(view_path)
    rel = rel.replace(".blade.php", "")
    rel = rel.replace(os.sep, ".")
    return rel

def class_guess_from_path(root: str, php_path: str) -> str:
    # app/Foo/Bar.php => App\Foo\Bar (best-effort)
    root = norm(root)
    php_path = norm(php_path)
    marker = norm(os.path.join(root, "app")) + os.sep
    if php_path.startswith(marker):
        rel = php_path[len(marker):]
        rel = rel.replace(".php", "")
        rel = rel.replace(os.sep, "\\")
        return "App\\" + rel
    return ""

def short_class_name(fqcn: str) -> str:
    return fqcn.split("\\")[-1] if fqcn else ""

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("root", help="project root")
    ap.add_argument("--out-json", default="unused_candidates.json")
    ap.add_argument("--out-csv", default="unused_candidates.csv")
    ap.add_argument("--exclude", action="append", default=[], help="exclude dir name or path relative to root, can repeat")
    args = ap.parse_args()

    root = norm(args.root)
    exclude_dirs = set(EXCLUDE_DIRS_DEFAULT)
    exclude_dirs.update({norm(x) for x in args.exclude})

    all_files = walk_files(root, exclude_dirs)

    # corpus = all text in project (excluding excluded dirs)
    text_files = [p for p in all_files if is_text_file(p)]
    corpus = build_corpus_text(text_files)

    candidates = []

    # 1) Blade views
    views = [p for p in all_files if p.lower().endswith(".blade.php") and (os.sep + "resources" + os.sep + "views" + os.sep) in p]
    for vp in views:
        key = view_key_from_path(root, vp)
        # common Laravel view usages
        patterns = [
            rf"view\(\s*['\"]{re.escape(key)}['\"]\s*[\),]",
            rf"Route::view\(\s*['\"][^'\"]+['\"]\s*,\s*['\"]{re.escape(key)}['\"]",
            rf"@include\(\s*['\"]{re.escape(key)}['\"]",
            rf"@extends\(\s*['\"]{re.escape(key)}['\"]",
            rf"@component\(\s*['\"]{re.escape(key)}['\"]",
            rf"@livewire\(\s*['\"]{re.escape(key)}['\"]",
            # include using slash style in some codebases: 'foo/bar'
            rf"view\(\s*['\"]{re.escape(key.replace('.', '/'))}['\"]\s*[\),]",
            rf"@include\(\s*['\"]{re.escape(key.replace('.', '/'))}['\"]",
            rf"@extends\(\s*['\"]{re.escape(key.replace('.', '/'))}['\"]",
        ]
        used = any(re.search(pat, corpus) for pat in patterns)
        if not used:
            candidates.append({
                "type": "blade_view",
                "file": os.path.relpath(vp, root),
                "key": key,
                "reason": "no_reference_found_for_view_key"
            })

    # 2) PHP classes under app/
    php_classes = [p for p in all_files if p.lower().endswith(".php") and (os.sep + "app" + os.sep) in p]
    # ignore some obvious entry points that might look unused but are loaded by framework
    ignore_class_files = {
        norm(os.path.join(root, "app", "Providers", "AppServiceProvider.php")),
        norm(os.path.join(root, "app", "Http", "Kernel.php")),
        norm(os.path.join(root, "app", "Console", "Kernel.php")),
        norm(os.path.join(root, "app", "Exceptions", "Handler.php")),
    }

    for pp in php_classes:
        if norm(pp) in ignore_class_files:
            continue
        fqcn = class_guess_from_path(root, pp)
        if not fqcn:
            continue
        short = short_class_name(fqcn)

        # references: FQCN, ::class, use statement, or short class name (rough)
        patterns = [
            rf"{re.escape(fqcn)}",
            rf"{re.escape(fqcn)}::class",
            rf"use\s+{re.escape(fqcn)}\s*;",
            rf"\b{re.escape(short)}::class\b",
            rf"['\"]{re.escape(short)}@",
        ]
        used = any(re.search(pat, corpus) for pat in patterns)
        if not used:
            candidates.append({
                "type": "php_class",
                "file": os.path.relpath(pp, root),
                "fqcn": fqcn,
                "reason": "no_reference_found_for_class"
            })

    # 3) Assets candidates (resources/js, resources/css) not referenced
    asset_dirs = [norm(os.path.join(root, "resources", "js")), norm(os.path.join(root, "resources", "css"))]
    assets = []
    for p in all_files:
        if any(p.startswith(d + os.sep) for d in asset_dirs) and p.lower().endswith((".js",".ts",".jsx",".tsx",".css",".scss",".vue")):
            assets.append(p)

    for apath in assets:
        rel = os.path.relpath(apath, root).replace("\\", "/")
        fname = os.path.basename(apath)
        patterns = [
            re.escape(rel),
            re.escape(fname),
        ]
        used = any(re.search(pat, corpus) for pat in patterns)
        if not used:
            candidates.append({
                "type": "asset",
                "file": os.path.relpath(apath, root),
                "reason": "no_reference_found_for_asset_path_or_name"
            })

    # output
    with open(args.out_json, "w", encoding="utf-8") as f:
        json.dump(candidates, f, ensure_ascii=False, indent=2)

    with open(args.out_csv, "w", encoding="utf-8", newline="") as f:
        w = csv.writer(f)
        cols = ["type", "file", "key", "fqcn", "reason"]
        w.writerow(cols)
        for c in candidates:
            w.writerow([
                c.get("type",""),
                c.get("file",""),
                c.get("key",""),
                c.get("fqcn",""),
                c.get("reason",""),
            ])

    print("done")
    print(f"scanned_files {len(all_files)}")
    print(f"candidates {len(candidates)}")
    print(f"outputs {args.out_json} {args.out_csv}")

if __name__ == "__main__":
    main()
