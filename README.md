# Project 1: Sneakd Up Apparel - Sales, Inventory, and Employee Management System

## Project Overview
Sneakd Up Apparel is a web-based Sales and Inventory Management System with an integrated Employee Roster and Chatbot module. Developed for a retail shop specializing in sneakers and apparel located in Parañaque City, the platform modernizes daily operations by transitioning manual, paper-based workflows into an automated digital environment.   

---

## Problem Statement
The shop previously relied heavily on manual recording methods (handwritten logs and paper-based tracking) for sales transactions, inventory monitoring, and staff shift scheduling. These traditional processes resulted in several critical challenges:   
* **Data Inaccuracies & Errors:** Human error during manual entry led to mismatched inventory counts and inaccurate sales reporting.   
* **Scheduling Conflicts:** Managing employee work rosters manually caused overlapping shifts, poor staffing coordination, and tracking difficulties.   
* **Operational Delays:** Compiling daily/monthly sales summaries and manually checking low-stock inventory items required excessive time and effort from management.   

---

## Objectives

### General Objective
To design and develop a comprehensive Sales, Inventory, and Employee Management System that automates daily sales recording, updates stock levels dynamically, and streamlines staff scheduling through a dedicated roster module.   

### Specific Objectives
* Maintain accurate records of shoe, sneaker, and apparel inventory (categories, sizes, prices, variants, and stock quantities).   
* Record daily sales transactions digitally and generate real-time daily and monthly sales reports.   
* Automatically deduct sold quantities from inventory upon transaction completion.   
* Monitor stock levels continuously and flag low-stock items via automated alerts.   
* Provide secure role-based access control for administrators, employees, and customers.   
* Manage staff work schedules, shifts, and attendance logs systematically.   

---

## Target Users
* **Administrators / Owners:** Manage overall store operations, system users, employee rosters, product inventory, and comprehensive sales reports.
* **Employees / Staff:** Process point-of-sale transactions, view individual work schedules, log attendance, and monitor stock availability.   
* **Customers:** Browse sneaker and apparel products, view detailed specifications/images, and interact with the automated support chatbot.   

---

## Technologies Used
* **Frontend:** HTML5, CSS3, JavaScript   
* **Backend:** PHP   
* **Database Management System:** MySQL / MariaDB (phpMyAdmin)   
* **Development Methodology:** Agile Development Model (Iterative SDLC phases including requirements gathering, incremental coding, and continuous testing)   

---

## Features
* **User Authentication & Role-Based Security:** Secure login system with hashed passwords (`password_hash`), verification codes, and distinct access roles (`admin`, `employee`, `customer`).   
* **Product Catalog & Visualization:** Dynamic product pages featuring detailed descriptions, pricing, categories, variants (size, color, stock), and image previews.   
* **Sales & POS Module:** Real-time transaction processing that generates unique receipt numbers, records buyer/employee actions, and updates financial totals instantly.   
* **Automated Inventory Tracking:** Real-time stock deductions upon sale completion and automated low-stock flagging mechanisms.   
* **Employee Roster & Attendance Module:** Shift scheduling, work date assignments, and time-in/time-out attendance monitoring.   
* **Customer Support Chatbot:** Integrated virtual assistant providing instant replies and guidance while browsing catalog items.   
* **Contact & Feedback Management:** Stores user feedback and inquiries submitted through site contact channels.

---

## System Architecture
The application follows a traditional Client-Server Architecture:
1. **Presentation Layer:** Built with HTML, CSS, and JavaScript running inside modern web browsers across desktop and mobile devices.   
2. **Application Logic Layer:** Powered by PHP scripts handling HTTP requests, session management, business rules, form processing, and database transactions.
3. **Database Layer:** Structured relational database hosted on MySQL/MariaDB (`u191994003_sneakdup`) ensuring data integrity via foreign key constraints and cascade deletions.

---

## Database Design
The database consists of 7 core relational tables:

### 1. `users`
Stores account profiles, credentials, and access permissions.
* `id` (INT, PK, Auto Increment)
* `fullname` (VARCHAR 100)
* `email` (VARCHAR 100, Unique)
* `password` (VARCHAR 255)
* `role` (ENUM: 'admin', 'employee', 'customer')
* `status` (ENUM: 'active', 'blocked')
* `verification_code` (VARCHAR 6)
* `created_at` (TIMESTAMP)

