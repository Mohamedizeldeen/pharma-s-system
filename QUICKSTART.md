# Quick Start Guide - Role-Based Dashboard

## Installation & Setup

1. **Run migrations and seed database**
```bash
php artisan migrate:fresh
php artisan db:seed --class=RoleBasedSeeder
```

2. **Start the development server**
```bash
php artisan serve
```

## Default Accounts

### Super Admin
- **Email:** admin@pharma.com
- **Password:** admin123
- **Access:** Full system management

### Pharmacy Owners
1. **El Ezaby Pharmacy**
   - Email: ahmed@pharma.com
   - Password: pharma123
   - Branches: 2 (Nasr City, Maadi)
   - Medicines: 5

2. **Seif Pharmacy**
   - Email: sara@pharma.com
   - Password: pharma123
   - Branches: 1 (San Stefano)
   - Medicines: 3 (including 1 low stock item)

### Regular Users
- mohamed@example.com / user123
- fatma@example.com / user123

## Testing the API

### 1. Login as Admin
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@pharma.com",
    "password": "admin123"
  }'
```

Save the token from response.

### 2. Access Admin Dashboard
```bash
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Login as Pharmacy Owner
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ahmed@pharma.com",
    "password": "pharma123"
  }'
```

### 4. Access Pharmacy Dashboard
```bash
curl -X GET http://localhost:8000/api/pharma/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Common Endpoints

### Admin Routes (require admin role)
```
GET  /api/admin/dashboard              - Overview statistics
GET  /api/admin/analytics?days=30      - Analytics
GET  /api/admin/users                  - List all users
POST /api/admin/pharmacies             - Create pharmacy
GET  /api/admin/pharmacies             - List pharmacies
GET  /api/admin/orders-statistics      - Order stats
```

### Pharma Routes (require pharma role)
```
GET  /api/pharma/dashboard             - Pharmacy overview
GET  /api/pharma/branches              - List my branches
POST /api/pharma/branches              - Create branch
GET  /api/pharma/medicines             - List my medicines
POST /api/pharma/medicines             - Add medicine
GET  /api/pharma/orders                - List my orders
PUT  /api/pharma/orders/{id}/status    - Update order status
```

## Role-Based Access Examples

### Admin Can:
✓ View all pharmacies, branches, orders
✓ Create/update/delete pharmacies
✓ Manage user roles
✓ View system-wide analytics
✓ Access all data

### Pharma Can:
✓ View only their own pharmacy data
✓ Manage their own branches
✓ Manage medicines in their branches
✓ View and process orders from their branches
✓ Cannot access other pharmacies' data

### User Can:
✓ Browse medicines
✓ Place orders
✓ View their order history
✓ Cannot access admin or pharma dashboards

## Authorization Checks

The system automatically verifies:
1. Authentication (via Sanctum token)
2. Role permission (via CheckRole middleware)
3. Resource ownership (pharma controllers verify ownership)

Example flow:
```
Request → auth:sanctum → role:pharma → Ownership Check → Success/403
```

## Testing Unauthorized Access

Try accessing admin endpoint with pharma token:
```bash
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer PHARMA_TOKEN"
```

Response: `403 Unauthorized. Required role: admin`

## Next Steps

1. ✓ Database seeded with test data
2. ✓ Role-based routes configured
3. ✓ Authorization middleware active
4. → Build frontend dashboard
5. → Add more features (reports, exports)
6. → Deploy to production

## Troubleshooting

### Issue: "Unauthenticated"
- Check if token is included in Authorization header
- Verify token hasn't expired

### Issue: "Unauthorized. Required role: X"
- Check user role in database
- Ensure using correct API prefix (/admin or /pharma)

### Issue: "No pharmacy associated with this account"
- Pharma role user must have a pharmacy record
- Check user_id in pharmas table

## File Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # Admin controllers
│   │   ├── Pharma/          # Pharma controllers
│   │   └── ...
│   └── Middleware/
│       └── CheckRole.php    # Role verification
├── Models/
│   └── User.php             # Role methods added
routes/
└── api.php                  # Role-based routes
database/
└── seeders/
    └── RoleBasedSeeder.php  # Test data
```

## Documentation Files
- `DASHBOARD_GUIDE.md` - Complete API documentation
- `QUICKSTART.md` - This file
- `SETUP_GUIDE.md` - WhatsApp integration setup
- `API_EXAMPLES.md` - WhatsApp webhook examples
