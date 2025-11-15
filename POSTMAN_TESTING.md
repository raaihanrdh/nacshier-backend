# 📋 POSTMAN TESTING GUIDE - NaCshier API

## 🔧 Setup Awal

**Base URL:** `http://localhost:8000/api`

**Headers untuk semua request (kecuali login/forgot-password):**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## 🔐 1. AUTHENTICATION (Public Routes)

### 1.1 Login
**POST** `/login`

**Body (JSON):**
```json
{
  "username": "admin",
  "password": "password123"
}
```

**Response Success:**
```json
{
  "message": "Login berhasil",
  "token": "1|xxxxxxxxxxxx",
  "user": {
    "user_id": "USR001",
    "username": "admin",
    "name": "Admin",
    "level": "admin"
  },
  "shift": {
    "shift_id": "SF000001",
    "start_time": "2025-01-15 09:00:00",
    "end_time": null
  }
}
```

**Response Error:**
```json
{
  "message": "Username atau password salah"
}
```

---

### 1.2 Forgot Password
**POST** `/forgot-password`

**Body (JSON):**
```json
{
  "email": "admin@example.com"
}
```

**Response:**
```json
{
  "message": "Link reset password telah dikirim ke email Anda"
}
```

---

### 1.3 Reset Password
**POST** `/reset-password`

**Body (JSON):**
```json
{
  "token": "reset_token_here",
  "email": "admin@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

## 🔒 2. AUTHENTICATION (Protected Routes)

### 2.1 Logout
**POST** `/logout`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "message": "Logout berhasil"
}
```

---

### 2.2 Change Password
**POST** `/change-password`

**Headers:** `Authorization: Bearer {token}`

**Body (JSON):**
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword123",
  "new_password_confirmation": "newpassword123"
}
```

---

### 2.3 Get User Profile
**GET** `/user-profile`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "user_id": "USR001",
  "username": "admin",
  "name": "Admin",
  "level": "admin"
}
```

---

## 👥 3. USER MANAGEMENT (Admin Only)

### 3.1 Get All Users
**GET** `/users`

**Query Parameters (optional):**
- `page`: 1
- `per_page`: 10
- `search`: "keyword"

---

### 3.2 Get User by ID
**GET** `/users/{id}`

**Example:** `GET /users/USR001`

---

### 3.3 Create User
**POST** `/users`

**Body (JSON):**
```json
{
  "username": "kasir1",
  "name": "Kasir Satu",
  "password": "password123",
  "password_confirmation": "password123",
  "level": "user"
}
```

**Level options:** `admin`, `user`, `kasir`

---

### 3.4 Update User
**PUT** `/users/{id}`

**Body (JSON):**
```json
{
  "name": "Kasir Satu Updated",
  "level": "kasir"
}
```

---

### 3.5 Delete User
**DELETE** `/users/{id}`

---

### 3.6 Reset User Password
**POST** `/users/{id}/reset-password`

