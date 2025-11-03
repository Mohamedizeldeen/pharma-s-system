# Setup and Installation Guide

## ✅ Completed Implementation

### 1. Core Components Created

#### Controllers
- ✅ `WhatsAppWebhookController` - Handles incoming WhatsApp messages with signature verification
- ✅ `AuthController` - User authentication (login, register, logout)
- ✅ `PharmaController` - Pharmacy CRUD operations
- ✅ `BranchController` - Branch CRUD operations
- ✅ `MedicineController` - Medicine CRUD with image upload
- ✅ `PharmacyInventoryController` - Inventory management
- ✅ `OrderController` - Order management with statistics
- ✅ `OrderItemController` - Order items management

#### Jobs
- ✅ `ProcessIncomingMessage` - Main orchestration job for message processing

#### Services
- ✅ `OCRService` - Extract text from images/PDFs (Google Vision + Tesseract fallback)
- ✅ `STTService` - Transcribe audio to text (Whisper + Google Speech)
- ✅ `ExtractionService` - Extract medicine names using NLP
- ✅ `SearchService` - Fuzzy search with Levenshtein distance
- ✅ `DistanceService` - Calculate distances (Haversine + Google Maps)
- ✅ `WhatsAppService` - Send messages via WhatsApp (Meta/Twilio/360Dialog)

#### Validation Requests
- ✅ StorePharmaRequest / UpdatePharmaRequest
- ✅ StoreBranchRequest / UpdateBranchRequest
- ✅ StoreMedicineRequest / UpdateMedicineRequest
- ✅ StorePharmacyInventoryRequest / UpdatePharmacyInventoryRequest
- ✅ StoreOrderRequest / UpdateOrderRequest

#### Configuration
- ✅ `config/whatsapp.php` - WhatsApp provider configuration
- ✅ `config/services.php` - Google Maps, Vision, Speech, OpenAI integration
- ✅ API Routes with authentication

---

## 📋 Next Steps - Environment Setup

### 1. Install Required Packages

```bash
# Install Redis for queues
# Windows: Download from https://github.com/microsoftarchive/redis/releases
# Or use Docker:
docker run -d -p 6379:6379 redis:latest

# Install Tesseract OCR (optional, for local OCR)
# Windows: Download from https://github.com/UB-Mannheim/tesseract/wiki
```

### 2. Configure Environment Variables

Copy `.env.example` to `.env` and configure:

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your credentials:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pharma
DB_USERNAME=root
DB_PASSWORD=your_password

# Queue
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# WhatsApp (choose one provider)
WHATSAPP_PROVIDER=meta  # or twilio, 360dialog
WHATSAPP_VERIFY_TOKEN=your_verify_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_ACCESS_TOKEN=your_facebook_access_token
WHATSAPP_APP_SECRET=your_app_secret

# Google Services
GOOGLE_MAPS_API_KEY=your_google_maps_key
GOOGLE_VISION_API_KEY=your_google_vision_key
GOOGLE_SPEECH_API_KEY=your_google_speech_key

# OpenAI (for Whisper STT)
OPENAI_API_KEY=your_openai_key
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE pharma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Run migrations
php artisan migrate

# Create storage link
php artisan storage:link
```

### 4. Install Sanctum (API Authentication)

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 5. Start Development Servers

```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start queue worker
php artisan queue:work redis --tries=3

# Terminal 3 (optional): Monitor queues with Horizon
php artisan horizon
```

---

## 🔧 WhatsApp Provider Setup

### Option 1: Meta (Facebook) WhatsApp Business API

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app → Business → WhatsApp
3. Get your:
   - Phone Number ID
   - Access Token
   - App Secret
4. Configure webhook URL: `https://yourdomain.com/api/webhook/whatsapp`
5. Set verify token (match with `WHATSAPP_VERIFY_TOKEN`)
6. Subscribe to `messages` webhook

### Option 2: Twilio

1. Sign up at [Twilio](https://www.twilio.com/)
2. Get WhatsApp Sandbox credentials
3. Configure webhook: `https://yourdomain.com/api/webhook/whatsapp`

### Option 3: 360Dialog

1. Sign up at [360Dialog](https://www.360dialog.com/)
2. Get API key and configure webhook

---

## 🧪 Testing

### Test Webhook Locally with ngrok

```bash
# Install ngrok
# Windows: Download from https://ngrok.com/download

# Start ngrok tunnel
ngrok http 8000

# Copy the HTTPS URL (e.g., https://abc123.ngrok.io)
# Set as webhook URL in WhatsApp provider dashboard:
# https://abc123.ngrok.io/api/webhook/whatsapp
```

### Test API Endpoints

```bash
# Register user
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin User",
    "email": "admin@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'

# Use the returned token for authenticated requests
curl -X GET http://localhost:8000/api/pharmas \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 📱 Testing WhatsApp Flow

1. **Send text message**: "هل يتوفر paracetamol؟"
2. **Send image**: Photo of medicine box
3. **Send voice**: Voice message asking about medicine
4. **Share location**: Required for finding nearby pharmacies

Expected flow:
1. User sends message → Webhook receives
2. Job queued → ProcessIncomingMessage runs
3. Text extracted (OCR/STT if needed)
4. Medicine name extracted
5. Database searched
6. Distances calculated
7. Response sent with map and results

---

## 🔍 Monitoring & Debugging

### Check Queue Jobs

```bash
# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Clear failed jobs
php artisan queue:flush
```

### View Logs

```bash
# Real-time log viewing
tail -f storage/logs/laravel.log

# Windows
Get-Content storage/logs/laravel.log -Wait -Tail 50
```

---

## 🚀 Production Deployment

### 1. Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### 2. Set Up Supervisor (Queue Worker)

Create `/etc/supervisor/conf.d/pharma-worker.conf`:

```ini
[program:pharma-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/pharma/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/pharma/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pharma-worker:*
```

### 3. Configure HTTPS

WhatsApp webhooks require HTTPS. Use:
- Let's Encrypt SSL certificate
- Nginx or Apache reverse proxy
- Cloudflare (free SSL)

---

## 📊 Database Schema

Make sure all migrations are up to date:

```bash
php artisan migrate:status
```

Required tables:
- users
- pharmas
- branches
- medicines
- pharmacy_inventories
- orders
- order_items
- personal_access_tokens (Sanctum)

---

## 🎯 Testing Checklist

- [ ] Database connected and migrated
- [ ] Redis running for queues
- [ ] Queue worker running
- [ ] WhatsApp webhook verified
- [ ] Can register/login via API
- [ ] Can create pharmacy/branch/medicine
- [ ] Webhook receives messages
- [ ] OCR extracts text from images
- [ ] STT transcribes audio
- [ ] Search finds medicines
- [ ] Distance calculation works
- [ ] WhatsApp sends replies
- [ ] Maps are generated

---

## 📞 Support

For issues:
1. Check logs in `storage/logs/laravel.log`
2. Verify queue is running: `php artisan queue:work`
3. Test webhook with Postman/curl
4. Check environment variables are set correctly

---

## 🔐 Security Checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] Webhook signature verification enabled
- [ ] Rate limiting configured
- [ ] HTTPS enabled
- [ ] CORS configured properly
- [ ] API tokens expire appropriately
