# 💊 Medicine Finder & Pharmacy Inventory System

A comprehensive full-stack web and mobile solution designed to connect customers with nearby pharmacies while enabling pharmacy admins to efficiently manage medicine inventory in real time.

---

## 🧠 Overview

**Objective:**  
Empower pharmacy owners with a robust web dashboard to manage their inventory and provide customers with a seamless mobile app experience to search, locate, and request medicines effortlessly.

### Key Components:
- **Admin Web App:** A centralized platform to manage pharmacies, branches, medicines, and inventory.
- **Customer Mobile App:** A user-friendly app for searching medicines, locating nearby branches, and placing orders or reservations.

---

## 🚀 Features

### 👨‍💼 Admin Web App
- Manage pharmacies and their branches
- Add, edit, and organize medicines with stock details
- Upload medicine images and descriptions
- Monitor orders and generate inventory reports
- Secure role-based authentication for admins

### 📱 Customer Mobile App
- Search medicines by name, brand, or barcode
- Check stock availability at nearby pharmacy branches
- View pharmacy locations on an interactive map
- Place orders or reserve medicines for pickup
- Receive real-time notifications for availability and order updates

---

## 🏗️ Tech Stack

| Layer              | Technology                     |
|--------------------|---------------------------------|
| **Backend / API**  | Laravel 11 (PHP 8.3)          |
| **Database**       | MySQL / MariaDB               |
| **Admin Dashboard**| Laravel Breeze, Blade |
| **Authentication** | Laravel Sanctum              |
| **Maps & Location**| Google Maps API              |
| **File Storage**   | AWS S3 / DigitalOcean Spaces |

---

## 🗄️ Database Schema (Summary)

- `users` → Admins and customers  
- `pharmacies` → Represents pharmacy brands or chains  
- `pharmacy_branches` → Individual branches with unique locations  
- `medicines` → Global medicine catalog  
- `pharmacy_inventories` → Stock details for each branch  
- `orders` → Customer orders  
- `order_items` → Medicines included in an order  
- `prescriptions` → Optional uploaded prescriptions  
- `notifications` → User notifications  
- `audit_logs` → Tracks admin changes to inventory  

---

## ⚙️ Installation & Setup

### Prerequisites
Ensure you have the following installed:
- **PHP 8.3** or higher
- **Composer** (Dependency Manager for PHP)
- **Node.js** and **npm** (for frontend dependencies)
- **MySQL** or **MariaDB** (Database)
- **Flutter** or **React Native CLI** (for mobile app development)
- **Git** (Version Control)

### 2️⃣ Install Dependencies
Navigate to the project directory and install backend dependencies:
```bash
composer install
```

Install frontend dependencies:
```bash
npm install
```

### 3️⃣ Configure Environment
Copy the `.env.example` file to `.env` and update the environment variables:
```bash
cp .env.example .env
```
Set up database credentials, mail configuration, and other environment-specific settings.

### 4️⃣ Generate Application Key
Run the following command to generate the application key:
```bash
php artisan key:generate
```

### 5️⃣ Run Migrations and Seed Database
Set up the database schema and seed initial data:
```bash
php artisan migrate --seed
```

### 6️⃣ Start Development Servers
Start the backend server:
```bash
php artisan serve
```

For the admin dashboard, compile assets:
```bash
npm run dev
```



### 7️⃣ Access the Application
- **Admin Dashboard:** Visit `http://localhost:8000` in your browser.

---
Follow these steps to set up the project locally. For production deployment, refer to the deployment guide in the documentation.

### 1️⃣ Clone the Repository
```bash
git clone https://github.com/your-username/medicine-finder.git
cd medicine-finder
```

