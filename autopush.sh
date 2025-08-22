#!/bin/bash
BRANCH="main"   # ubah ke master kalau perlu

while true; do
  if [[ -n $(git status --porcelain) ]]; then
    git add .
    git commit -m "auto update on $(date '+%Y-%m-%d %H:%M:%S')"
    git push origin $BRANCH
    echo "✅ Auto-pushed at $(date '+%H:%M:%S')"
  fi
  sleep 10
done