**Body (JSON):**
```json
{
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

## 📦 4. PRODUCTS

### 4.1 Get All Products
**GET** `/products`

**Query Parameters:**
- `page`: 1
- `per_page`: 10
- `search`: "product name"
- `category_id`: "CAT001"
- `sort`: "name" | "price" | "stock"
- `order`: "asc" | "desc"

---

### 4.2 Get Product by ID
**GET** `/products/{id}`

---

### 4.3 Create Product
**POST** `/products`

**Body (Form-Data):**
```
name: "Produk Baru"
selling_price: 25000
capital_price: 20000
stock: 100
category_id: "CAT001"
image: [file]
```

---

### 4.4 Update Product
**PUT** `/products/{id}`

**Body (Form-Data):**
```
name: "Produk Updated"
selling_price: 30000
capital_price: 25000
stock: 150
category_id: "CAT001"
image: [file] (optional)
_method: PUT
```

---

### 4.5 Delete Product
**DELETE** `/products/{id}`

---

### 4.6 Remove Product Image
**DELETE** `/products/{id}/image`

---

## 📁 5. CATEGORIES

### 5.1 Get All Categories
**GET** `/categories`

---

### 5.2 Get Category by ID
**GET** `/categories/{id}`

---

### 5.3 Create Category
**POST** `/categories`

**Body (JSON):**
```json
{
  "name": "Kategori Baru",
  "description": "Deskripsi kategori"
}
```

---

### 5.4 Update Category
**PUT** `/categories/{id}`

**Body (JSON):**
```json
{
  "name": "Kategori Updated",
  "description": "Deskripsi updated"
}
```

---

### 5.5 Delete Category
**DELETE** `/categories/{id}`

---

### 5.6 Get Products by Category
**GET** `/categories/{category_id}/products`

---

## 💰 6. TRANSACTIONS

### 6.1 Get All Transactions
**GET** `/transactions`

**Query Parameters:**
- `page`: 1
- `per_page`: 10
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"
- `shift_id`: "SF000001"

---

### 6.2 Get Transaction by ID
**GET** `/transactions/{id}`

---

### 6.3 Create Transaction
**POST** `/transactions`

**Body (JSON):**
```json
{
  "shift_id": "SF000001",
  "payment_method": "cash",
  "items": [
    {
      "product_id": "PRD001",
      "quantity": 2,
      "price": 25000
    },
    {
      "product_id": "PRD002",
      "quantity": 1,
      "price": 50000
    }
  ]
}
```

**Payment Methods:** `cash`, `transfer`, `qris`, `debit`, `credit`

---

### 6.4 Update Transaction
**PUT** `/transactions/{id}`

**Body (JSON):**
```json
{
  "payment_method": "transfer"
}
```

---

### 6.5 Delete Transaction
**DELETE** `/transactions/{id}`

---

### 6.6 Export Transactions to Excel
**GET** `/transactions/export/excel`

**Query Parameters:**
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"

---

### 6.7 Export Transactions to PDF
**GET** `/transactions/export/pdf`

**Query Parameters:**
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"

---

### 6.8 Get Transaction Items
**GET** `/transactions/{transactionId}/items`

---

### 6.9 Add Transaction Item
**POST** `/transactions/{transactionId}/items`

**Body (JSON):**
```json
{
  "product_id": "PRD001",
  "quantity": 2,
  "price": 25000
}
```

---

### 6.10 Delete Transaction Item
**DELETE** `/transactions/{transactionId}/items/{itemId}`

---

## 💵 7. CASHFLOW

### 7.1 Get All Cashflows
**GET** `/cashflows`

**Query Parameters:**
- `page`: 1
- `per_page`: 10
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"
- `category`: "income" | "expense"
- `type`: "operational" | "non-operational"
- `method`: "cash" | "transfer"

---

### 7.2 Get Cashflow by ID
**GET** `/cashflows/{id}`

---

### 7.3 Create Cashflow
**POST** `/cashflows`

**Body (JSON):**
```json
{
  "date": "2025-01-15",
  "description": "Pembelian bahan baku",
  "category": "expense",
  "type": "operational",
  "method": "cash",
  "amount": 500000
}
```

**Categories:** `income`, `expense`
**Types:** `operational`, `non-operational`
**Methods:** `cash`, `transfer`, `qris`, `debit`, `credit`

---

### 7.4 Update Cashflow
**PUT** `/cashflows/{id}`

**Body (JSON):**
```json
{
  "description": "Updated description",
  "amount": 600000
}
```

---

### 7.5 Delete Cashflow
**DELETE** `/cashflows/{id}`

---

### 7.6 Get Cashflow Summary
**GET** `/cashflow/summary`

**Query Parameters:**
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"

**Response:**
```json
{
  "total_income": 10000000,
  "total_expense": 5000000,
  "balance": 5000000
}
```

---

### 7.7 Get Cashflow Categories
**GET** `/cashflow/categories`

**Response:**
```json
{
  "categories": ["income", "expense"]
}
```

---

### 7.8 Get Cashflow Methods
**GET** `/cashflow/methods`

**Response:**
```json
{
  "methods": ["cash", "transfer", "qris", "debit", "credit"]
}
```

---

### 7.9 Export Cashflow to PDF
**GET** `/cashflow/export/pdf`

**Query Parameters:**
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"
- `category`: "income" | "expense" (optional)
- `type`: "operational" | "non-operational" (optional)
- `method`: "cash" | "transfer" (optional)

---

### 7.10 Export Cashflow to Excel
**GET** `/cashflow/export/excel`

**Query Parameters:** (sama seperti PDF)

---

## 📊 8. PROFIT

### 8.1 Calculate Profit
**GET** `/profit`

**Query Parameters:**
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"
- `period`: "daily" | "weekly" | "monthly" | "yearly"

**Response:**
```json
{
  "total_selling_price": 10000000,
  "total_capital_price": 7000000,
  "total_profit": 3000000,
  "profit_percentage": 30.0
}
```

---

### 8.2 Get Yearly Profit Chart
**GET** `/profit/yearly-chart`

**Query Parameters:**
- `year`: 2025

---

### 8.3 Get Profit Comparison
**GET** `/profit/comparison`

**Query Parameters:**
- `period1_start`: "2025-01-01"
- `period1_end`: "2025-01-31"
- `period2_start`: "2025-02-01"
- `period2_end`: "2025-02-28"

---

### 8.4 Export Profit to PDF
**GET** `/profit/export/pdf`

**Query Parameters:**
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"
- `period`: "daily" | "weekly" | "monthly" | "yearly"

---

### 8.5 Export Profit to Excel
**GET** `/profit/export/excel`

**Query Parameters:** (sama seperti PDF)

---

## 📈 9. DASHBOARD

### 9.1 Get Dashboard Summary
**GET** `/dashboard/summary`

**Response:**
```json
{
  "today_income": 5000000,
  "monthly_income": 150000000,
  "total_transactions": 150,
  "today_transactions": 25
}
```

---

### 9.2 Get Latest Transactions
**GET** `/dashboard/latest-transactions`

**Query Parameters:**
- `limit`: 10

---

### 9.3 Get Top Products
**GET** `/dashboard/top-products`

**Query Parameters:**
- `limit`: 10
- `period`: "today" | "week" | "month"

---

### 9.4 Get Sales Chart
**GET** `/dashboard/sales-chart`

**Query Parameters:**
- `period`: "daily" | "weekly" | "monthly"
- `start_date`: "2025-01-01"
- `end_date`: "2025-01-31"

---

### 9.5 Get Low Stock Products
**GET** `/dashboard/low-stock`

**Query Parameters:**
- `threshold`: 10

---

## 🕐 10. SHIFT MANAGEMENT (Kasir)

### 10.1 Get Active Shift
**GET** `/shift/active`

**Response:**
```json
{
  "shift_id": "SF000001",
  "user_id": "USR002",
  "start_time": "2025-01-15 09:00:00",
  "end_time": null,
  "shift_number": 1
}
```

---

### 10.2 Get or Create Shift
**GET** `/shift/get-or-create`

**Response:** (sama seperti Get Active Shift)

**Note:** Otomatis membuat shift baru jika belum ada berdasarkan waktu (Shift 1: 09:00-15:00, Shift 2: 15:00-10:00)

---

### 10.3 Close Shift
**POST** `/shift/close`

**Response:**
```json
{
  "message": "Shift berhasil ditutup",
  "shift": {
    "shift_id": "SF000001",
    "end_time": "2025-01-15 15:00:00"
  }
}
```

---

## 📝 11. REPORTS

### 11.1 Get Daily Income
**GET** `/daily-income`

**Query Parameters:**
- `date`: "2025-01-15"

---

### 11.2 Get Income Report
**GET** `/income-report/{period}`

**Period options:** `today`, `week`, `month`, `year`

**Example:** `GET /income-report/month`

---

## 🧪 TESTING CHECKLIST

### ✅ Authentication
- [ ] Login (Admin)
- [ ] Login (Kasir)
- [ ] Forgot Password
- [ ] Reset Password
- [ ] Logout
- [ ] Change Password
- [ ] Get User Profile

### ✅ User Management (Admin Only)
- [ ] Get All Users
- [ ] Get User by ID
- [ ] Create User
- [ ] Update User
- [ ] Delete User
- [ ] Reset User Password

### ✅ Products
- [ ] Get All Products
- [ ] Get Product by ID
- [ ] Create Product (with image)
- [ ] Update Product
- [ ] Delete Product
- [ ] Remove Product Image
- [ ] Filter by Category
- [ ] Search Products

### ✅ Categories
- [ ] Get All Categories
- [ ] Get Category by ID
- [ ] Create Category
- [ ] Update Category
- [ ] Delete Category
- [ ] Get Products by Category

### ✅ Transactions
- [ ] Get All Transactions
- [ ] Get Transaction by ID
- [ ] Create Transaction
- [ ] Update Transaction
- [ ] Delete Transaction
- [ ] Export to Excel
- [ ] Export to PDF
- [ ] Filter by Date Range
- [ ] Filter by Shift

### ✅ Cashflow
- [ ] Get All Cashflows
- [ ] Get Cashflow by ID
- [ ] Create Income
- [ ] Create Expense
- [ ] Update Cashflow
- [ ] Delete Cashflow
- [ ] Get Summary
- [ ] Export to PDF
- [ ] Export to Excel
- [ ] Filter by Category/Type/Method

### ✅ Profit
- [ ] Calculate Daily Profit
- [ ] Calculate Monthly Profit
- [ ] Get Yearly Chart
- [ ] Get Profit Comparison
- [ ] Export to PDF
- [ ] Export to Excel

### ✅ Dashboard
- [ ] Get Summary
- [ ] Get Latest Transactions
- [ ] Get Top Products
- [ ] Get Sales Chart
- [ ] Get Low Stock Products

### ✅ Shift Management
- [ ] Get Active Shift
- [ ] Get or Create Shift
- [ ] Close Shift

### ✅ Reports
- [ ] Get Daily Income
- [ ] Get Income Report (Today/Week/Month/Year)

---

## 🔑 Tips Testing

1. **Simpan Token:** Setelah login, simpan token di Postman Environment Variable
2. **Test Error Cases:** Coba dengan data invalid, missing fields, unauthorized access
3. **Test Pagination:** Coba dengan berbagai nilai `page` dan `per_page`
4. **Test Filters:** Coba semua kombinasi filter yang tersedia
5. **Test File Upload:** Pastikan upload image untuk products berfungsi
6. **Test Export:** Download dan cek file Excel/PDF yang dihasilkan
7. **Test Authorization:** Coba akses admin-only endpoints dengan user kasir

---

## 🐛 Common Errors

### 401 Unauthorized
- Pastikan token valid dan belum expired
- Pastikan header `Authorization: Bearer {token}` sudah benar

### 403 Forbidden
- Endpoint memerlukan level `admin`
- Pastikan user yang login adalah admin

### 422 Validation Error
- Cek semua required fields sudah diisi
- Cek format data sesuai (email, date, dll)

### 404 Not Found
- Pastikan ID resource valid
- Pastikan endpoint URL benar

---

**Happy Testing! 🚀**

