#!/usr/bin/env bash
# Real WP-CLI/PHP/MySQL process stops at each POS persistence boundary.
set -euo pipefail
WP_DIR="${WP_DIR:-/tmp/adim-wordpress}"
WPCLI="${WPCLI:-/tmp/wp-cli.phar}"
ROOT="$PWD"
for stage in ledger_claimed order_saved_before_link order_linked stock_reserved stock_reduced order_completed; do
  sku=$(ADIM_CRASH_STAGE="$stage" php "$WPCLI" eval-file "$ROOT/tests/pos-crash-setup.php" --path="$WP_DIR" --allow-root | tail -n 1)
  key=$(cat /proc/sys/kernel/random/uuid)
  set +e
  output=$(ADIM_CRASH_STAGE="$stage" ADIM_CRASH_KEY="$key" ADIM_CRASH_SKU="$sku" php "$WPCLI" eval-file "$ROOT/tests/pos-crash-worker.php" --path="$WP_DIR" --allow-root 2>&1)
  status=$?
  set -e
  echo "$output"
  if [[ "$status" -ne 86 ]] || [[ "$output" != *"ADIM_FAULT_INJECTED stage=$stage"* ]]; then
    echo "Fault not injected safely at $stage, process status: $status" >&2
    exit 1
  fi
  ADIM_CRASH_STAGE="$stage" ADIM_CRASH_KEY="$key" ADIM_CRASH_SKU="$sku" \
    php "$WPCLI" eval-file "$ROOT/tests/pos-crash-verify.php" --path="$WP_DIR" --allow-root
done
echo "PASS: all six POS crash persistence and idempotency scenarios"
