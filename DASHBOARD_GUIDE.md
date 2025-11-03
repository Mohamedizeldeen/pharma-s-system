# Role-Based Dashboard System

This document explains the role-based access control system for the pharmacy management platform.

## User Roles

The system has three user roles:

1. **Admin** - Super administrator with full system access
2. **Pharma** - Pharmacy owner who can manage their own pharmacies, branches, and medicines
3. **User** - Regular customer who can browse medicines and place orders

## Admin Dashboard

### Access
- Base URL: `/api/admin/*`
- Required Role: `admin`
- Authentication: Sanctum token required

### Features

#### 1. Dashboard Overview
**GET** `/api/admin/dashboard`

Returns comprehensive statistics:
- Total users, pharmacies, branches, medicines, orders
- Users breakdown by role
- Recent users and orders
- Order status distribution
- Revenue statistics (total, pending, today, this month)
- Top selling medicines
- Low stock alerts

#### 2. Analytics
**GET** `/api/admin/analytics?days=30`

Returns:
- Orders trend (daily count and revenue)
- User growth trend
- Pharmacy performance comparison

#### 3. User Management
**GET** `/api/admin/users?role=pharma&search=john`

List all users with filters:
- Filter by role (admin, pharma, user)
- Search by name or email
- Pagination support

**PUT** `/api/admin/users/{userId}/role`
```json
{
  "role": "pharma"
}
```
Change user role.

**DELETE** `/api/admin/users/{userId}`

Delete user (cannot delete admin users).

#### 4. Pharmacy Management
**GET** `/api/admin/pharmacies?search=&status=`

List all pharmacies with branches and user info.

**POST** `/api/admin/pharmacies`
```json
{
  "name": "Pharmacy Name",
  "email": "owner@pharmacy.com",
  "main_address": "123 Main St",
  "phone": "1234567890",
  "password": "temporary_password"
}
```
Creates pharmacy and user account automatically.

**GET** `/api/admin/pharmacies/{pharmaId}`

Get pharmacy details with statistics:
- Total branches, medicines, orders
- Total revenue

**PUT** `/api/admin/pharmacies/{pharmaId}`

Update pharmacy details.

**DELETE** `/api/admin/pharmacies/{pharmaId}`

Delete pharmacy and associated user account.

**POST** `/api/admin/pharmacies/{pharmaId}/toggle-status`

Activate/suspend pharmacy (requires status column).

**GET** `/api/admin/pharmacies/{pharmaId}/performance?days=30`

Get pharmacy performance:
- Sales trend
- Top medicines
- Branch performance comparison

#### 5. Branch Monitoring
**GET** `/api/admin/branches?pharma_id=&search=`

List all branches with filters.

**GET** `/api/admin/branches/{branchId}`

Get branch details with statistics.

**GET** `/api/admin/branches/{branchId}/performance?days=30`

Get branch performance data.

#### 6. Order Monitoring
**GET** `/api/admin/orders?status=&pharma_id=&branch_id=&from_date=&to_date=`

List all orders with comprehensive filters.

**GET** `/api/admin/orders/{orderId}`

Get order details.

**GET** `/api/admin/orders-statistics?days=30`

Returns:
- Overview (total, completed, pending, cancelled orders)
- Revenue statistics
- Orders by status
- Daily trend
- Top branches by revenue
- Top customers

**GET** `/api/admin/top-selling-medicines?days=30`

Get top 20 selling medicines across all pharmacies.

---

## Pharmacy Owner Dashboard

### Access
- Base URL: `/api/pharma/*`
- Required Role: `pharma`
- Authentication: Sanctum token required

### Features

#### 1. Dashboard Overview
**GET** `/api/pharma/dashboard`

Returns statistics for the pharmacy owner:
- Pharmacy details with branches
- Overview (branches, medicines, orders count)
- Revenue (total, today, this month)
- Recent orders
- Low stock alerts
- Top selling medicines

#### 2. Analytics
**GET** `/api/pharma/analytics?days=30`

Returns:
- Sales trend (daily)
- Branch comparison

#### 3. Profile Management
**PUT** `/api/pharma/profile`
```json
{
  "name": "Updated Pharmacy Name",
  "main_address": "New Address",
  "phone": "9876543210"
}
```

#### 4. Branch Management
**GET** `/api/pharma/branches`

