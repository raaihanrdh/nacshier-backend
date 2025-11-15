#!/bin/bash

# NaCshier API Testing Script
# Usage: ./test_api.sh

BASE_URL="http://localhost:8000/api"
USERNAME="admin"
PASSWORD="raihan123"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=========================================="
echo "NaCshier API Testing Script"
echo "=========================================="
echo ""

# Function to print test result
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
    fi
}

# Function to extract token from response
extract_token() {
    echo $1 | grep -o '"token":"[^"]*' | cut -d'"' -f4
}

# 1. LOGIN
echo "1. Testing Authentication..."
echo "----------------------------------------"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"username\":\"$USERNAME\",\"password\":\"$PASSWORD\"}")

TOKEN=$(extract_token "$LOGIN_RESPONSE")

if [ -z "$TOKEN" ]; then
    echo -e "${RED}✗ Login failed${NC}"
    echo "Response: $LOGIN_RESPONSE"
    exit 1
else
    echo -e "${GREEN}✓ Login successful${NC}"
    echo "Token: ${TOKEN:0:20}..."
    echo ""
fi

# 2. GET USER PROFILE
echo "2. Testing Get User Profile..."
echo "----------------------------------------"
PROFILE_RESPONSE=$(curl -s -X GET "$BASE_URL/user-profile" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get User Profile"
echo "Response: $PROFILE_RESPONSE" | head -3
echo ""

# 3. GET ALL CATEGORIES
echo "3. Testing Categories..."
echo "----------------------------------------"
CATEGORIES_RESPONSE=$(curl -s -X GET "$BASE_URL/categories" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get All Categories"
echo "Response: $CATEGORIES_RESPONSE" | head -3
echo ""

# 4. GET ALL PRODUCTS
echo "4. Testing Products..."
echo "----------------------------------------"
PRODUCTS_RESPONSE=$(curl -s -X GET "$BASE_URL/products?page=1&per_page=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get All Products"
echo "Response: $PRODUCTS_RESPONSE" | head -3
echo ""

# 5. GET DASHBOARD SUMMARY
echo "5. Testing Dashboard..."
echo "----------------------------------------"
DASHBOARD_RESPONSE=$(curl -s -X GET "$BASE_URL/dashboard/summary" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Dashboard Summary"
echo "Response: $DASHBOARD_RESPONSE" | head -3
echo ""

# 6. GET TRANSACTIONS
echo "6. Testing Transactions..."
echo "----------------------------------------"
TRANSACTIONS_RESPONSE=$(curl -s -X GET "$BASE_URL/transactions?page=1&per_page=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get All Transactions"
echo "Response: $TRANSACTIONS_RESPONSE" | head -3
echo ""

# 7. GET CASHFLOWS
echo "7. Testing Cashflow..."
echo "----------------------------------------"
CASHFLOW_RESPONSE=$(curl -s -X GET "$BASE_URL/cashflows?page=1&per_page=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get All Cashflows"
echo "Response: $CASHFLOW_RESPONSE" | head -3
echo ""

# 8. GET CASHFLOW SUMMARY
echo "8. Testing Cashflow Summary..."
echo "----------------------------------------"
CASHFLOW_SUMMARY=$(curl -s -X GET "$BASE_URL/cashflow/summary" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Cashflow Summary"
echo "Response: $CASHFLOW_SUMMARY" | head -3
echo ""

# 9. GET PROFIT
echo "9. Testing Profit..."
echo "----------------------------------------"
PROFIT_RESPONSE=$(curl -s -X GET "$BASE_URL/profit?start_date=2025-01-01&end_date=2025-01-31" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Calculate Profit"
echo "Response: $PROFIT_RESPONSE" | head -3
echo ""

# 10. GET USERS (Admin Only)
echo "10. Testing Users (Admin Only)..."
echo "----------------------------------------"
USERS_RESPONSE=$(curl -s -X GET "$BASE_URL/users?page=1&per_page=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get All Users"
echo "Response: $USERS_RESPONSE" | head -3
echo ""

# 11. GET SHIFT (if kasir)
echo "11. Testing Shift Management..."
echo "----------------------------------------"
SHIFT_RESPONSE=$(curl -s -X GET "$BASE_URL/shift/active" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Active Shift"
echo "Response: $SHIFT_RESPONSE" | head -3
echo ""

# 12. GET CASHFLOW CATEGORIES
echo "12. Testing Cashflow Categories..."
echo "----------------------------------------"
CF_CATEGORIES=$(curl -s -X GET "$BASE_URL/cashflow/categories" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Cashflow Categories"
echo "Response: $CF_CATEGORIES"
echo ""

# 13. GET CASHFLOW METHODS
echo "13. Testing Cashflow Methods..."
echo "----------------------------------------"
CF_METHODS=$(curl -s -X GET "$BASE_URL/cashflow/methods" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Cashflow Methods"
echo "Response: $CF_METHODS"
echo ""

# 14. GET DASHBOARD LATEST TRANSACTIONS
echo "14. Testing Dashboard Latest Transactions..."
echo "----------------------------------------"
LATEST_TRX=$(curl -s -X GET "$BASE_URL/dashboard/latest-transactions?limit=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Latest Transactions"
echo "Response: $LATEST_TRX" | head -3
echo ""

# 15. GET TOP PRODUCTS
echo "15. Testing Top Products..."
echo "----------------------------------------"
TOP_PRODUCTS=$(curl -s -X GET "$BASE_URL/dashboard/top-products?limit=5&period=month" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Top Products"
echo "Response: $TOP_PRODUCTS" | head -3
echo ""

# 16. GET LOW STOCK
echo "16. Testing Low Stock Products..."
echo "----------------------------------------"
LOW_STOCK=$(curl -s -X GET "$BASE_URL/dashboard/low-stock?threshold=10" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Low Stock Products"
echo "Response: $LOW_STOCK" | head -3
echo ""

# 17. LOGOUT
echo "17. Testing Logout..."
echo "----------------------------------------"
LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/logout" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Logout"
echo "Response: $LOGOUT_RESPONSE"
echo ""

echo "=========================================="
echo -e "${GREEN}Testing Complete!${NC}"
echo "=========================================="

