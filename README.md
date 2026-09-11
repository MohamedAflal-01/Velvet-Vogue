# 🛒 E-Commerce Platform

A full-stack **E-Commerce Web Application** developed using **PHP, HTML, CSS, JavaScript, MySQL, and Bootstrap**. The platform provides a complete online shopping experience with product management, customer accounts, shopping cart functionality, order processing, payment integration, product reviews, and a dedicated administration panel.

---

## 📌 Project Overview

The E-Commerce Platform is a database-driven web application designed to provide customers with a convenient and secure online shopping experience.

Customers can browse products, view detailed product information, add items to their shopping cart, place orders, make payments through the integrated payment system, and submit reviews and ratings for purchased products.

The system also includes a dedicated **Admin Panel** that allows administrators to manage products, categories, customers, orders, payments, reviews, and other important aspects of the online store.

The application combines a responsive frontend with a PHP backend and MySQL database to provide an organized and efficient e-commerce solution.

---

## ✨ Key Features

### 🛍️ Product Management

- Browse available products
- View product details
- Display product images, prices, and descriptions
- Organize products into categories
- Manage product availability
- Search and filter products

### 🛒 Shopping Cart

- Add products to cart
- Update product quantities
- Remove products from cart
- Automatically calculate subtotals
- Calculate the total order amount
- Review cart before checkout

### 💳 Payment Integration

- Integrated online payment functionality
- Secure checkout workflow
- Process customer payments
- Store payment-related information
- Connect payments with customer orders
- Display payment status

### 📦 Order Management

- Place customer orders
- Generate order records
- Store order details
- Track order status
- View previous orders
- Manage customer order information

### ⭐ Product Reviews & Ratings

- Customers can submit product reviews
- Customers can provide ratings
- Display reviews on product pages
- Store reviews in the database
- Admin can manage submitted reviews

### 👤 Customer Management

- Customer registration and login
- Manage customer information
- Maintain customer order history
- Manage customer account information

### 🔐 Admin Panel

A dedicated administration section provides centralized control over the e-commerce platform.

Admin functionality includes:

- Manage products
- Manage product categories
- Manage customers
- Manage orders
- Manage payments
- Manage reviews
- Monitor product information
- Manage store-related data
- View important system information

### 📊 Admin Dashboard

The dashboard provides an overview of important e-commerce activities, including:

- Total products
- Total customers
- Total orders
- Revenue information
- Recent orders
- Payment information
- Product reviews
- Store activity

### 📱 Responsive Design

- Mobile-friendly interface
- Responsive layouts
- Bootstrap components
- Cross-device compatibility
- User-friendly navigation

---

## 🛠️ Technologies Used

| Technology | Purpose |
|------------|---------|
| **PHP** | Backend development and server-side logic |
| **HTML5** | Web page structure |
| **CSS3** | Styling and visual design |
| **JavaScript** | Client-side functionality and interactions |
| **MySQL** | Database management |
| **Bootstrap** | Responsive UI design |
| **Payment Gateway** | Online payment processing |

---

## 🏗️ System Architecture

```text
                    ┌──────────────────────┐
                    │      Customers       │
                    └──────────┬───────────┘
                               │
                               ▼
              ┌─────────────────────────────┐
              │       E-Commerce UI         │
              │   HTML / CSS / Bootstrap    │
              │       JavaScript            │
              └─────────────┬───────────────┘
                            │
                            ▼
              ┌─────────────────────────────┐
              │        PHP Backend          │
              │                             │
              │  Authentication             │
              │  Product Management         │
              │  Cart Management             │
              │  Order Processing            │
              │  Review Management           │
              │  Payment Processing          │
              └─────────────┬───────────────┘
                            │
                            ▼
              ┌─────────────────────────────┐
              │       MySQL Database        │
              │                             │
              │ Products                    │
              │ Customers                   │
              │ Orders                      │
              │ Payments                    │
              │ Reviews                     │
              │ Categories                  │
              └─────────────────────────────┘
                            ▲
                            │
              ┌─────────────┴───────────────┐
              │         Admin Panel         │
              │                             │
              │ Products | Orders           │
              │ Customers | Payments        │
              │ Reviews | Categories        │
              └─────────────────────────────┘
