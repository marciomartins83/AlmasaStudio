#!/bin/bash
# =============================================================================
# reminder_update_docs.sh — Lembrete periodico para atualizar o Livro Almasa
#
# Adaptado da metodologia "livro unico" do sftTest.
# Roda em background, cria flag a cada 1 hora para lembrar o Claude
# de revisar e atualizar o docs/LIVRO_ALMASA.md.
#
# Uso:
#   ./scripts/automacao/reminder_update_docs.sh &
#
# Para matar:
#   kill $(cat /tmp/almasa_reminder.pid)
# =============================================================================

PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
FLAG_FILE="$PROJECT_DIR/docs/.UPDATE_PENDING"
LOG_FILE="$PROJECT_DIR/logs/reminder.log"
PID_FILE="/tmp/almasa_reminder.pid"
INTERVAL=3600  # 1 hora em segundos

# Evitar duplicatas
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if kill -0 "$OLD_PID" 2>/dev/null; then
        echo "[reminder] Ja existe instancia rodando (PID $OLD_PID). Saindo."
        exit 0
    fi
fi

# Registrar PID
echo $$ > "$PID_FILE"

# Garantir que o diretorio de logs existe
mkdir -p "$(dirname "$LOG_FILE")"

# Cleanup ao sair
cleanup() {
    rm -f "$PID_FILE"
    rm -f "$FLAG_FILE"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Reminder encerrado." >> "$LOG_FILE"
    exit 0
}
trap cleanup EXIT INT TERM

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Reminder iniciado (PID $$, intervalo ${INTERVAL}s)" >> "$LOG_FILE"

while true; do
    sleep "$INTERVAL"

    # Cria flag para o Claude ler
    touch "$FLAG_FILE"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Flag criado: $FLAG_FILE" >> "$LOG_FILE"
done
