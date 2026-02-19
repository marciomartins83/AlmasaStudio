#!/usr/bin/env python3
"""
notify_email.py — Notificacao por email para Claude Code
Envia emails ao proprietario quando o sistema precisa de atencao.

Uso:
    python3 scripts/automacao/notify_email.py "Assunto" "Mensagem"
    python3 scripts/automacao/notify_email.py --status  # Envia status atual dos testes
"""

import smtplib
import sys
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime

# Configuracao
SMTP_SERVER = "smtp.gmail.com"
SMTP_PORT = 587
GMAIL_SENDER = "neuzinhagatinha44@gmail.com"
GMAIL_APP_PASSWORD = "rtfh bacb czhn ihor"
RECIPIENT = "marcioramos1983@gmail.com"


def send_email(subject: str, body: str, html: bool = False) -> bool:
    """Envia email via Gmail SMTP"""
    try:
        msg = MIMEMultipart("alternative")
        msg["From"] = f"AlmasaStudio Bot <{GMAIL_SENDER}>"
        msg["To"] = RECIPIENT
        msg["Subject"] = f"[AlmasaStudio] {subject}"

        content_type = "html" if html else "plain"
        msg.attach(MIMEText(body, content_type, "utf-8"))

        with smtplib.SMTP(SMTP_SERVER, SMTP_PORT, timeout=15) as server:
            server.starttls()
            server.login(GMAIL_SENDER, GMAIL_APP_PASSWORD)
            server.send_message(msg)

        print(f"[OK] Email enviado: {subject}")
        return True

    except Exception as e:
        print(f"[ERRO] Falha ao enviar email: {e}")
        return False


def notify_need_attention(message: str):
    """Notifica que precisa de atencao do usuario"""
    now = datetime.now().strftime("%d/%m/%Y %H:%M")
    body = f"""
<h2>AlmasaStudio - Preciso da sua atencao</h2>
<p><strong>Data:</strong> {now}</p>
<hr>
<p>{message}</p>
<hr>
<p><em>Enviado automaticamente pelo Claude Code</em></p>
"""
    send_email("Preciso da sua atencao", body, html=True)


def notify_progress(phase: str, details: str):
    """Notifica progresso de uma fase"""
    now = datetime.now().strftime("%d/%m/%Y %H:%M")
    body = f"""
<h2>AlmasaStudio - Progresso: {phase}</h2>
<p><strong>Data:</strong> {now}</p>
<hr>
<pre>{details}</pre>
<hr>
<p><em>Enviado automaticamente pelo Claude Code</em></p>
"""
    send_email(f"Progresso: {phase}", body, html=True)


def notify_complete(phase: str, summary: str):
    """Notifica conclusao de uma fase"""
    now = datetime.now().strftime("%d/%m/%Y %H:%M")
    body = f"""
<h2>AlmasaStudio - Fase Concluida: {phase}</h2>
<p><strong>Data:</strong> {now}</p>
<hr>
<pre>{summary}</pre>
<hr>
<p><em>Enviado automaticamente pelo Claude Code</em></p>
"""
    send_email(f"CONCLUIDO: {phase}", body, html=True)


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python3 notify_email.py 'Assunto' 'Mensagem'")
        sys.exit(1)

    if sys.argv[1] == "--test":
        send_email("Teste de Conexao", "Se voce recebeu este email, o sistema de notificacao esta funcionando!")
    elif len(sys.argv) >= 3:
        send_email(sys.argv[1], sys.argv[2])
    else:
        send_email("Notificacao", sys.argv[1])
