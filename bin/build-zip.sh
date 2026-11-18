#!/usr/bin/env bash
# Build the plugin ZIP for WordPress.org / manual upload (plugin files only).
set -euo pipefail
cd "$(dirname "$0")/.."
OUT="${1:-dist}"; NAME=ognivid-ai-assistant
rm -rf "$OUT/$NAME" "$OUT/$NAME.zip"; mkdir -p "$OUT/$NAME"
cp -r ognivid-ai-assistant.php uninstall.php readme.txt LICENSE includes assets "$OUT/$NAME/"
( cd "$OUT" && python3 -c "
import zipfile,os
z=zipfile.ZipFile('$NAME.zip','w',zipfile.ZIP_DEFLATED)
for r,_,fs in os.walk('$NAME'):
    for f in sorted(fs): z.write(os.path.join(r,f))
z.close()" )
rm -rf "$OUT/$NAME"; echo "$OUT/$NAME.zip"
