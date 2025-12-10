# 🧭 Laravel + Vue.js Exchange Order Matching System

Technical Assessment – Full Stack (Laravel API + Vue.js + Real-Time)

## 📌 Overview

This project is a mini crypto exchange simulation built with **Laravel** (API backend) and **Vue.js** (frontend). It implements a safe and scalable system for managing balances, assets, limit orders, and a real-time matching engine using **Pusher + Laravel Broadcasting**.

The system focuses heavily on **financial data integrity**, **concurrency safety**, **atomic transactions**, and **instant UI updates**.

---

## 🚀 Tech Stack

### Backend Setup

* Laravel (latest)
* MySQL/PostgreSQL
* Laravel Broadcasting (Pusher)
* Laravel Sanctum (Authentication)

### Frontend

* Vue.js 3 (Composition API)
* Vite
* TailwindCSS
* Pusher JS client

---

## 🛠 Features Implemented

### 1. User Wallet & Assets

Each user has:

* **USD balance**
* **Crypto assets** (BTC, ETH)
* **Locked balances** (for open orders)

---

### 2. Limit Orders (Buy/Sell)

API supports:

* Creating limit buy orders
* Creating limit sell orders
* Canceling open orders
* Viewing orderbook

All logic uses:

* **Database transactions**
* **Row-level locking**
* **Race-condition safe checks**

---

### 3. Matching Engine (Full Match Only)

Rules implemented:

* BUY matches first SELL with `sell.price <= buy.price`
* SELL matches first BUY with `buy.price >= sell.price`
* No partial fills (strict full match)
* Commission applied at **1.5%**

Commission is deducted from:

* **Buyer’s USD** (chosen consistently)

Matching handled by:

* Internal service class
* Triggered synchronously when a new order is created

---

### 4. Real-Time Notifications (Pusher)

When a match occurs:

* `OrderMatched` event is broadcast
* Both users receive:

  * Updated wallet balances
  * Updated asset amounts
  * Updated order statuses

Frontend listens on:
`private-user.{id}`

---

## 📡 API Endpoints

### 🔐 Authentication

| Method | Endpoint      | Description                |
| ------ | ------------- | -------------------------- |
| POST   | `/api/login`  | Login using email/password |
| POST   | `/api/logout` | Logout                     |

---

### 👤 Profile

| Method | Endpoint       | Description                            |
| ------ | -------------- | -------------------------------------- |
| GET    | `/api/profile` | Returns USD + asset balances + summary |

---

### 📈 Orders

| Method | Endpoint                  | Description                         |
| ------ | ------------------------- | ----------------------------------- |
| GET    | `/api/orders?symbol=BTC`  | List user's open orders + orderbook |
| POST   | `/api/orders`             | Create a buy/sell limit order       |
| POST   | `/api/orders/{id}/cancel` | Cancel an open order                |

---

## 📟 Frontend Screens

### 1. Limit Order Form

Includes:

* Symbol selector (BTC/ETH)
* Side (BUY/SELL)
* Price input
* Amount input
* Instant USD/volume preview

### 2. Wallet + Orders Page

Shows:

* USD balance
* Asset balances
* Open orders
* Filled orders
* Cancelled orders
* Real-time updates when:

  * A match happens
  * Balance changes
  * Order status updates

---

## ⚙️ Setup Instructions

### Backend

```plaintext
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
```

#### Start the server

```plaintext
php artisan serve
```

### Start queue worker (if used)

```plaintext
php artisan queue:work
```

---

### Frontend Setup

```plaintext
npm install
npm run dev
```

---

## 🔒 Concurrency & Safety Measures

This project uses:

### ✔ Database transactions (`DB::transaction()`)

Ensures wallet + asset updates are atomic.

### ✔ `FOR UPDATE` row locking

Prevents race conditions on:

* Balances
* Asset rows
* Orders

### ✔ Validation of all financial values

* Prevent negative balances
* Prevent floating-point drift (decimal fields used)

---

## 📬 Event Broadcasting

On match:

```plaintext
OrderMatched {
  buyer_id
  seller_id
  symbol
  price
  amount
  fee
}
```

Broadcast channels:

* `private-user.{id}`

---

## 📦 Deployment Notes

* Use Supervisor for queues
* Pusher credentials must be set in `.env`
* Use HTTPS for broadcasting
* Ensure DB isolation level is `REPEATABLE READ` or higher
