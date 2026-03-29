#!/usr/bin/env bash
# Runs PHPUnit tests for recent chat-related changes.
# ChatChangesRealAccountTest logs in existing adventurers (no RefreshDatabase) — needs real accounts in DB.
# Real-accounts only: php artisan test tests/Feature/ChatChangesRealAccountTest.php
# Usage: ./scripts/test-chat-changes.sh
# Optional: ./scripts/test-chat-changes.sh --filter draw

set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

php artisan test \
  tests/Feature/ChatChangesRegressionTest.php \
  tests/Feature/CustomGameFlowTest.php \
  tests/Feature/ChatChangesRealAccountTest.php \
  "$@"
