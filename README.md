# Azani Internet Service Provider Information System (Laravel + MySQL)

## 1. Setup
```bash
composer create-project laravel/laravel azani
cd azani
composer require barryvdh/laravel-dompdf
# copy the contents of this folder over the new project (overwrite routes/web.php, routes/console.php, DatabaseSeeder.php)
```
Edit `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=azani
DB_USERNAME=root
DB_PASSWORD=
```
```bash
php artisan migrate:fresh --seed
php artisan serve        # open http://127.0.0.1:8000
php artisan schedule:work   # optional: auto billing & fines
```

## 2. Business rules (config/azani.php)
| Rule | Value |
|---|---|
| Registration fee | KSh 8,500 |
| Installation fee | KSh 10,000 |
| Personal computer | KSh 40,000 each |
| Upgrade discount | 10% of the new plan |
| Overdue fine | 15% of the amount (after end of month) |
| Disconnection | unpaid after 10th of next month |
| Re-connection fee | KSh 1,000 |

Table 1 (bandwidth) and Table 2 (LAN nodes) are stored in `bandwidth_plans` and `lan_node_rates` (seeded).

## 3. Database design (ERD)
```
bandwidth_plans 1---* institutions 1---1 infrastructure_requirements
                         |  1
                         |---* upgrades (from_plan, to_plan -> bandwidth_plans)
                         |---* monthly_bills 1---* payments
                         |---* payments
lan_node_rates (lookup)
```
| Table | Key fields |
|---|---|
| institutions | name, category (primary/junior/senior/college), location, contact person (name, ID no., designation, phone, email), bandwidth_plan_id, status |
| infrastructure_requirements | users, computers available, has LAN, PCs/LAN nodes to buy, pc_cost, lan_cost, ready |
| upgrades | from/to plan, original cost, discount, discounted cost |
| monthly_bills | billing month, amount, due date, disconnection date, overdue fine, reconnection fee, paid |
| payments | type (registration, installation, monthly, overdue_fine, reconnection, computers, lan), amount, method, reference, date |

## 4. Task mapping
| Task | Where |
|---|---|
| 1 Register institution + contact | Register page (`InstitutionController@store`) |
| 2a Registration fee | captured on registration |
| 2b Installation fee | Institution page → *Capture installation payment* |
| 2c Monthly payments | Institution page → bills → *Pay* |
| 3a–d Lists | Reports: Registered, Defaulters, Disconnection Issues, Infrastructure |
| 4a Total installation cost | Report: Total Installation Cost |
| 4b PCs & LAN cost | Report: Assorted Services |
| 4c Upgraded charges | Report: Upgraded Services |
| 4d Charges/fines/reconnection by category | Report: By Category |
| 4e Aggregate per service per institution | Report: Aggregate |
| 5 Reports | Every report has a **PDF** button (DomPDF) |

All calculations live in `app/Services/BillingService.php`.

## 5. Monthly cycle
1. `php artisan azani:bill 2026-10` – creates bills for active institutions (discounted rate if upgraded).
2. `php artisan azani:overdue` – after month end adds 15% fine; after the 10th disconnects and adds KSh 1,000 re-connection fee.
3. Paying a bill records the monthly charge, fine and re-connection fee and reconnects the institution.
(The dashboard button runs both steps.)

## 6. Azani ISP case study summary

### Overview
Azani is a fictional company that provides internet services and infrastructure to learning institutions in Kenya: primary schools, junior schools, senior schools, and colleges.

### The problem
Azani currently uses a manual system of paper records and spreadsheets to record institutions, track connection status, monitor payments, and manage infrastructure. As demand for internet installations grows, this approach has become:

- **Inaccurate:** client records can be lost or incomplete.
- **Inefficient:** it is difficult to track paid and unpaid accounts.
- **Poor at defaulter tracking:** overdue accounts are not easy to identify.
- **Error-prone:** counting computers and LAN nodes is cumbersome.
- **Slow for reporting:** management lacks timely information for decisions.

### What the system must do
The case study proposes a Microsoft Access DBMS with the following capabilities:

| Function | Details |
|---|---|
| Registration | Register institutions and their contact-person details. |
| Subscriptions | Track monthly internet bandwidth packages and upgrades. |
| Payments | Capture registration, installation, and monthly payments. |
| Fines and fees | Calculate overdue fines, reconnection fees, and upgrade discounts. |
| Infrastructure | Track PCs and LAN nodes required by each institution and calculate installation costs. |
| Disconnection | Disconnect service for overdue accounts and restore it after the outstanding balance is paid. |
| Reports | Generate registered-institution, defaulter, disconnected-institution, and infrastructure-requirements reports. |

### Key business rules

- An unpaid bill at month-end incurs a **15% overdue fine**.
- Reconnection after payment incurs a **KSh 1,000 fee**.
- A bandwidth upgrade receives a **10% discount** on the upgraded package.
- Disconnected service is restored once the outstanding amount and fine have been paid.

### Feasibility and justification
The proposed system provides centralized data storage, automatic calculations, referential integrity, comprehensive reporting, and user authentication, at a lower long-term cost than the current manual approach.

> **Implementation note:** The case study describes a Microsoft Access proposal; this repository implements the Azani workflow as a Laravel and MySQL web application. In this implementation, overdue fines are processed after month-end and disconnection occurs after the 10th of the following month, as described in the monthly-cycle rules above.
