# API Examples & Testing

## Authentication

### Register User
```bash
POST /api/register
Content-Type: application/json

{
  "name": "Ahmed Mohamed",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "Ahmed Mohamed",
    "email": "ahmed@example.com"
  },
  "access_token": "1|abc123...",
  "token_type": "Bearer"
}
```

### Login
```bash
POST /api/login
Content-Type: application/json

{
  "email": "ahmed@example.com",
  "password": "password123"
}
```

---

## Pharmacy Management

### Create Pharmacy
```bash
POST /api/pharmas
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 1,
  "name": "صيدلية النور",
  "email": "info@alnoor-pharmacy.com",
  "main_address": "شارع الجامعة، القاهرة",
  "phone": "+201234567890"
}
```

### Get All Pharmacies
```bash
GET /api/pharmas
Authorization: Bearer {token}
```

---

## Branch Management

### Create Branch
```bash
POST /api/branches
Authorization: Bearer {token}
Content-Type: application/json

{
  "pharma_id": 1,
  "name": "فرع المعادي",
  "address": "شارع 9، المعادي، القاهرة",
  "phone": "+201234567891",
  "latitude": "29.9602",
  "longitude": "31.2569",
  "opening_hours": "08:00",
  "closing_hours": "23:00"
}
```

### Get Branches by Pharmacy
```bash
GET /api/branches/pharma/1
Authorization: Bearer {token}
```

---

## Medicine Management

### Create Medicine (with image)
```bash
POST /api/medicines
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "branch_id": 1,
  "pharma_id": 1,
  "name": "باراسيتامول 500 مجم",
  "scientific_name": "Paracetamol",
  "price": 15.50,
  "quantity": 100,
  "description": "مسكن للآلام وخافض للحرارة",
  "expiry_date": "2026-12-31",
  "image": [binary file]
}
```

### Search Medicines (Public)
```bash
GET /api/medicines-search?name=paracetamol&branch_id=1
```

**Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "باراسيتامول 500 مجم",
      "scientific_name": "Paracetamol",
      "price": "15.50",
      "quantity": 100,
      "image": "medicines/abc123.jpg",
      "branch": {
        "id": 1,
        "name": "فرع المعادي"
      },
      "pharma": {
        "id": 1,
        "name": "صيدلية النور"
      }
    }
  ],
  "total": 1
}
```

---

## Order Management

### Create Order
```bash
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 1,
  "branch_id": 1,
  "medicine_id": 1,
  "total_price": 45.50,
  "status": "pending",
  "order_items": [
    {
      "medicine_id": 1,
      "quantity": 2,
      "price": 15.50
    },
    {
      "medicine_id": 2,
      "quantity": 1,
      "price": 14.50
    }
  ]
}
```

### Get Order Statistics
```bash
GET /api/orders-statistics
Authorization: Bearer {token}
```

**Response:**
```json
{
  "total_orders": 150,
  "pending_orders": 25,
  "completed_orders": 120,
  "canceled_orders": 5,
  "total_revenue": "45250.00"
}
```

### Update Order Status
```bash
POST /api/orders/1/update-status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "completed"
}
```

---

## WhatsApp Webhook Examples

### Webhook Verification (GET)
```bash
GET /api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=your_token&hub.challenge=challenge_string
```

**Response:** `challenge_string`

### Text Message Webhook (Meta Format)
```json
POST /api/webhook/whatsapp
X-Hub-Signature-256: sha256=...

