#!/usr/bin/env python3
"""Parse a Figma get_metadata XML dump into theme architecture data.

The Figma MCP `get_metadata` output for a whole page is usually too large to read
directly, so it lands in a tool-results file. This script reads that file and
answers the questions the figma-analysis skill needs, without further MCP calls.

Usage:
    figma_tree.py <dump> probe                 naming/grouping quality checks
    figma_tree.py <dump> pages                 list page frames
    figma_tree.py <dump> map [<page-id>|all]   ordered blocks per page
    figma_tree.py <dump> clusters              recurring blocks + their copy
    figma_tree.py <dump> signals [kw-file]     interaction affordances
    figma_tree.py <dump> segment <page-id>     boundary guess for flat pages

The dump may be raw XML or the JSON array wrapper the MCP writes.
"""

import json
import re
import statistics
import sys
from collections import Counter, defaultdict

NODE_RE = re.compile(
    r'^(\s*)<(\w[\w-]*) id="([^"]+)" name="([^"]*)" '
    r'x="([-\d.]+)" y="([-\d.]+)" width="([\d.]+)" height="([\d.]+)"'
)

# Placeholder layer names: text nodes named like this carry no copy.
PLACEHOLDER_RE = re.compile(
    r'^(text|frame|group|rectangle|ellipse|vector|line|layer)[\s_-]*\d*$', re.I
)

# Default affordance keywords. Language-specific by nature: override with a JSON
# file mapping label -> list of regexes when the project is not DE/EN.
DEFAULT_KEYWORDS = {
    "filter/sort": [r"^(filter|view|sortieren|ordenar|filtrar)$"],
    "pagination": [r"^(\d+\s*/\s*\d+|more|mehr laden|load more|ver mais)$"],
    "accordion": [r"^(faq|häufige|perguntas frequentes)"],
    "slider nav": [r"^(next|prev|weiter|zurück|seguinte|anterior)$"],
    "form submit": [r"^(senden|absenden|submit|anmelden|enviar)$"],
    "map": [r"(wo sie uns finden|anfahrt|como chegar)"],
}


def load(path):
    """Return the XML text from a raw or JSON-wrapped dump."""
    raw = open(path, encoding="utf-8").read()
    stripped = raw.lstrip()
    if stripped.startswith("["):
        return "".join(part.get("text", "") for part in json.loads(raw))
    return raw


def parse(text):
    """Build the node tree. Returns (roots, by_id); every node carries depth."""
    by_id, stack, roots = {}, [], []
    for line in text.split("\n"):
        m = NODE_RE.match(line)
        if not m:
            continue
        depth = len(m.group(1)) // 2
        node = {
            "type": m.group(2), "id": m.group(3), "name": m.group(4),
            "x": float(m.group(5)), "y": float(m.group(6)),
            "w": float(m.group(7)), "h": float(m.group(8)),
            "depth": depth, "kids": [],
        }
        by_id[node["id"]] = node
        while stack and stack[-1]["depth"] >= depth:
            stack.pop()
        if stack:
            stack[-1]["kids"].append(node)
        else:
            roots.append(node)
        stack.append(node)
    return roots, by_id


def descendants(node):
    out = []
    queue = [node]
    while queue:
        cur = queue.pop()
        out.append(cur)
        queue.extend(cur["kids"])
    return out


def page_frames(roots, min_w=390.0, min_h=1200.0):
    """Top-level frames big enough to be screens, not stray artwork."""
    canvas = roots[0] if roots else None
    if canvas is None:
        return []
    pool = canvas["kids"] if canvas["type"] == "canvas" else roots
    return [n for n in pool if n["w"] >= min_w and n["h"] >= min_h]


def texts_of(node):
    return [n for n in descendants(node) if n["type"] == "text"]


# --- commands ---------------------------------------------------------------

