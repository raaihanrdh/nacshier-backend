#!/bin/bash

# NaCshier API Testing Script - Cashier User
# Usage: ./test_cashier.sh

BASE_URL="http://localhost:8000/api"
USERNAME="cashier"
PASSWORD="cashierpassword"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "=========================================="
echo "NaCshier API Testing - CASHIER USER"
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

# 1. LOGIN AS CASHIER
echo -e "${BLUE}1. Testing Cashier Login...${NC}"
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
    
    # Check if shift data is included
    if echo "$LOGIN_RESPONSE" | grep -q "shift"; then
        echo -e "${GREEN}✓ Shift data included in login response${NC}"
        echo "$LOGIN_RESPONSE" | grep -o '"shift_id":"[^"]*' | head -1
    fi
    echo ""
fi

# 2. GET USER PROFILE
echo -e "${BLUE}2. Testing Get Cashier Profile...${NC}"
echo "----------------------------------------"
PROFILE_RESPONSE=$(curl -s -X GET "$BASE_URL/user-profile" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Cashier Profile"
echo "Response: $PROFILE_RESPONSE"
echo ""

# 3. GET OR CREATE SHIFT
echo -e "${BLUE}3. Testing Shift Management...${NC}"
echo "----------------------------------------"
SHIFT_RESPONSE=$(curl -s -X GET "$BASE_URL/shift/get-or-create" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get or Create Shift"
echo "Response: $SHIFT_RESPONSE"
echo ""

# Extract shift_id if available (from data.shift_id)
SHIFT_ID=$(echo "$SHIFT_RESPONSE" | grep -o '"shift_id":"[^"]*' | head -1 | cut -d'"' -f4)
if [ ! -z "$SHIFT_ID" ]; then
    echo -e "${GREEN}✓ Shift ID: $SHIFT_ID${NC}"
    echo ""
fi

# 4. GET ACTIVE SHIFT
echo -e "${BLUE}4. Testing Get Active Shift...${NC}"
echo "----------------------------------------"
ACTIVE_SHIFT=$(curl -s -X GET "$BASE_URL/shift/active" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Active Shift"
echo "Response: $ACTIVE_SHIFT"
echo ""

# 5. GET ALL PRODUCTS (Cashier can view products)
echo -e "${BLUE}5. Testing Get Products...${NC}"
echo "----------------------------------------"
PRODUCTS_RESPONSE=$(curl -s -X GET "$BASE_URL/products?page=1&per_page=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get All Products"
echo "Response: $PRODUCTS_RESPONSE" | head -3
echo ""

# 6. GET CATEGORIES
echo -e "${BLUE}6. Testing Get Categories...${NC}"
echo "----------------------------------------"
CATEGORIES_RESPONSE=$(curl -s -X GET "$BASE_URL/categories" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get All Categories"
echo "Response: $CATEGORIES_RESPONSE" | head -3
echo ""

# 7. CREATE TRANSACTION (if shift exists)
# Try to get shift_id from active shift if not already set
if [ -z "$SHIFT_ID" ]; then
    SHIFT_ID=$(echo "$ACTIVE_SHIFT" | grep -o '"shift_id":"[^"]*' | head -1 | cut -d'"' -f4)
fi

if [ ! -z "$SHIFT_ID" ]; then
    echo -e "${BLUE}7. Testing Create Transaction...${NC}"
    echo "----------------------------------------"
    TRANSACTION_BODY="{\"shift_id\":\"$SHIFT_ID\",\"payment_method\":\"cash\",\"items\":[{\"product_id\":\"PR000001\",\"quantity\":1,\"price\":25000},{\"product_id\":\"PR000002\",\"quantity\":1,\"price\":10000}]}"
    TRANSACTION_RESPONSE=$(curl -s -X POST "$BASE_URL/transactions" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "$TRANSACTION_BODY")
    print_result $? "Create Transaction"
    echo "Response: $TRANSACTION_RESPONSE"
    echo ""
else
    echo -e "${YELLOW}⚠ Skipping Create Transaction (No active shift)${NC}"
    echo ""
fi

# 8. GET TRANSACTIONS
echo -e "${BLUE}8. Testing Get Transactions...${NC}"
echo "----------------------------------------"
TRANSACTIONS_RESPONSE=$(curl -s -X GET "$BASE_URL/transactions?page=1&per_page=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get All Transactions"
echo "Response: $TRANSACTIONS_RESPONSE" | head -3
echo ""

# 9. GET DASHBOARD SUMMARY
echo -e "${BLUE}9. Testing Dashboard...${NC}"
echo "----------------------------------------"
DASHBOARD_RESPONSE=$(curl -s -X GET "$BASE_URL/dashboard/summary" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Dashboard Summary"
echo "Response: $DASHBOARD_RESPONSE"
echo ""

# 10. GET LATEST TRANSACTIONS
echo -e "${BLUE}10. Testing Latest Transactions...${NC}"
echo "----------------------------------------"
LATEST_TRX=$(curl -s -X GET "$BASE_URL/dashboard/latest-transactions?limit=5" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Get Latest Transactions"
echo "Response: $LATEST_TRX" | head -3
echo ""

# 11. TEST ADMIN ONLY ENDPOINT (Should fail)
echo -e "${BLUE}11. Testing Admin-Only Endpoint (Should Fail)...${NC}"
echo "----------------------------------------"
USERS_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X GET "$BASE_URL/users" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
HTTP_CODE=$(echo "$USERS_RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)
if [ "$HTTP_CODE" = "403" ] || [ "$HTTP_CODE" = "401" ]; then
    echo -e "${GREEN}✓ Correctly rejected (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}✗ Should be rejected but got HTTP $HTTP_CODE${NC}"
fi
echo "Response: $USERS_RESPONSE" | head -3
echo ""

# 12. CLOSE SHIFT (if shift exists)
if [ ! -z "$SHIFT_ID" ]; then
    echo -e "${BLUE}12. Testing Close Shift...${NC}"
    echo "----------------------------------------"
    CLOSE_SHIFT_RESPONSE=$(curl -s -X POST "$BASE_URL/shift/close" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json")
    print_result $? "Close Shift"
    echo "Response: $CLOSE_SHIFT_RESPONSE"
    echo ""
else
    echo -e "${YELLOW}⚠ Skipping Close Shift (No active shift)${NC}"
    echo ""
fi

# 13. LOGOUT
echo -e "${BLUE}13. Testing Logout...${NC}"
echo "----------------------------------------"
LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/logout" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")
print_result $? "Logout"
echo "Response: $LOGOUT_RESPONSE"
echo ""

echo "=========================================="
echo -e "${GREEN}Cashier Testing Complete!${NC}"
echo "=========================================="

