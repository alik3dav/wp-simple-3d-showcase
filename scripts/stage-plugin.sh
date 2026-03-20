#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_DIR="${1:-$ROOT_DIR/dist/3d-model-viewer}"

rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR"

cd "$ROOT_DIR"

git ls-files | while IFS= read -r file; do
	case "$file" in
		node_modules/*|dist/*|.gitignore|scripts/*)
			continue
			;;
	esac

	mkdir -p "$TARGET_DIR/$(dirname "$file")"
	cp -R "$file" "$TARGET_DIR/$file"
done

printf 'Staged plugin into %s\n' "$TARGET_DIR"
