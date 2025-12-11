# Project Setup Guide

This README provides complete setup instructions for both the **Laravel backend** and the **Vue 3 frontend** using Pusher for real-time broadcasting.

---

## 📦 Requirements

* PHP 8.2+
* Composer
* Node.js 20+
* Yarn or NPM
* Laravel 12+
* Vue 3 + Vite
* Pusher account (app key, secret, cluster)

---

# 🚀 Backend Setup (Laravel)

## 1. Clone the Repository

```bash
git clone https://github.com/Abdqadr1/virgosoft.git
cd backend
```

## 2. Install Dependencies

```bash
composer install
```

## 3. Create Environment File

```bash
cp .env.example .env
```

Update the following fields in your `.env`:

```env
APP_URL=http://127.0.0.1:8000

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=your-cluster

BROADCAST_DRIVER=pusher
```

## 4. Generate Application Key

```bash
php artisan key:generate
```

## 5. Run Migrations

```bash
php artisan migrate --seed
```

## 6. Serve the Backend

```bash
composer run dev
```
This will serve the backend, start the queue and so on.
Backend will be available at: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**.
You can also view the API documentation page at: **[http://127.0.0.1:8000/docs/api](http://127.0.0.1:8000/docs/api)**.

---

# 🎨 Frontend Setup (Vue 3 + Vite)

## 1. Navigate to Frontend Folder

```bash
cd frontend
```

## 2. Install Dependencies

```bash
yarn
# OR
npm install
```

## 4. Create Environment Variables

Create a `.env` file:

```env
VITE_API_URL="http://127.0.0.1:8000/api"
VITE_PUSHER_APP_KEY=your-app-key
VITE_PUSHER_APP_CLUSTER=your-cluster
```

## 5. Serve the frontend

```bash
npm run dev
```

---

# ✅ Done

Both backend and frontend are now set up for real-time private channel broadcasting using Pusher Cloud.