List all branches owned by the pharmacy with medicine and order counts.

**POST** `/api/pharma/branches`
```json
{
  "branch_name": "Downtown Branch",
  "longitude": 31.2357,
  "latitude": 30.0444,
  "phone": "1234567890",
  "open_time": "08:00",
  "close_time": "22:00"
}
```

**GET** `/api/pharma/branches/{branchId}`

Get branch details (only if it belongs to the pharmacy).

**PUT** `/api/pharma/branches/{branchId}`

Update branch details.

**DELETE** `/api/pharma/branches/{branchId}`

Delete branch (prevented if there are active orders).

#### 5. Medicine Management
**GET** `/api/pharma/medicines?search=&branch_id=&stock_status=`

List medicines with filters:
- Search by name, scientific name, description
- Filter by branch (must belong to pharmacy)
- Filter by stock status (low, out, available)

**POST** `/api/pharma/medicines`
```json
{
  "branch_id": 1,
  "pharma_id": 1,
  "name": "Aspirin",
  "scientific_name": "Acetylsalicylic acid",
  "description": "Pain reliever",
  "quantity": 100,
  "price": 5.50,
  "image": "file upload"
}
```

**GET** `/api/pharma/medicines/{medicineId}`

Get medicine details.

**PUT** `/api/pharma/medicines/{medicineId}`

Update medicine (with optional image upload).

**POST** `/api/pharma/medicines/{medicineId}/update-stock`
```json
{
  "quantity": 50,
  "action": "add"
}
```
Actions: `set`, `add`, `subtract`

**DELETE** `/api/pharma/medicines/{medicineId}`

Delete medicine and its image.

**POST** `/api/pharma/medicines/bulk-import`
```
branch_id: 1
csv_file: medicines.csv
```
Import medicines from CSV file.

#### 6. Order Management
**GET** `/api/pharma/orders?status=&branch_id=&from_date=&to_date=&search=`

List orders with filters.

**GET** `/api/pharma/orders/{orderId}`

Get order details.

**PUT** `/api/pharma/orders/{orderId}/status`
```json
{
  "status": "processing"
}
```
Status options: `pending`, `processing`, `ready`, `completed`, `cancelled`

Note: Cancelling restores medicine quantities automatically.

**POST** `/api/pharma/orders/{orderId}/cancel`
```json
{
  "reason": "Out of stock"
}
```

**GET** `/api/pharma/orders-statistics?days=30`

Returns:
- Total orders, orders by status
- Revenue, average order value
- Top customers
- Daily trend

---

## Authorization Flow

1. **User logs in** → Receives Sanctum token
2. **Token included in requests** → `Authorization: Bearer {token}`
3. **Middleware validates**:
   - `auth:sanctum` → Checks if user is authenticated
   - `role:admin` → Checks if user has admin role
   - `role:pharma` → Checks if user has pharma role
4. **Controllers verify ownership** → Pharma controllers check if resources belong to the authenticated pharmacy owner

## Security Features

1. **Role-based access control** using middleware
2. **Ownership verification** in pharma controllers
3. **Cannot delete admin users** through API
4. **Cannot delete branches with active orders**
5. **Medicine quantity restoration** when orders are cancelled
6. **Image cleanup** when medicines are deleted

## Next Steps

1. Add `status` column to `pharmas` table for suspension feature
2. Implement WhatsApp notifications for order status changes
3. Add email notifications for new pharmacy accounts
4. Create frontend dashboard using the API
5. Add export features (PDF reports, CSV exports)
6. Implement advanced analytics with charts

## Testing

### Create Admin User
```bash
php artisan tinker
```
```php
$admin = App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'admin@pharma.com',
    'password' => bcrypt('password'),
    'role' => 'admin'
]);
```

### Create Pharma User
Use admin endpoint:
```
POST /api/admin/pharmacies
```

Or manually:
```php
$user = App\Models\User::create([
    'name' => 'Pharmacy Owner',
    'email' => 'owner@pharmacy.com',
    'password' => bcrypt('password'),
    'role' => 'pharma'
]);

$pharma = App\Models\pharma::create([
    'user_id' => $user->id,
    'name' => 'My Pharmacy',
    'email' => 'owner@pharmacy.com',
    'main_address' => '123 Main St',
    'phone' => '1234567890'
]);
```
