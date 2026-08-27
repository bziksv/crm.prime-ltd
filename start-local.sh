#!/bin/bash
# Local CRM via macOS LaunchAgent (survives terminal close)
set -e
ROOT="/Users/stanislav/Documents/projects/crm.prime-ltd"
PORT=8787
LOG="/tmp/crm-php-8787.log"
PLIST="$HOME/Library/LaunchAgents/com.prime.crm.local.plist"
LABEL="com.prime.crm.local"
PHP="$(command -v php)"
URL="http://127.0.0.1:$PORT"

write_plist() {
  mkdir -p "$HOME/Library/LaunchAgents"
  cat >"$PLIST" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key>
  <string>$LABEL</string>
  <key>ProgramArguments</key>
  <array>
    <string>$PHP</string>
    <string>-d</string><string>memory_limit=256M</string>
    <string>-d</string><string>opcache.enable=0</string>
    <string>-S</string><string>127.0.0.1:$PORT</string>
    <string>router.php</string>
  </array>
  <key>WorkingDirectory</key>
  <string>$ROOT</string>
  <key>KeepAlive</key>
  <true/>
  <key>RunAtLoad</key>
  <true/>
  <key>StandardOutPath</key>
  <string>$LOG</string>
  <key>StandardErrorPath</key>
  <string>$LOG</string>
</dict>
</plist>
PLIST
}

is_listening() {
  lsof -iTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1
}

stop_service() {
  launchctl bootout "gui/$(id -u)/$LABEL" 2>/dev/null || launchctl unload "$PLIST" 2>/dev/null || true
  pkill -f "php -S 127.0.0.1:$PORT" 2>/dev/null || true
  echo "stopped"
}

start_service() {
  write_plist
  stop_service >/dev/null 2>&1 || true
  launchctl bootstrap "gui/$(id -u)" "$PLIST" 2>/dev/null || launchctl load "$PLIST"
  echo "started launch agent $LABEL"
}

status_service() {
  if is_listening; then
    echo "running on $URL"
    curl -sS -m 5 -o /dev/null -w "signin HTTP %{http_code}\n" "$URL/index.php/signin" || true
  else
    echo "not running on port $PORT"
    exit 1
  fi
}

case "${1:-start}" in
  start)
    start_service
    for i in {1..10}; do
      sleep 1
      is_listening && break
    done
    status_service
    echo "CRM: $URL/"
    ;;
  stop)
    stop_service
    ;;
  restart)
    stop_service
    sleep 1
    "$0" start
    ;;
  status)
    status_service
    ;;
  *)
    echo "Usage: $0 {start|stop|restart|status}"
    exit 1
    ;;
esac
