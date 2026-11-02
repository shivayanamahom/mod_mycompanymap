#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
version=$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' mod_mycompanymap.xml | head -1)
[[ -n "$version" ]] || { echo 'Version missing' >&2; exit 1; }
mkdir -p dist
archive="dist/mod_mycompanymap-${version}.zip"
rm -f "$archive"
zip -X -q -r "$archive" mod_mycompanymap.xml mod_mycompanymap.php README.md LICENSE language media services src tmpl -x '*.DS_Store' -x '__MACOSX/*'
cp "$archive" dist/mod_mycompanymap.zip
(cd dist && shasum -a 256 "mod_mycompanymap-${version}.zip" mod_mycompanymap.zip > SHA256SUMS.txt)
echo "Built $archive"
