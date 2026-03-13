# SB Panchal CMS - Handover & Developer Guide

## Quick Summary

**SB Panchal CMS** is a PHP/MySQL operations system for CRM, commercial documents, procurement, stock movement, and project execution.

**Current active business flow:**
CRM / Leads → Client conversion → Proforma Invoice (order confirmed) → Supplier Purchase Order (can start immediately) + PO Factory Details (parallel/optional) → Stock In/Out → Project Planner → Tasks

**Tech Stack:**
- Backend: PHP 7+ with MySQLi (direct SQL, no ORM)
- Frontend: AdminLTE 3, Bootstrap 4, jQuery
- Database: MySQL / MariaDB
- Application pattern: page-centric PHP screens posting to `classes/Master.php?f=function_name`

**Core request pattern:**
```
User action on admin page
→ jQuery AJAX POST to classes/Master.php?f=...
→ Master.php function runs SQL
→ JSON response returned
→ page updates DataTable / UI feedback
```

## What Is Active vs Legacy

### Active modules surfaced in the current sidebar

**CRM / Commercial**
- Leads (`admin/leads/`)
- Clients (`admin/clients/`)
- Proforma Invoice (`admin/proforma_invoice/`)

**Procurement / Stock**
- PO Factory Details (`admin/po_details/`)
- Purchase Order (`admin/purchase_order/`)
- Stock In/Out (`admin/stock/`)
- Supplier List (`admin/maintenance/supplier.php`)

**Inventory / Delivery**
- Item List (`admin/maintenance/item.php`)
- Machine Items (`admin/machine_items/`)

**Execution / Admin**
- Project Planner (`admin/project_planner2/`)
- Tasks (`admin/tasks/`)
- Utility Suppliers (`admin/utility/`)
- User List (`admin/user/`)
- Settings (`admin/system_info/`)

### Legacy code paths present in repo but not part of the current handover flow

- Quotations (`admin/quotations/`) exists in code, but the sidebar link is commented out and the lead screen quotation button is commented out.
- Quote Items (`admin/quote_items/`) still exists in code, but it is not part of the active commercial workflow being used.
- Back Order (`admin/back_order/`) exists as pages and queries, but it is not surfaced in the current sidebar/navigation.
- Users(C) exists historically in the repo, but it is not part of the current active module map.

## Current Architecture

### Central Hub

**`classes/Master.php`**
- Main AJAX controller
- Contains save / update / delete handlers for most modules
- Directly talks to MySQL
- Returns JSON or HTML fragments depending on the endpoint

### Important screens that define the current flow

| File | Why it matters |
|------|----------------|
| `admin/inc/navigation.php` | Best source of truth for what is currently exposed in the sidebar |
| `admin/leads/view_lead.php` | Shows active CRM actions; quotation button is commented out; client conversion is active |
| `classes/Master.php` | `save_client`, `save_pi`, `save_po_details`, `save_po`, stock and task endpoints |
| `admin/proforma_invoice/manage_pi.php` | Active commercial document creation/edit screen |
| `admin/po_details/manage_po_details.php` | Factory PO details and file attachments |
| `admin/purchase_order/` | Supplier-facing purchase order flow |
| `admin/stock/` | Receiving and utilization flows |
| `admin/project_planner2/` | Project allocation / planning |
| `admin/tasks/` | Task assignment and tracking |

### Real commercial flow in code today

1. Lead is created and followed up in CRM.
2. Lead is converted to client using `save_client()`.
3. Proforma Invoice is created for the client using `save_pi()` and is treated as confirmed order intent.
4. Procurement can start immediately by creating Supplier PO using `save_po()`.
5. PO Factory Details (`save_po_details()`) are captured in parallel or afterward as operational/shipment detail.
6. Received material moves into stock, then out to projects and tasks.

## Key Classes & Files

### Core classes

