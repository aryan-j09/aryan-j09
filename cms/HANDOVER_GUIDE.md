# SB Panchal CMS - Handover & Developer Guide

## Quick Summary

**SB Panchal CMS** is a sales & procurement management system for manufacturing. It tracks leads → quotations → proforma invoices → purchase orders → stock management → project allocation.

**Tech Stack:**
- Backend: PHP 7+ with MySQLi (direct SQL queries - no ORM)
- Frontend: AdminLTE 3 (Bootstrap 4), jQuery 3
- Database: MySQL 5.7+ / MariaDB (35 tables)
- Architecture: Page-centric MVC with centralized AJAX controller (Master.php)

**Key Pattern:**
```
User clicks on page → jQuery AJAX POST to classes/Master.php?f=function_name
→ Master.php executes function → Direct SQL INSERT/UPDATE/DELETE 
→ Returns JSON → jQuery updates page with DataTables/SweetAlert notifications
```

---

## System Architecture

### 19 Admin Modules (Color-Coded)

**🔵 SALES & CRM (Blue)** - Lead-to-Quote pipeline:
- **Leads** (`admin/leads/`) - Convert prospects to opportunities
- **❌ DEPRECATED: Quotations** (`admin/quotations/`) - ⚠️ NOT IN USE - NOT READY - DO NOT USE
- **❌ DEPRECATED: Quote Items** (`admin/quote_items/`) - ⚠️ NOT IN USE - NOT READY - DO NOT USE
- **Proforma Invoice** (`admin/proforma_invoice/`) - Convert quote to invoice (use this instead)
- **Clients** (`admin/clients/`) - Customer database

**🟢 PROCUREMENT & STOCK (Green)** - Supply chain:
- **Purchase Orders** (`admin/purchase_order/`) - Supplier POs from factory requirements
- **Stock** (`admin/stock/`) - Receiving & utilization tracking
- **PO Details** (`admin/po_details/`) - Link proforma invoice to factory POs
- **Back Orders** (`admin/back_order/`) - Manage unfulfilled orders
- **Suppliers** (`admin/supplier/`) - Vendor database

**🟣 INVENTORY (Purple)** - Master data:
- **Items** (`admin/maintenance/item.php`) - Product master catalog
- **Machine Items** (`admin/machine_items/`) - Spare parts / components

**🟠 PROJECTS (Orange)**:
- **Project Planner** (`admin/project_planner2/`) - Project tracking & resource allocation

**⚫ TASKS & ADMIN (Gray)**:
- **Tasks** (`admin/tasks/`) - Task assignments & tracking
- **Utility** (`admin/utility/`) - Utility supplier management
- **Users** (`admin/user/`) - System user accounts
- **Users(C)** (`admin/users(c)/`) - Client portal users
- **System Info** (`admin/system_info/`) - Settings & configuration

### Central Hub

**`classes/Master.php`** - Contains **100+ AJAX endpoints**
- All form submissions POST here with `?f=function_name` parameter
- Routes to appropriate function
- Executes direct SQL queries
- Returns JSON responses

**Example flow:**
```
admin/quotations/manage_quote.php → Click "Save Quote" button
→ jQuery.post('../../classes/Master.php?f=save_quotation', {data})
→ Master.php::save_quotation() function executes
→ INSERT INTO quotations ... UPDATE quotation_items ...
→ return json_encode(['status'=>'success', 'id'=>$quote_id])
→ jQuery updates DataTable, shows SweetAlert success message
```

---

## Key Classes & Files

### Core Classes (`classes/` folder)

| File | Purpose |
|------|---------|
| **Master.php** | 100+ AJAX endpoints - heart of the system |
| **DBConnection.php** | MySQL connection singleton |
| **Login.php** | Authentication (MD5 hashing - ⚠️ security risk) |
| **Users.php** | User management & permissions |
| **SystemSettings.php** | Global system config |
| **QRCodeGenerator.php** | QR code generation for POs |
| **SerialNumberGenerator.php** | Auto-generate serial numbers |

### Session & Security (`admin/inc/` folder)

| File | Purpose |
|------|---------|
| **sess_auth.php** | Check if user logged in (required on every admin page) |
| **header.php** | HTML header, navigation, styling |
| **footer.php** | HTML footer, scripts |

### Entry Points

| File | Purpose |
|------|---------|
| **index.php** | Public home page (if any) |
| **admin/home.php** | Admin dashboard (requires login) |
| **admin/index.php** | Admin login page |
| **admin/login.php** | Login form & handler |

---

## Database Overview

**35 Tables organized by function:**

### Sales Tables
- `leads` - Prospect contacts
- `quotations` - Price quotes
- `quotation_items` - Items in quote
- `proforma_invoice_list` - Invoice records
- `proforma_invoice_items` - Line items
- `clients` - Customer accounts

### Procurement Tables
- `purchase_order_list` - Supplier POs
- `po_items` - PO line items
- `po_details` - Factory PO details
- `supplier_list` - Vendor database
- `back_order_list` - Unfulfilled orders

### Inventory Tables
- `item_list` - Product master
- `item_attributes` - Item properties
- `machine_list` - Machine/spare parts master
- `stock_list` - Current inventory levels
- `stock_movement` - Receiving/sending history (⚠️ missing from dumps but exists in code)
- `utilization_history` - Project usage tracking (⚠️ missing from dumps but exists in code)

### Project & Task Tables
- `project_planner` - Project header
- `project_items` - Project resource requirements
- `project_activities` - Project timeline
- `project_sheets` - Project planning sheets (⚠️ missing from dumps but exists in code)
- `tasks` - Task assignments
- `daily_tasks` - Daily task log

### User & System Tables
- `users` - System user accounts
- `users_c` - Client portal users
- `utility_suppliers` - Utility vendor master
- Plus system config & audit tables

---

## Important File Locations

| Path | Contains |
|------|----------|
| `admin/*/manage_*.php` | Add/Edit forms for each module |
| `admin/*/index.php` | List views with DataTables |
| `admin/*/view_*.php` | Detail/readonly views |
| `classes/Master.php` | All AJAX endpoint functions |
| `admin/inc/sess_auth.php` | Session check (include on every admin page) |
| `database/if0_37987606_sms_db.sql` | Latest database schema dump |
| `uploads/` | File upload destination (invoices, QR codes, etc.) |

## Diagrams & Visual References

See separate `.drawio` files (open in Draw.io or VS Code DrawIO extension):

1. **SB_Panchal_CMS_Architecture.drawio** - System overview, all active modules with Master.php hub
2. ~~**Workflow_1_Lead_to_Quote_to_PI_to_PO.drawio**~~ - **DEPRECATED** (Quotations & Quote Items not ready - refer to Proforma Invoice flow instead)
3. **Workflow_2_PO_to_Stock_to_Project.drawio** - Active: Inventory flow (PO → Stock → Project)
4. ~~**Workflow_3_Quote_Building.drawio**~~ - **DEPRECATED** (Quote Items module not ready)
5. **Workflow_4_Stock_Movement.drawio** - Active: Stock receiving vs utilization detailed flow

---

## Next Steps

1. **Read this guide** - Get system overview
2. **Open diagrams** - Visual understanding of data flows
3. **Explore `classes/Master.php`** - Understand the endpoint routing
4. **Trace one workflow end-to-end** - E.g., create a Lead → Quotation → see what changed in database
5. **Make a small test change** - Prove you can modify & deploy
6. **Set up security roadmap** - Plan MD5→bcrypt migration
7. **Document your environment** - Server details, credentials (secure!), backup procedures

---

**Last Updated:** March 11, 2026