{
  "object": "whatsapp_business_account",
  "entry": [
    {
      "id": "123456",
      "changes": [
        {
          "value": {
            "messaging_product": "whatsapp",
            "metadata": {
              "display_phone_number": "1234567890",
              "phone_number_id": "123456789"
            },
            "contacts": [
              {
                "profile": {
                  "name": "Ahmed"
                },
                "wa_id": "201234567890"
              }
            ],
            "messages": [
              {
                "from": "201234567890",
                "id": "wamid.xyz123",
                "timestamp": "1234567890",
                "type": "text",
                "text": {
                  "body": "هل يتوفر paracetamol؟"
                }
              }
            ]
          },
          "field": "messages"
        }
      ]
    }
  ]
}
```

### Image Message Webhook (Meta Format)
```json
{
  "entry": [
    {
      "changes": [
        {
          "value": {
            "messages": [
              {
                "from": "201234567890",
                "id": "wamid.xyz123",
                "timestamp": "1234567890",
                "type": "image",
                "image": {
                  "id": "media_id_123",
                  "mime_type": "image/jpeg"
                }
              }
            ]
          }
        }
      ]
    }
  ]
}
```

### Location Message Webhook
```json
{
  "entry": [
    {
      "changes": [
        {
          "value": {
            "messages": [
              {
                "from": "201234567890",
                "id": "wamid.xyz123",
                "timestamp": "1234567890",
                "type": "location",
                "location": {
                  "latitude": 29.9602,
                  "longitude": 31.2569
                }
              }
            ]
          }
        }
      ]
    }
  ]
}
```

---

## Testing with Postman

### Import Collection

Create a Postman collection with these variables:

- `base_url`: `http://localhost:8000/api`
- `token`: (set after login)

### Environment Setup

1. Register user
2. Login and copy token
3. Set token in environment variables
4. Use `{{token}}` in Authorization header

---

## Testing WhatsApp Locally

### Using ngrok

```bash
# Start Laravel
php artisan serve

# Start ngrok (in another terminal)
ngrok http 8000

# Copy HTTPS URL (e.g., https://abc123.ngrok.io)
# Set webhook in WhatsApp dashboard:
# https://abc123.ngrok.io/api/webhook/whatsapp
```

### Manual Webhook Test

```bash
# Test webhook verification
curl "http://localhost:8000/api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=your_token&hub.challenge=test123"

# Test text message (without signature for local testing)
curl -X POST http://localhost:8000/api/webhook/whatsapp \
  -H "Content-Type: application/json" \
  -d '{
    "entry": [{
      "changes": [{
        "value": {
          "contacts": [{"profile": {"name": "Test"}, "wa_id": "123"}],
          "messages": [{
            "from": "123",
            "id": "msg1",
            "timestamp": "1234567890",
            "type": "text",
            "text": {"body": "paracetamol"}
          }]
        }
      }]
    }]
  }'
```

---

## Expected Responses

### Successful Medicine Search Flow

1. User sends: "هل يتوفر paracetamol؟"
2. System responds:

```
🔍 نتائج البحث عن: *paracetamol*

وجدت 3 صيدلية قريبة منك:

📍 *1. صيدلية النور - فرع المعادي*
   💊 الدواء: باراسيتامول 500 مجم
   💰 السعر: 15.50 ج.م
   📦 متوفر: 100 عبوة
   📏 المسافة: 1.2 كم (~5 دقيقة)
   📞 الهاتف: +201234567891
   🕒 مواعيد العمل: 08:00 - 23:00

📍 *2. صيدلية الشفاء - فرع مدينة نصر*
   💊 الدواء: Paracetamol 500mg
   💰 السعر: 14.00 ج.م
   📦 متوفر: 50 عبوة
   📏 المسافة: 2.5 كم (~10 دقيقة)
   📞 الهاتف: +201234567892
   🕒 مواعيد العمل: 09:00 - 22:00
```

3. Followed by a static map image showing locations
4. Interactive buttons: [🗺️ الاتجاهات] [📞 اتصال] [🛒 حجز]

### Medicine Not Found

```
عذراً، لم أجد الدواء "xyz123" في الصيدليات القريبة منك. يرجى التحقق من الاسم والمحاولة مرة أخرى.
```

### Location Required

```
يرجى مشاركة موقعك الحالي للعثور على أقرب الصيدليات.
```

---

## Error Handling

### Invalid Credentials
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

### Validation Error
```json
{
  "message": "The name field is required. (and 1 more error)",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field is required."]
  }
}
```

### Unauthorized
```json
{
  "message": "Unauthenticated."
}
```
