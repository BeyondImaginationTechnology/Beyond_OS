"""Validate Jaguar JSONL training data without downloading any ML packages."""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

REQUIRED_FIELDS = {"instruction", "input", "output", "type"}
VALID_TYPES = {"lesson_qa", "prompt_grading", "socratic_tutor", "debug_ai"}


def validate(path: Path) -> list[str]:
    errors: list[str] = []
    examples = 0
    seen: set[tuple[str, str]] = set()
    with path.open(encoding="utf-8") as source:
        for line_number, raw in enumerate(source, start=1):
            if not raw.strip():
                continue
            try:
                row = json.loads(raw)
            except json.JSONDecodeError as exc:
                errors.append(f"line {line_number}: invalid JSON ({exc.msg})")
                continue
            if not isinstance(row, dict):
                errors.append(f"line {line_number}: every record must be an object")
                continue
            missing = REQUIRED_FIELDS - row.keys()
            if missing:
                errors.append(f"line {line_number}: missing {', '.join(sorted(missing))}")
                continue
            for field in REQUIRED_FIELDS:
                if not isinstance(row[field], str) or not row[field].strip():
                    if field == "input" and row[field] == "":
                        continue
                    errors.append(f"line {line_number}: {field} must be a non-empty string")
            if row.get("type") not in VALID_TYPES:
                errors.append(f"line {line_number}: unknown type {row.get('type')!r}")
            key = (row["instruction"].strip(), row["input"].strip())
            if key in seen:
                errors.append(f"line {line_number}: duplicate instruction/input pair")
            seen.add(key)
            examples += 1
    if examples == 0:
        errors.append("dataset has no examples")
    return errors


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("dataset", type=Path)
    args = parser.parse_args()
    if not args.dataset.is_file():
        print(f"Dataset not found: {args.dataset}", file=sys.stderr)
        return 2
    errors = validate(args.dataset)
    if errors:
        print("Dataset validation failed:", file=sys.stderr)
        print("\n".join(f"- {error}" for error in errors), file=sys.stderr)
        return 1
    count = sum(1 for line in args.dataset.open(encoding="utf-8") if line.strip())
    print(f"Dataset valid: {count} examples in {args.dataset}")
    if count < 500:
        print("Warning: this is a pipeline smoke-test dataset; target 500+ reviewed examples.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