def cmd_probe(roots, _by_id, _args):
    """Decide whether the cheap parsing path is viable for this file."""
    all_text = [n for n in descendants(roots[0]) if n["type"] == "text"]
    named = [n for n in all_text if not PLACEHOLDER_RE.match(n["name"].strip())]
    ratio = len(named) / len(all_text) if all_text else 0.0

    print("SONDA 1 — os nomes das camadas de texto trazem o copy?")
    print(f"  {len(named)}/{len(all_text)} nós de texto com nome real ({ratio:.0%})")
    if ratio >= 0.6:
        print("  → OK: dá para nomear secções por parsing, sem screenshots.")
    else:
        print("  → AVISO: nomes genéricos. É preciso o caminho por screenshots;")
        print("    não confiar em nomeação automática de secções neste ficheiro.")

    pages = page_frames(roots)
    print(f"\nSONDA 2 — as páginas estão agrupadas em secções? ({len(pages)} páginas)")
    flat = []
    for p in pages:
        kids = p["kids"]
        frames = [k for k in kids if k["type"] == "frame"]
        if len(kids) > 30 or (kids and len(frames) / len(kids) < 0.25):
            flat.append((p, len(kids), len(frames)))
    print(f"  {len(pages) - len(flat)} agrupadas (fronteiras dadas pelo designer)")
    print(f"  {len(flat)} flat (precisam de segmentação + confirmação visual):")
    for p, nk, nf in sorted(flat, key=lambda r: -r[1]):
        print(f"    · {p['name']}  —  {nk} filhos soltos, só {nf} frames  [{p['id']}]")


def cmd_pages(roots, _by_id, _args):
    for p in page_frames(roots):
        print(f"{p['id']:<14}{p['w']:>6.0f}×{p['h']:<8.0f} {p['name']}")


def cmd_map(roots, _by_id, args):
    """Ordered top-level blocks of a page — the section skeleton."""
    target = args[0] if args else "all"
    for p in page_frames(roots):
        if target not in ("all", p["id"], p["name"]):
            continue
        print(f"\n{'=' * 72}\n{p['name']}  [{p['id']}]  {p['w']:.0f}×{p['h']:.0f}\n{'=' * 72}")
        for k in sorted(p["kids"], key=lambda n: n["y"]):
            copy = [t["name"][:52] for t in texts_of(k)][:3]
            hint = ("  « " + " | ".join(copy)) if copy else ""
            print(f"  y={k['y']:>7.0f} h={k['h']:>6.0f} {k['type']:<18}"
                  f" {k['id']:<13} {k['name'][:26]:<26}{hint}")


def cmd_clusters(roots, _by_id, _args):
    """Blocks repeated across pages: the reusable section catalogue.

    Clusters on geometry, not layer name — the same block is often duplicated
    under different group ids, so name matching undercounts reuse badly.
    """
    pages = page_frames(roots)
    buckets = defaultdict(list)
    for p in pages:
        for k in p["kids"]:
            if k["type"] != "frame":
                continue
            key = (round(k["w"] / 40) * 40, round(k["h"] / 40) * 40)
            buckets[key].append((k, p))

    print("BLOCOS RECORRENTES (agrupados por geometria)\n")
    for (w, h), inst in sorted(buckets.items(), key=lambda x: -len(x[1])):
        pages_hit = {p["name"] for _, p in inst}
        if len(pages_hit) < 3:
            continue
        rep = inst[0][0]
        rel = statistics.median(k["y"] / p["h"] for k, p in inst)
        pos = "topo" if rel < 0.08 else ("fundo" if rel > 0.82 else f"meio {rel:.0%}")
        names = Counter(k["name"] for k, _ in inst)
        print(f"■ ~{w:.0f}×{h:.0f}  em {len(pages_hit)} páginas  ({pos})"
              f"  exemplo {rep['id']}")
        if len(names) > 1:
            print(f"    ids distintos p/ o mesmo bloco: {len(names)}"
                  f"  — {', '.join(list(names)[:3])}")
        for t in texts_of(rep)[:6]:
            print(f"    • {t['name'][:78]}")
        print()


