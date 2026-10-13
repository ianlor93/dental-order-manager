#!/bin/bash
# backup_env.sh — Crea backup timestampato di .env
# Mantiene solo gli ultimi 10 backup

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE=".env.backup_${DATE}"

cd "$APP_DIR"

if [ ! -f ".env" ]; then
    echo "❌ File .env non trovato in $APP_DIR"
    exit 1
fi

cp .env "$BACKUP_FILE"
echo "✓ Backup creato: $BACKUP_FILE"

# Mantieni solo gli ultimi 10 backup
BACKUP_COUNT=$(ls -1 .env.backup_* 2>/dev/null | wc -l)
if [ "$BACKUP_COUNT" -gt 10 ]; then
    ls -t .env.backup_* | tail -n +11 | xargs rm -f
    echo "✓ Backup vecchi rimossi (mantenuti ultimi 10)"
fi