### 2. `products`
Maintains catalog information for items sold in-store.
* `id` (INT, PK, Auto Increment)
* `name` (VARCHAR 255)
* `category` (VARCHAR 50)
* `description` (TEXT)
* `price` (DECIMAL 10,2)
* `image` (VARCHAR 255)
* `is_promo_eligible` (TINYINT 1)
* `is_popular` (TINYINT 1)
* `created_at` (TIMESTAMP)

### 3. `product_variants`
Tracks specific attributes (sizes, colors, and stock availability) for each product.
* `id` (INT, PK, Auto Increment)
* `product_id` (INT, FK referencing `products.id`, ON DELETE CASCADE)
* `size` (VARCHAR 20)
* `color` (VARCHAR 50)
* `image` (VARCHAR 255)
* `stock` (INT)

### 4. `sales`
Logs completed checkout records and receipt details.
* `id` (INT, PK, Auto Increment)
* `receipt_no` (VARCHAR 50)
* `user_id` (INT)
* `product_name` (VARCHAR 255)
* `variant_info` (VARCHAR 100)
* `quantity` (INT)
* `total_price` (DECIMAL 10,2)
* `sale_date` (TIMESTAMP)

### 5. `employee_schedules`
Organizes staff shift allocations by date.
* `id` (INT, PK, Auto Increment)
* `user_id` (INT)
* `work_date` (DATE)
* `created_at` (TIMESTAMP)
* *Constraint:* Unique combination of `user_id` and `work_date`.

### 6. `attendance`
Monitors staff working hours via time-in and time-out logs.
* `id` (INT, PK, Auto Increment)
* `user_id` (INT)
* `date` (DATE)
* `time_in` (DATETIME)
* `time_out` (DATETIME)

### 7. `contact_messages`
Stores customer inquiries and feedback messages submitted through the website.
* `id` (INT, PK, Auto Increment)
* `name` (VARCHAR 100)
* `email` (VARCHAR 100)
* `subject` (VARCHAR 150)
* `message` (TEXT)
* `created_at` (TIMESTAMP)

---

## Installation & Setup
1. **Prerequisites:** Install a local server environment supporting PHP and MySQL (e.g., XAMPP, WAMP, or LAMP stack).
2. **Clone the Repository:**
   ```bash
   git clone [https://github.com/your-username/project-1-sneakdup.git](https://github.com/your-username/project-1-sneakdup.git)

   ## Installation & Setup

1. **Deploy Source Files:** Move the project directory into your web server's root folder (e.g., `htdocs` for XAMPP).

2. **Configure Database:**
   - Open phpMyAdmin: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
   - Create a new database named `u191994003_sneakdup`.
   - Import the provided SQL schema and dump file into the newly created database.

3. **Run the Application:** Open your web browser and navigate to:

   ```text
   http://localhost/project-1-sneakdup/
   ```

## Usage

- **Customer Flow:** Register an account or log in, browse sneaker and apparel collections using filter options, view variant specifications, and utilize the chatbot for assistance.
- **Staff/Admin Flow:** Log in with administrative credentials to process point-of-sale transactions, check low-stock flags, update product variations, manage employee work calendars, and review generated sales reports.

## Testing

The system underwent rigorous evaluation involving internal users (the shop owner and 4 staff members) and external users (200 customer respondents).

- **Functional Testing:** Verified that sales transactions automatically deduct variant stock levels and record receipts correctly.
- **Usability Testing:** Evaluated using Likert-scale questionnaires, resulting in high satisfaction ratings (e.g., Product Visualization weighted mean: 4.655; Roster Management & Sales Module weighted mean: 5.0).
- **Security & Reliability Testing:** Confirmed stable user sessions, secure password encryption, and error-free role-based authentication.

## Limitations

- Designed specifically for small-scale retail operations, such as Sneakd Up Apparel, and may require structural scaling for multi-branch retail chains.
- Focuses strictly on internal point-of-sale, stock tracking, and employee scheduling without built-in third-party payment gateway integration or automated supplier procurement.
- System performance and inventory accuracy depend heavily on precise manual data entry by authorized staff.

## Future Improvements

- Integration of barcode or QR code scanning for faster checkout and inventory audits.
- Expansion into a full e-commerce platform with online payment processing and customer delivery tracking.
- Automated payroll calculation linked directly with the employee attendance and scheduling modules.

## Developer Information

Developed by **Software Engineering Students – Datamex College of Saint Adeline**

- Al-saafin, Abdulrhaman H.
- Al-saafin, Mohammad H.
- Besmonte, John Marc
- Dajay, Chanalie
- Lagajino, Lester
- Malsi, John Benedick
- Perrera, Dustin
- Rivero, Nurul Wahida
