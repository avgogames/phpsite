#!/usr/bin/env bash
# End-to-end smoke test against a running server with seeded data.
# Usage: tests/smoke.sh [base_url]   (default http://127.0.0.1:8000)
# Requires: curl. Admin credentials come from ADMIN_EMAIL / ADMIN_PASSWORD (defaults match .env.example).
set -euo pipefail

BASE="${1:-http://127.0.0.1:8000}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-change-me-now}"
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
pass=0

check() { # check <description> <expected> <actual>
  if [[ "$3" == *"$2"* ]]; then pass=$((pass + 1)); echo "ok   $1"; else echo "FAIL $1 (expected '$2', got '$3')"; exit 1; fi
}
token() { curl -s -b "$1" -c "$1" "$BASE$2" | sed -n 's/.*name="_token" value="\([a-f0-9]*\)".*/\1/p' | head -1; }
post() { # post <jar> <path> <data...> -> "status location"
  local jar=$1 path=$2; shift 2
  curl -s -o "$TMP/body" -w '%{http_code} %{redirect_url}' -b "$jar" -c "$jar" -X POST "$BASE$path" "$@"
}

C="$TMP/customer"; A="$TMP/admin"
email="smoke$(date +%s)$RANDOM@example.com"

check "home page renders" "200" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/")"
check "POST without CSRF token is rejected" "419" "$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/cart/add" -d product_id=1)"

t=$(token "$C" /register)
check "register" "303 $BASE/" "$(post "$C" /register --data-urlencode "_token=$t" -d name=Smoke --data-urlencode "email=$email" -d password=supersecret1 -d password_confirmation=supersecret1)"

pid=$(curl -s -b "$C" "$BASE/products/hex-bolts-m8x60-10" | sed -n 's/.*name="product_id" value="\([0-9]*\)".*/\1/p' | head -1)
t=$(token "$C" /products)
check "add to cart" "303 $BASE/cart" "$(post "$C" /cart/add -d "_token=$t" -d "product_id=$pid" -d quantity=3)"
check "cart shows the product" "Hex Bolts M8" "$(curl -s -b "$C" "$BASE/cart")"

t=$(token "$C" /checkout)
check "checkout validation" "303 $BASE/checkout" "$(post "$C" /checkout -d "_token=$t" -d ship_name=)"
check "validation error shown" "is required" "$(curl -s -b "$C" -c "$C" "$BASE/checkout")"
t=$(token "$C" /checkout)
out=$(post "$C" /checkout -d "_token=$t" -d ship_name=Smoke -d "ship_address=1 Main St" -d ship_city=Town -d ship_postcode=12345 -d ship_country=Nowhere)
check "place order" "303 $BASE/account/orders/" "$out"
order_url="${out#* }"; order_id="${order_url##*/}"
check "order page" "Order #$order_id" "$(curl -s -b "$C" "$BASE/account/orders/$order_id")"
check "cart emptied after order" "Your cart is empty" "$(curl -s -b "$C" "$BASE/cart")"
check "customer blocked from admin" "403" "$(curl -s -o /dev/null -w '%{http_code}' -b "$C" "$BASE/admin")"

t=$(token "$A" /login)
check "admin login" "303 $BASE/admin" "$(post "$A" /login -d "_token=$t" --data-urlencode "email=$ADMIN_EMAIL" --data-urlencode "password=$ADMIN_PASSWORD")"
check "admin sees order" "#$order_id" "$(curl -s -b "$A" "$BASE/admin/orders")"
t=$(token "$A" "/admin/orders/$order_id")
post "$A" "/admin/orders/$order_id/status" -d "_token=$t" -d status=paid >/dev/null
check "order marked paid" "status-paid" "$(curl -s -b "$A" -c "$A" "$BASE/admin/orders/$order_id")"
t=$(token "$A" "/admin/orders/$order_id")
post "$A" "/admin/orders/$order_id/status" -d "_token=$t" -d status=pending >/dev/null
check "invalid transition refused" "cannot move from paid to pending" "$(curl -s -b "$A" -c "$A" "$BASE/admin/orders/$order_id")"

sku="SMOKE-$RANDOM$RANDOM"
t=$(token "$A" /admin/products/new)
out=$(post "$A" /admin/products -d "_token=$t" -d "name=Smoke Test Widget" -d "sku=$sku" -d category_id=1 -d price=2.50 -d stock=7 -d is_active=1)
check "admin creates product" "/edit" "$out"
check "new product is live" "Smoke Test Widget" "$(curl -s "$BASE/products?q=$sku")"
edit="${out#* }"; product_id=$(echo "$edit" | sed 's#.*/products/\([0-9]*\)/edit#\1#')
t=$(token "$A" "/admin/products/$product_id/edit")
check "admin deletes product" "303 $BASE/admin/products" "$(post "$A" "/admin/products/$product_id/delete" -d "_token=$t")"

echo "All $pass checks passed."
