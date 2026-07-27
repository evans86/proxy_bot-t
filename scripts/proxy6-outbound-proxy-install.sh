#!/usr/bin/env bash
# HTTP-прокси для исходящих запросов проекта proxy6-back к proxy6.net API.
# Доступ только с ALLOWED_CLIENT_IP (+ логин/пароль).
#
# Использование:
#   ALLOWED_CLIENT_IP=1.2.3.4 PROXY_USER=proxy6relay PROXY_PASS=secret PORT=3128 \
#     bash proxy6-outbound-proxy-install.sh

set -euo pipefail

ALLOWED_CLIENT_IP="${ALLOWED_CLIENT_IP:?ALLOWED_CLIENT_IP required}"
PROXY_USER="${PROXY_USER:-proxy6relay}"
PROXY_PASS="${PROXY_PASS:?PROXY_PASS required}"
PORT="${PORT:-3128}"
STATE_DIR="${STATE_DIR:-/opt/proxy6-outbound-proxy}"
CONFIG_PATH="${STATE_DIR}/3proxy.cfg"
BIN_PATH="${STATE_DIR}/3proxy"
SERVICE_NAME="proxy6-outbound-proxy"

log() { echo "[proxy6-outbound-proxy] $*"; }
die() { log "ОШИБКА: $*"; exit 1; }

need_cmd() { command -v "$1" >/dev/null 2>&1; }

if ! [[ "$PORT" =~ ^[0-9]+$ ]] || [ "$PORT" -lt 1024 ] || [ "$PORT" -gt 65535 ]; then
  die "Некорректный порт: $PORT (нужен 1024-65535)"
fi

install_deps() {
  if need_cmd apt-get; then
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -qq
    apt-get install -y -qq ca-certificates curl build-essential git >/dev/null
  elif need_cmd dnf; then
    dnf install -y ca-certificates curl gcc make git
  elif need_cmd yum; then
    yum install -y ca-certificates curl gcc make git
  else
    die "Не найден apt-get/dnf/yum"
  fi
}

install_3proxy() {
  mkdir -p "$STATE_DIR"
  if [ -x "$BIN_PATH" ]; then
    log "3proxy уже установлен: $BIN_PATH"
    return
  fi

  install_deps
  TMP="/tmp/3proxy-build-$$"
  rm -rf "$TMP"
  git clone --depth 1 https://github.com/3proxy/3proxy.git "$TMP"
  make -C "$TMP" -f Makefile.Linux >/dev/null
  cp -f "$TMP/bin/3proxy" "$BIN_PATH"
  chmod 755 "$BIN_PATH"
  rm -rf "$TMP"
  log "3proxy собран: $BIN_PATH"
}

write_config() {
  mkdir -p "$STATE_DIR"
  cat > "$CONFIG_PATH" <<EOF
daemon
maxconn 64
nserver 1.1.1.1
nserver 8.8.8.8
nscache 65536
timeouts 1 5 30 60 180 1800 15 60

auth strong
users ${PROXY_USER}:CL:${PROXY_PASS}
allow ${PROXY_USER},${ALLOWED_CLIENT_IP}
deny *

proxy -p${PORT}
EOF
  chmod 600 "$CONFIG_PATH"
  log "Конфиг: $CONFIG_PATH (allow ${ALLOWED_CLIENT_IP})"
}

write_systemd_unit() {
  cat > "/etc/systemd/system/${SERVICE_NAME}.service" <<EOF
[Unit]
Description=Proxy6 outbound HTTP proxy relay
After=network-online.target
Wants=network-online.target

[Service]
Type=forking
ExecStart=${BIN_PATH} ${CONFIG_PATH}
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
  systemctl daemon-reload
  systemctl enable "${SERVICE_NAME}" >/dev/null
  systemctl restart "${SERVICE_NAME}"
  log "systemd: ${SERVICE_NAME} запущен"
}

open_firewall() {
  if need_cmd ufw && ufw status 2>/dev/null | grep -qi active; then
    ufw allow from "${ALLOWED_CLIENT_IP}" to any port "${PORT}" proto tcp comment 'proxy6-outbound' >/dev/null || true
    log "ufw: разрешён ${ALLOWED_CLIENT_IP}:${PORT}"
  fi
  if need_cmd firewall-cmd && firewall-cmd --state 2>/dev/null | grep -qi running; then
    firewall-cmd --permanent --add-rich-rule="rule family=\"ipv4\" source address=\"${ALLOWED_CLIENT_IP}/32\" port port=\"${PORT}\" protocol=\"tcp\" accept" >/dev/null 2>&1 || true
    firewall-cmd --reload >/dev/null 2>&1 || true
    log "firewalld: правило для ${ALLOWED_CLIENT_IP}"
  fi
}

self_test() {
  sleep 1
  if ! systemctl is-active --quiet "${SERVICE_NAME}"; then
    journalctl -u "${SERVICE_NAME}" -n 30 --no-pager || true
    die "Сервис ${SERVICE_NAME} не активен"
  fi
  log "Проверка proxy6.net через локальный прокси…"
  if curl -fsS --connect-timeout 10 --max-time 20 \
    -x "http://${PROXY_USER}:${PROXY_PASS}@127.0.0.1:${PORT}" \
    "https://proxy6.net/" >/dev/null; then
    log "proxy6.net доступен через прокси"
  else
    log "Предупреждение: self-test proxy6.net не прошёл (прокси может работать для других целей)"
  fi
}

if [ "$(id -u)" -ne 0 ]; then
  die "Запускайте от root (sudo bash $0)"
fi

install_3proxy
write_config
write_systemd_unit
open_firewall
self_test

PUBLIC_IP="$(curl -fsS --connect-timeout 5 --max-time 10 https://api.ipify.org 2>/dev/null || hostname -I | awk '{print $1}')"
echo ""
echo "=== ГОТОВО ==="
echo "PROXY6_OUTBOUND_PROXY=http://${PROXY_USER}:${PROXY_PASS}@${PUBLIC_IP}:${PORT}"
echo "Разрешён клиент: ${ALLOWED_CLIENT_IP}"
