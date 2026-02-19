#!/bin/bash
#
# monitor_claude_session.sh — Monitora se o Claude Code esta ativo
# Verifica a cada 20 minutos se o processo Claude Code esta rodando.
# Se detectar que caiu/fechou, envia email alertando a paralisacao.
#
# Uso: bash scripts/automacao/monitor_claude_session.sh &
#

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
LOG_FILE="$PROJECT_DIR/logs/monitor_claude.log"
CHECK_INTERVAL=1200  # 20 minutos em segundos
ALREADY_NOTIFIED=false

mkdir -p "$PROJECT_DIR/logs"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

is_claude_running() {
    # Verifica se existe processo claude-code ou claude rodando
    if pgrep -f "claude" > /dev/null 2>&1; then
        return 0
    fi
    # Verificar tambem pelo node com claude
    if pgrep -f "node.*claude" > /dev/null 2>&1; then
        return 0
    fi
    return 1
}

log "Monitor iniciado. Verificando a cada $((CHECK_INTERVAL / 60)) minutos."
log "PID do monitor: $$"

# Aguardar 1 minuto antes da primeira verificacao
sleep 60

while true; do
    if is_claude_running; then
        if [ "$ALREADY_NOTIFIED" = true ]; then
            # Claude voltou! Notificar recuperacao
            log "Claude Code RECUPERADO - voltou a funcionar"
            python3 "$SCRIPT_DIR/notify_email.py" \
                "Claude Code RECUPERADO" \
                "O Claude Code voltou a funcionar no projeto AlmasaStudio. O trabalho foi retomado." 2>/dev/null
            ALREADY_NOTIFIED=false
        else
            log "Claude Code OK - rodando normalmente"
        fi
    else
        if [ "$ALREADY_NOTIFIED" = false ]; then
            # Claude caiu! Notificar
            log "ALERTA: Claude Code NAO DETECTADO - possivel paralisacao"
            python3 "$SCRIPT_DIR/notify_email.py" \
                "ALERTA: Claude Code PARADO" \
                "O Claude Code NAO esta mais rodando no projeto AlmasaStudio. Possivel paralisacao do trabalho. Verifique o computador e reinicie se necessario." 2>/dev/null
            ALREADY_NOTIFIED=true
        else
            log "Claude Code ainda parado (ja notificado)"
        fi
    fi

    sleep "$CHECK_INTERVAL"
done
