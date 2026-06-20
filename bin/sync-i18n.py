#!/usr/bin/env python3
"""Sync picot-theme-seeder POT/PO/MO files from PHP sources."""

from __future__ import annotations

import re
import subprocess
import sys
import tempfile
from collections import OrderedDict
from datetime import datetime, timezone, timedelta
from pathlib import Path

DOMAIN = 'picot-theme-seeder'
BUGS_TO = 'https://github.com/tsubu/picot-theme-seeder/issues'
ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / 'languages'
POT_PATH = LANG_DIR / f'{DOMAIN}.pot'
JA_PO_PATH = LANG_DIR / f'{DOMAIN}-ja.po'
JA_MO_PATH = LANG_DIR / f'{DOMAIN}-ja.mo'

PHP_FUNCS = (
    '__',
    '_e',
    'esc_html__',
    'esc_html_e',
    'esc_attr__',
    'esc_attr_e',
)

SKIP_DIRS = {'.git', 'languages', 'node_modules', 'vendor'}


def escape_po(text: str) -> str:
    return text.replace('\\', '\\\\').replace('"', '\\"')


def unescape_po(text: str) -> str:
    return text.replace('\\"', '"').replace('\\\\', '\\')


def wrap_po(text: str, prefix: str) -> str:
    if '\n' in text or len(text) > 70:
        lines = []
        current = ''
        for char in text:
            if len(current) >= 70:
                lines.append(current)
                current = ''
            current += char
        if current:
            lines.append(current)
        body = '\n'.join(f'"{escape_po(line)}"' for line in lines)
        return f'{prefix} ""\n{body}'
    return f'{prefix} "{escape_po(text)}"'


def render_po_header(language: str | None = None) -> str:
    jst = datetime.now(timezone(timedelta(hours=9))).strftime('%Y-%m-%d %H:%M%z')
    header = OrderedDict(
        [
            ('Project-Id-Version', 'Picot Theme Seeder 1.0.0'),
            ('Report-Msgid-Bugs-To', BUGS_TO),
            ('POT-Creation-Date', jst),
            ('PO-Revision-Date', jst),
            ('Last-Translator', 'PICOT'),
            ('Language-Team', 'PICOT'),
            ('MIME-Version', '1.0'),
            ('Content-Type', 'text/plain; charset=UTF-8'),
            ('Content-Transfer-Encoding', '8bit'),
            ('X-Generator', 'Picot Theme Seeder i18n sync'),
        ]
    )
    if language:
        header['Language'] = language
        header['Plural-Forms'] = 'nplurals=1; plural=0;'
    lines = ['msgid ""', 'msgstr ""']
    for key, value in header.items():
        lines.append(f'"{key}: {value}\\n"')
    return '\n'.join(lines) + '\n\n'


def render_pot(entries: dict[str, list[str]]) -> str:
    chunks = []
    for msgid, refs in entries.items():
        block_lines = [f'#: {ref}' for ref in refs]
        block_lines.append(wrap_po(msgid, 'msgid'))
        block_lines.append('msgstr ""')
        chunks.append('\n'.join(block_lines))
    return render_po_header() + '\n\n'.join(chunks) + '\n'


def extract_php_strings() -> dict[str, list[str]]:
    pattern = re.compile(
        r'(?:' + '|'.join(PHP_FUNCS) + r')\(\s*'
        r'((?:\'(?:\\\'|[^\'])*\'|"(?:\\"|[^"])*")(?:\s*\.\s*(?:\'(?:\\\'|[^\'])*\'|"(?:\\"|[^"])*"))*)\s*,\s*'
        r'[\'"]' + re.escape(DOMAIN) + r'[\'"]\s*\)',
        re.DOTALL,
    )
    strings: dict[str, list[str]] = OrderedDict()
    for path in sorted(ROOT.rglob('*.php')):
        if any(part in SKIP_DIRS for part in path.parts):
            continue
        rel = path.relative_to(ROOT).as_posix()
        content = path.read_text(encoding='utf-8', errors='replace')
        for match in pattern.finditer(content):
            raw = match.group(1)
            parts = re.findall(r"'((?:\\'|[^'])*)'|\"((?:\\\"|[^\"])*)\"", raw)
            msgid = ''.join(p[0] or p[1] for p in parts)
            msgid = msgid.replace("\\'", "'").replace('\\"', '"')
            strings.setdefault(msgid, [])
            if rel not in strings[msgid]:
                strings[msgid].append(rel)
    return strings


def count_translated(path: Path) -> tuple[int, int]:
    text = path.read_text(encoding='utf-8')
    total = 0
    translated = 0
    for block in re.split(r'\n\n+', text):
        if 'msgid ' not in block:
            continue
        if block.startswith('msgid ""\n') and 'Project-Id-Version' in block:
            continue
        if block.startswith('msgid ""\n'):
            mid = re.search(r'msgid ""\n((?:".*"\n)+)', block)
            msgid = unescape_po(''.join(re.findall(r'"(.*)"', mid.group(1)))) if mid else ''
        else:
            m = re.search(r'msgid "(.*)"', block)
            msgid = unescape_po(m.group(1)) if m else ''
        if not msgid:
            continue
        total += 1
        if re.search(r'msgstr ""\n(?:")', block):
            translated += 1
        else:
            m = re.search(r'msgstr "(.*)"', block)
            msgstr = unescape_po(m.group(1)) if m else ''
            if msgstr.strip():
                translated += 1
    return translated, total


def main() -> int:
    extracted = extract_php_strings()
    POT_PATH.write_text(render_pot(extracted), encoding='utf-8')

    subprocess.run(
        ['msgmerge', '--update', '--no-fuzzy-matching', str(JA_PO_PATH), str(POT_PATH)],
        check=True,
    )
    subprocess.run(['msgfmt', '-o', str(JA_MO_PATH), str(JA_PO_PATH)], check=True)

    translated, total = count_translated(JA_PO_PATH)
    print(f'Synced {total} strings ({translated} translated in ja.po).')
    print(f'Wrote {POT_PATH.name}, {JA_PO_PATH.name}, {JA_MO_PATH.name}')
    return 0


if __name__ == '__main__':
    sys.exit(main())
