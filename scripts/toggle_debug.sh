#!/bin/bash
# toggle_debug.sh — Abilita/disabilita APP_DEBUG in modo sicuro
# Uso: ./scripts/toggle_debug.sh [production|debug]
#
# ROLLBACK EMERGENZA:
#   ./scripts/toggle_debug.sh debug    (riabilita in ~30 secondi)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
MODE=${1:-""}

cd "$APP_DIR"

if [ -z "$MODE" ]; then
    echo "Uso: ./scripts/toggle_debug.sh [production|debug]"
    echo ""
    echo "  production  — Disabilita debug (APP_DEBUG=false, LOG_LEVEL=warning)"
    echo "  debug       — Abilita debug (APP_DEBUG=true, LOG_LEVEL=debug) [EMERGENZA]"
    exit 1
fi

# Backup automatico prima di ogni modifica
bash "$SCRIPT_DIR/backup_env.sh"

if [ "$MODE" == "production" ]; then
    sed -i.bak 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
    sed -i.bak 's/^LOG_LEVEL=.*/LOG_LEVEL=warning/' .env
    rm -f .env.bak
    echo "✓ APP_DEBUG=false"
    echo "✓ LOG_LEVEL=warning"
    echo "→ Modalità PRODUZIONE attivata"

elif [ "$MODE" == "debug" ]; then
    sed -i.bak 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env
    sed -i.bak 's/^LOG_LEVEL=.*/LOG_LEVEL=debug/' .env
    rm -f .env.bak
    echo "⚠️  APP_DEBUG=true"
    echo "⚠️  LOG_LEVEL=debug"
    echo "→ Modalità DEBUG attivata [SOLO PER EMERGENZA — ri-disabilitare dopo]"

else
    echo "❌ Modalità non valida: '$MODE'"
    echo "Usa: production | debug"
    exit 1
fi

# Ricarica configurazione Laravel
# Se PHP non è nel PATH (es. host Unraid), istruzioni manuali
if command -v php &>/dev/null; then
    php artisan config:clear
    php artisan config:cache
    php artisan optimize
    echo ""
    echo "✓ Cache configurazione aggiornata"
    echo ""
else
    echo ""
    echo "⚠️  PHP non trovato nel PATH — esegui manualmente dentro il container:"
    echo "   docker exec -it <container_name> php artisan config:clear"
    echo "   docker exec -it <container_name> php artisan config:cache"
    echo ""
fi

# Verifica finale
CURRENT_DEBUG=$(grep '^APP_DEBUG=' .env | cut -d= -f2)
CURRENT_LOG=$(grep '^LOG_LEVEL=' .env | cut -d= -f2)
echo "Stato attuale:"
echo "  APP_DEBUG  = $CURRENT_DEBUG"
echo "  LOG_LEVEL  = $CURRENT_LOG"
