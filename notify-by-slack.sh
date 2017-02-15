#!/bin/bash
set -e
set -u

if [ -z "$*" ]; then
	echo "Give me message"
	exit 1
fi

DT=$( date "+[%Y-%m-%d %H:%M:%S]" )
MSG="$*"

res=$( curl -X POST --data-urlencode "payload={\"channel\": \"#server_alerts\", \"username\": \"devbot\", \"text\": \"${DT} $*\", \"icon_emoji\": \":ghost:\"}" https://hooks.slack.com/services/XX/YY/ZZ )

echo '${res}'
