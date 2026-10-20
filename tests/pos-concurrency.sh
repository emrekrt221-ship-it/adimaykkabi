#!/usr/bin/env bash
# Two real WP-CLI cashier processes contend for the same WooCommerce stock.
set -euo pipefail
WP_DIR="${WP_DIR:-/tmp/adim-wordpress}"
WPCLI="${WPCLI:-/tmp/wp-cli.phar}"
ROOT="$PWD"
TMP_DIR=$(mktemp -d /tmp/adim-pos-race.XXXXXX)
trap 'rm -rf "$TMP_DIR"' EXIT

run_case() {
  local stock="$1"
  local expected_after="$2"
  local same_key="$3"
  local sku key1 key2 gate rc1 rc2 okay error
  sku=$(ADIM_RACE_STOCK="$stock" php "$WPCLI" eval-file "$ROOT/tests/pos-race-setup.php" --path="$WP_DIR" --allow-root | tail -n 1)
  key1=$(cat /proc/sys/kernel/random/uuid)
  key2=$(cat /proc/sys/kernel/random/uuid)
  if [[ "$same_key" == "yes" ]]; then key2="$key1"; fi
  gate="$TMP_DIR/go-$stock-$same_key"
  rm -f "$gate"
  ADIM_RACE_SKU="$sku" ADIM_RACE_KEY="$key1" ADIM_RACE_GATE="$gate" php "$WPCLI" eval-file "$ROOT/tests/pos-race-worker.php" --path="$WP_DIR" --allow-root > "$TMP_DIR/a.log" 2>&1 &
  local first_pid=$!
  ADIM_RACE_SKU="$sku" ADIM_RACE_KEY="$key2" ADIM_RACE_GATE="$gate" php "$WPCLI" eval-file "$ROOT/tests/pos-race-worker.php" --path="$WP_DIR" --allow-root > "$TMP_DIR/b.log" 2>&1 &
  local second_pid=$!
  sleep 2
  touch "$gate"
  rc1=0; rc2=0
  wait "$first_pid" || rc1=$?
  wait "$second_pid" || rc2=$?
  cat "$TMP_DIR/a.log" "$TMP_DIR/b.log"
  if [[ "$rc1" -ne 0 && "$rc1" -ne 2 ]] || [[ "$rc2" -ne 0 && "$rc2" -ne 2 ]]; then
    echo "Unexpected POS worker failure codes: $rc1 $rc2" >&2
    exit 1
  fi
  okay=$(grep -hc '^OK:' "$TMP_DIR"/{a,b}.log | awk '{s+=$1}END{print s+0}')
  error=$(grep -hc '^ERR:' "$TMP_DIR"/{a,b}.log | awk '{s+=$1}END{print s+0}')
  if [[ "$same_key" == "no" ]]; then
    if [[ "$okay" -ne 1 || "$error" -ne 1 ]]; then
      echo "Unique keys must yield exactly one successful sale and one blocked sale" >&2
      exit 1
    fi
  else
    if [[ "$okay" -lt 1 || $((okay+error)) -ne 2 ]]; then
      echo "Duplicate key requests failed to give consistent results" >&2
      exit 1
    fi
  fi
  ADIM_RACE_SKU="$sku" ADIM_RACE_KEY1="$key1" ADIM_RACE_KEY2="$key2" ADIM_RACE_EXPECT_STOCK="$expected_after" \
    php "$WPCLI" eval-file "$ROOT/tests/pos-race-verify.php" --path="$WP_DIR" --allow-root
}

run_case 1 0 no
run_case 2 1 yes
echo 'PASS: POS last-item contention and duplicate-key concurrency'
