#!/bin/sh
# Local development server: http://localhost:8000
cd "$(dirname "$0")"
exec php -d post_max_size=80M -d upload_max_filesize=80M -d memory_limit=256M \
    -S "${HOST:-localhost}:${PORT:-8000}" -t public public/index.php
