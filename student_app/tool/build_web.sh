#!/bin/sh
# Builds the Flutter web app and publishes it into Laravel's public/app,
# served at https://<domain>/app/. CanvasKit is loaded from Google's CDN,
# so the local canvaskit/ folder (~37 MB) is not published.
set -e
cd "$(dirname "$0")/.."
flutter build web --release --base-href /app/
rm -rf ../public/app
mkdir -p ../public/app
rsync -a --exclude 'canvaskit/' --exclude '*.symbols' build/web/ ../public/app/
echo "Published to public/app ($(du -sh ../public/app | cut -f1))"