| File | Purpose |
|------|---------|
| `classes/Master.php` | Main controller for AJAX endpoints |
| `classes/DBConnection.php` | Database connection |
| `classes/Login.php` | Authentication logic |
| `classes/Users.php` | User management |
| `classes/SystemSettings.php` | Global settings |
| `classes/QRCodeGenerator.php` | QR generation |
| `classes/SerialNumberGenerator.php` | Serial / number generation |

### Session / layout helpers

| File | Purpose |
|------|---------|
| `admin/inc/sess_auth.php` | Login/session guard |
| `admin/inc/header.php` | Shared page header |
| `admin/inc/footer.php` | Shared footer and scripts |
| `admin/inc/navigation.php` | Current sidebar/module exposure |

### Entry points

| File | Purpose |
|------|---------|
| `admin/index.php` | Admin entry |
| `admin/login.php` | Login page |
| `admin/home.php` | Dashboard after login |
| `index.php` | Public/root entry |

## Database Overview

### Active business tables

**CRM / Client**
- `leads`
- `lead_activities`
- `clients`

**Commercial**
- `proforma_invoice_list`
- `proforma_invoice_items`

**Procurement**
- `purchase_order_list`
- `po_items`
- PO detail data used by the factory details flow
- `supplier_list`

**Inventory**
- `item_list`
- `item_attributes`
- `machine_list`
- `stock_list`
- `stock_movement`
- `utilization_history`

**Projects / Tasks**
- `project_planner`
- `project_items`
- `project_activities`
- `project_sheets`
- `tasks`
- `daily_tasks`

### Legacy / non-active-flow tables still present

- `quotations`
- `quotation_items`
- quote-item related pricing / accessory tables
- `back_order_list`
- `users_c`

These still exist in code or schema history, but they should not be treated as part of the current operational workflow unless the business explicitly revives them.

## Important File Locations

| Path | Contains |
|------|----------|
| `admin/*/manage_*.php` | Add/edit forms |
| `admin/*/index.php` | Listing pages |
| `admin/*/view_*.php` | Detail pages |
| `classes/Master.php` | Core AJAX handlers |
| `admin/inc/navigation.php` | Current module exposure |
| `database/if0_37987606_sms_db.sql` | Main schema dump |
| `uploads/` | Uploaded business files |

## Diagrams & Visual References

Open these in Draw.io or a Draw.io VS Code extension:

1. **SB_Panchal_CMS_Architecture.drawio** - Current active module architecture only
2. **Workflow_1_Lead_to_Client_to_PI_to_PO.drawio** - Current commercial flow
3. **Workflow_2_PO_to_Stock_to_Project.drawio** - Procurement to stock to project flow
4. **Workflow_3_Stock_Movement.drawio** - Detailed stock in vs stock out flow

Removed from active documentation:

- Quote-only workflow diagrams

## What To Tell the Replacement

Tell them this system should currently be understood in two layers:

1. **Active operational layer**
	Leads, Clients, Proforma Invoice, Purchase Order, PO Factory Details, Stock, Projects, Tasks (with PO creation allowed immediately after PI confirmation).

2. **Legacy code layer**
	Quotations, Quote Items, Back Order, and Users(C) still exist in the repo, but they are not the current live workflow and should be ignored during normal maintenance unless specifically requested.

## Recommended First Read

1. Read this guide.
2. Open the architecture diagram.
3. Read `admin/inc/navigation.php` to see what is actually exposed.
4. Trace this path in code: `admin/leads/view_lead.php` → `classes/Master.php::save_client()` → `admin/proforma_invoice/manage_pi.php` → `classes/Master.php::save_pi()`.
5. Then trace procurement and stock: PO Details → Purchase Order → Stock → Project Planner → Tasks.
5. Then trace procurement and stock: Proforma Invoice → Purchase Order (immediate) with PO Details in parallel → Stock → Project Planner → Tasks.

## Last Updated

March 12, 2026