def cmd_signals(roots, _by_id, args):
    """Interaction affordances — candidates for Module classification."""
    keywords = DEFAULT_KEYWORDS
    if args:
        keywords = json.load(open(args[0], encoding="utf-8"))
    compiled = {k: [re.compile(p, re.I) for p in v] for k, v in keywords.items()}

    hits = defaultdict(list)
    for p in page_frames(roots):
        for t in texts_of(p):
            name = t["name"].strip()
            for label, pats in compiled.items():
                if any(pat.search(name) for pat in pats):
                    hits[label].append((p["name"], name))

    print("AFORDÂNCIAS DE INTERACÇÃO → candidatos a MODULE\n")
    print("AVISO: keyword matching dá falsos positivos (texto legal, nomes de")
    print("produto). Confirmar sempre antes de classificar.\n")
    for label, found in sorted(hits.items(), key=lambda x: -len(x[1])):
        pages_hit = sorted({p for p, _ in found})
        print(f"■ {label} — {len(found)} ocorrências em {len(pages_hit)} páginas")
        for p in pages_hit[:8]:
            print(f"    · {p}")
        if len(pages_hit) > 8:
            print(f"    · … +{len(pages_hit) - 8}")
        print(f"    termos: {', '.join(sorted({n for _, n in found})[:6])}\n")

    dupes = {n: c for n, c in Counter(p["name"] for p in page_frames(roots)).items() if c > 1}
    if dupes:
        print("PÁGINAS DUPLICADAS → normalmente estados de interacção desenhados:")
        for name, count in dupes.items():
            print(f"    {count}× {name}")


def cmd_segment(roots, _by_id, args):
    """Boundary candidates for a flat page, ranked by signal strength.

    Only a starting point: metadata carries no font size, so a tall paragraph
    and a big heading look alike. Always confirm against a screenshot.
    """
    if not args:
        sys.exit("segment precisa do id da página")
    page = next((p for p in page_frames(roots) if p["id"] == args[0]), None)
    if page is None:
        sys.exit(f"página {args[0]} não encontrada")

    marks = []
    for k in sorted(page["kids"], key=lambda n: n["y"]):
        name, wide = k["name"], k["w"] >= page["w"] * 0.9
        if k["type"] in ("rounded-rectangle", "rectangle") and wide and k["h"] >= 300:
            marks.append((k["y"], 3, "fundo full-bleed (dá início E fim)", name, k["h"]))
        elif k["type"] == "line" and k["w"] > page["w"] * 0.5:
            marks.append((k["y"], 3, "linha divisória", name, k["h"]))
        elif k["type"] == "text":
            chars = len(name.strip())
            # Headings: few characters in a tall box. Body copy: many.
            if k["h"] >= 60 and chars / max(k["h"], 1) < 0.45:
                marks.append((k["y"], 2, "título grande (provável H2)", name, k["h"]))
            elif 18 <= k["h"] <= 34 and name.isupper() and chars < 30:
                marks.append((k["y"], 1, "overline (pode ser botão/idioma)", name, k["h"]))

    print(f"FRONTEIRAS CANDIDATAS — {page['name']} [{page['id']}]")
    print("força 3 = fiável · 2 = provável · 1 = fraco, confirmar\n")
    prev = None
    for y, score, kind, name, h in marks:
        gap = f"   ⟵ gap {y - prev:.0f}px" if prev is not None and y - prev > 300 else ""
        print(f"  y={y:>7.0f} [{score}] {kind:<34} {name[:44]}{gap}")
        prev = y


COMMANDS = {
    "probe": cmd_probe, "pages": cmd_pages, "map": cmd_map,
    "clusters": cmd_clusters, "signals": cmd_signals, "segment": cmd_segment,
}


def main():
    if len(sys.argv) < 3 or sys.argv[2] not in COMMANDS:
        sys.exit(__doc__)
    roots, by_id = parse(load(sys.argv[1]))
    if not roots:
        sys.exit("dump vazio ou em formato inesperado")
    COMMANDS[sys.argv[2]](roots, by_id, sys.argv[3:])


if __name__ == "__main__":
    main()
