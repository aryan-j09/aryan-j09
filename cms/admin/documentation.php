<?php
/**
 * SB Panchal CMS - Unified Documentation Portal
 * Integrated technical and non-technical documentation with interactive diagrams
 * Requires: admin login (sess_auth.php)
 */

require_once '../admin/inc/sess_auth.php';
require_once '../classes/DBConnection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB Panchal CMS - Documentation Portal</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Draw.io Embed -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        
        .docs-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        /* Header Section */
        .docs-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .docs-header h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .docs-header p {
            font-size: 1.1em;
            opacity: 0.95;
        }
        
        /* Navigation Tabs */
        .nav-tabs {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            flex-wrap: wrap;
        }
        
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: #667eea;
            border-bottom-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .nav-tabs .nav-link.active {
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom-color: #667eea;
        }
        
        /* Content Areas */
        .tab-content {
            padding: 40px;
            min-height: 600px;
        }
        
        .section-header {
            font-size: 1.8em;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
            padding-left: 15px;
        }
        
        .section-subtitle {
            font-size: 1.2em;
            color: #718096;
            margin: 25px 0 15px 0;
            font-weight: 600;
        }
        
        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            line-height: 1.6;
        }
        
        .info-box strong {
            color: #667eea;
        }
        
        .warning-box {
            background: #fff5f5;
            border-left: 4px solid #f56565;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .success-box {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        /* Diagrams */
        .diagram-container {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .diagram-container iframe {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 4px;
        }
        
        .diagram-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        /* Module Cards */
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .module-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .module-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .module-card-title {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .module-card-path {
            font-size: 0.85em;
            color: #718096;
            font-family: 'Courier New', monospace;
            background: #f7fafc;
            padding: 8px;
            border-radius: 4px;
            margin: 10px 0;
            word-break: break-all;
        }
        
        .file-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        
        .file-list li {
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            font-size: 0.9em;
        }
        
        .file-list li:last-child {
            border-bottom: none;
        }
        
        .file-icon {
            color: #667eea;
            margin-right: 8px;
            width: 15px;
        }
        
        /* Workflow Steps */
        .workflow-step {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }
        
        .workflow-step-number {
            display: inline-block;
            background: #667eea;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: 700;
            margin-right: 12px;
        }
        
        .workflow-step-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.05em;
        }
        
        .workflow-step-desc {
            color: #718096;
            margin: 8px 0 0 44px;
            line-height: 1.5;
        }
        
        /* Tables */
        .docs-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .docs-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        .docs-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .docs-table tr:hover {
            background: #f8f9fa;
        }
        
        .docs-table code {
            background: #f7fafc;
            padding: 2px 6px;
            border-radius: 3px;
            color: #667eea;
            font-size: 0.9em;
        }
        
        /* Quick Links */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .quick-link {
            padding: 15px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .quick-link:hover {
            background: #f0f4ff;
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .docs-header h1 {
                font-size: 1.8em;
            }
            
            .tab-content {
                padding: 20px;
                min-height: auto;
            }
            
            .module-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-tabs {
                border-bottom: 1px solid #dee2e6;
            }
            
            .nav-tabs .nav-link {
                padding: 10px 15px;
                font-size: 0.9em;
            }
        }
        
        /* Code blocks */
        .code-block {
            background: #2d3748;
            color: #a0aec0;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            font-size: 0.85em;
            line-height: 1.5;
        }
        
        /* Anchor links */
        .anchor-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .anchor-link:hover {
            text-decoration: underline;
        }
        
        /* Footer */
        .docs-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #718096;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="docs-container">
        <!-- Header -->
        <div class="docs-header">
            <h1>
                <i class="fas fa-book-open"></i> SB Panchal CMS
            </h1>
            <p>Unified Documentation Portal - Technical & Business Overview</p>
        </div>
        
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="docsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-home"></i> Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="workflows-tab" data-bs-toggle="tab" data-bs-target="#workflows" type="button" role="tab">
                    <i class="fas fa-diagram-project"></i> Workflows & Diagrams
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="modules-tab" data-bs-toggle="tab" data-bs-target="#modules" type="button" role="tab">
                    <i class="fas fa-cubes"></i> Modules & Files
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="architecture-tab" data-bs-toggle="tab" data-bs-target="#architecture" type="button" role="tab">
                    <i class="fas fa-sitemap"></i> Architecture
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button" role="tab">
                    <i class="fas fa-briefcase"></i> Business Guide
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="database-tab" data-bs-toggle="tab" data-bs-target="#database" type="button" role="tab">
                    <i class="fas fa-database"></i> Database
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="training-tab" data-bs-toggle="tab" data-bs-target="#training" type="button" role="tab">
                    <i class="fas fa-graduation-cap"></i> Training
                </button>
            </li>
        </ul>
        
        <!-- Content Tabs -->
        <div class="tab-content" id="docsContent">
            
            <!-- TAB 1: OVERVIEW -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <h2 class="section-header">System Overview</h2>
                
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i> What is SB Panchal CMS?</strong><br>
                    A complete PHP/MySQL operations system managing the complete business lifecycle:
                    Lead capture → Client conversion → Commercial documents (Proforma Invoice) → 
                    Supplier procurement (Purchase Orders) → Stock management → Project execution → Task tracking
                </div>
                
                <h3 class="section-subtitle">Core Business Flow (Current Active)</h3>
                <div class="workflow-step">
                    <i class="fas fa-arrow-right"></i> 
                    <strong>Leads</strong> (CRM enquiry capture)
                    <i class="fas fa-arrow-right"></i> 
                    <strong>Clients</strong> (Lead converted to customer)
                    <i class="fas fa-arrow-right"></i> 
                    <strong>Proforma Invoice</strong> (Commercial confirmation = Order received)
                </div>
                <div class="workflow-step">
                    <i class="fas fa-arrow-right"></i>
                    <strong>Purchase Order → Stock In</strong> (Procurement can start immediately after PI)
                    <i class="fas fa-arrow-right"></i>
                    <strong>PO Factory Details</strong> (Parallel: shipment info, tracking, files)
                </div>
                <div class="workflow-step">
                    <i class="fas fa-arrow-right"></i>
                    <strong>Stock Out</strong> (To projects or utilization)
                    <i class="fas fa-arrow-right"></i>
                    <strong>Project Planner → Tasks</strong> (Execution tracking)
                </div>
                
                <h3 class="section-subtitle">Technology Stack</h3>
                <table class="docs-table">
                    <tr>
                        <th style="width: 200px;">Component</th>
                        <th>Technology</th>
                    </tr>
                    <tr>
                        <td><strong>Backend</strong></td>
                        <td>PHP 7+ (MySQLi, direct SQL, no ORM)</td>
                    </tr>
                    <tr>
                        <td><strong>Frontend</strong></td>
                        <td>Bootstrap 4, jQuery, DataTables</td>
                    </tr>
                    <tr>
                        <td><strong>UI Framework</strong></td>
                        <td>AdminLTE 3 Dashboard</td>
                    </tr>
                    <tr>
                        <td><strong>Database</strong></td>
                        <td>MySQL / MariaDB</td>
                    </tr>
                    <tr>
                        <td><strong>Architecture</strong></td>
                        <td>Page-centric + AJAX to Master.php controller</td>
                    </tr>
                </table>
                
                <h3 class="section-subtitle">How It Works: Request Pattern</h3>
                <div class="code-block">
User action on admin page (save, update, delete)
    ↓
jQuery AJAX POST to classes/Master.php?f=function_name
    ↓
Master.php executes function (runs SQL operations)
    ↓
JSON response returned to page
    ↓
DataTable / UI updated with feedback (success/error)
                </div>
                
                <h3 class="section-subtitle">Active vs Legacy</h3>
                <div class="success-box">
                    <strong><i class="fas fa-check-circle"></i> Currently Active:</strong><br>
                    Leads, Clients, Proforma Invoice, Purchase Order, PO Factory Details, Stock Movement, Project Planner, Tasks
                </div>
                <div class="warning-box">
                    <strong><i class="fas fa-exclamation-circle"></i> Legacy (Ignore in normal workflow):</strong><br>
                    Quotations, Quote Items, Back Order, Users(C) - These exist in code but are not part of the current operational flow.
                </div>
            </div>
            
            <!-- TAB 2: WORKFLOWS & DIAGRAMS -->
            <div class="tab-pane fade" id="workflows" role="tabpanel">
                <h2 class="section-header">Business Workflows & Visual Diagrams</h2>
                
                <p>Interactive diagrams showing the system's complete business flow. Click on any diagram to view full details.</p>
                
                <!-- Workflow 1 -->
                <h3 class="section-subtitle">
                    <i class="fas fa-diagram-project"></i> Workflow 1: Lead to PO Flow
                </h3>
                <p style="color: #718096; margin-bottom: 15px;">
                    Shows how a sales enquiry (Lead) is converted to a Client and results in a Proforma Invoice, 
                    which immediately triggers Purchase Order creation. PO Factory Details are captured in parallel.
                </p>
                <div class="diagram-container">
                    <p style="color: #718096;">
                        <i class="fas fa-image"></i><br>
                        View Workflow 1 in Draw.io:<br>
                        <a href="#" onclick="openDiagram('Workflow_1_Lead_to_Client_to_PI_to_PO.drawio'); return false;" class="anchor-link">
                            Open Workflow 1 Diagram
                        </a>
                    </p>
                </div>
                
                <!-- Workflow 2 -->
                <h3 class="section-subtitle">
                    <i class="fas fa-diagram-project"></i> Workflow 2: Procurement to Execution Flow
                </h3>
                <p style="color: #718096; margin-bottom: 15px;">
                    Shows how a Purchase Order flows through Stock receiving, stock utilization, 
                    and finally reaches the Project Planner and Task execution stages.
                </p>
                <div class="diagram-container">
                    <p style="color: #718096;">
                        <i class="fas fa-image"></i><br>
                        View Workflow 2 in Draw.io:<br>
                        <a href="#" onclick="openDiagram('Workflow_2_PO_to_Stock_to_Project.drawio'); return false;" class="anchor-link">
                            Open Workflow 2 Diagram
                        </a>
                    </p>
                </div>
                
                <!-- Workflow 3 -->
                <h3 class="section-subtitle">
                    <i class="fas fa-diagram-project"></i> Workflow 3: Stock Movement Details
                </h3>
                <p style="color: #718096; margin-bottom: 15px;">
                    Deep dive into Stock-In vs Stock-Out mechanics. Shows how received material 
                    enters inventory and how utilization works across projects.
                </p>
                <div class="diagram-container">
                    <p style="color: #718096;">
                        <i class="fas fa-image"></i><br>
                        View Workflow 3 in Draw.io:<br>
                        <a href="#" onclick="openDiagram('Workflow_3_Stock_Movement.drawio'); return false;" class="anchor-link">
                            Open Workflow 3 Diagram
                        </a>
                    </p>
                </div>
                
                <!-- Architecture Diagram -->
                <h3 class="section-subtitle">
                    <i class="fas fa-sitemap"></i> System Architecture
                </h3>
                <p style="color: #718096; margin-bottom: 15px;">
                    Module dependencies and data flow. Shows how all active modules interconnect 
                    and which Master.php endpoints power each operation.
                </p>
                <div class="diagram-container">
                    <p style="color: #718096;">
                        <i class="fas fa-image"></i><br>
                        View Architecture in Draw.io:<br>
                        <a href="#" onclick="openDiagram('SB_Panchal_CMS_Architecture.drawio'); return false;" class="anchor-link">
                            Open Architecture Diagram
                        </a>
                    </p>
                </div>
                
                <div class="info-box">
                    <strong><i class="fas fa-lightbulb"></i> How to use these diagrams:</strong>
                    <br>All diagrams are stored in the codebase as Draw.io files. 
                    Diagrams open in Draw.io's viewer where you can interact with them. 
                    To edit, download the file and use the <a href="https://marketplace.visualstudio.com/items?itemName=hediet.vscode-drawio" target="_blank" class="anchor-link">Draw.io extension in VS Code</a>.
                </div>
            </div>
            
            <!-- TAB 3: MODULES & FILES -->
            <div class="tab-pane fade" id="modules" role="tabpanel">
                <h2 class="section-header">Modules & File Structure</h2>
                
                <p style="color: #718096; margin-bottom: 30px;">
                    Each module follows a standard 3-file pattern: <code>index.php</code> (list view), 
                    <code>manage_*.php</code> (create/edit), and <code>view_*.php</code> (detail view).
                </p>
                
                <!-- Module Cards -->
                <div class="module-grid">
                    
                    <!-- Leads Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-user"></i> Leads (CRM)</div>
                        <div class="module-card-path">admin/leads/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>index.php</strong> - Lead list &amp; search</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_lead.php</strong> - Create/edit lead</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>view_lead.php</strong> - Lead details + activities</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoint: save_lead()</em>
                    </div>
                    
                    <!-- Clients Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-building"></i> Clients</div>
                        <div class="module-card-path">admin/clients/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>index.php</strong> - Client list</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_client.php</strong> - Create/edit client</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>view_client.php</strong> - Client details</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoint: save_client()</em>
                    </div>
                    
                    <!-- Proforma Invoice Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-receipt"></i> Proforma Invoice</div>
                        <div class="module-card-path">admin/proforma_invoice/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>index.php</strong> - PI list</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_pi.php</strong> - Create/edit PI</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>view_pi.php</strong> - PI detail view</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoint: save_pi()</em>
                    </div>
                    
                    <!-- Purchase Order Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-clipboard-list"></i> Purchase Order</div>
                        <div class="module-card-path">admin/purchase_order/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>index.php</strong> - PO list</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_po.php</strong> - Create/edit PO</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>view_po.php</strong> - PO details</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoint: save_po()</em>
                    </div>
                    
                    <!-- PO Factory Details Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-industry"></i> PO Factory Details</div>
                        <div class="module-card-path">admin/po_details/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>index.php</strong> - Details list</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_po_details.php</strong> - Create/edit</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>view_po_details.php</strong> - Details view</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoint: save_po_details()</em>
                    </div>
                    
                    <!-- Stock Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-boxes"></i> Stock Management</div>
                        <div class="module-card-path">admin/stock/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>index.php</strong> - Stock dashboard</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_receiving.php</strong> - Stock-in entry</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_utilization.php</strong> - Stock-out entry</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoints: save_stock_in(), save_utilization()</em>
                    </div>
                    
                    <!-- Project Planner Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-project-diagram"></i> Project Planner</div>
                        <div class="module-card-path">admin/project_planner2/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>index.php</strong> - Project list</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_project.php</strong> - Create/edit project</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>view_project.php</strong> - Project details</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoint: save_project()</em>
                    </div>
                    
                    <!-- Tasks Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-tasks"></i> Tasks</div>
                        <div class="module-card-path">admin/tasks/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>index.php</strong> - Task list</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_task.php</strong> - Create/edit task</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>view_task.php</strong> - Task details</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoint: save_task()</em>
                    </div>
                    
                    <!-- Items Module -->
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-list"></i> Items (Inventory)</div>
                        <div class="module-card-path">admin/maintenance/</div>
                        <ul class="file-list">
                            <li><i class="fas fa-file-code file-icon"></i><strong>item.php</strong> - Item list</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>manage_item.php</strong> - Create/edit item</li>
                            <li><i class="fas fa-file-code file-icon"></i><strong>view_item.php</strong> - Item details</li>
                        </ul>
                        <em style="color: #718096; font-size: 0.85em;">Endpoint: save_item()</em>
                    </div>
                </div>
                
                <h3 class="section-subtitle"><i class="fas fa-star"></i> Central AJAX Hub: Master.php</h3>
                <div class="info-box">
                    <strong>Location:</strong> <code>classes/Master.php</code><br>
                    <strong>Purpose:</strong> Main controller handling ALL save/update/delete operations across all modules<br>
                    <strong>Pattern:</strong> Accessed via <code>classes/Master.php?f=function_name</code><br>
                    <strong>Response:</strong> Returns JSON for most endpoints (AJAX consumption) or HTML fragments<br>
                    <strong>Database:</strong> Direct MySQLi queries - no ORM
                </div>
            </div>
            
            <!-- TAB 4: ARCHITECTURE -->
            <div class="tab-pane fade" id="architecture" role="tabpanel">
                <h2 class="section-header">System Architecture</h2>
                
                <h3 class="section-subtitle">Request Flow Architecture</h3>
                <div class="code-block">
                    User clicks button on admin page
                          ↓
                    jQuery event handler triggered
                          ↓
                    AJAX POST to classes/Master.php?f=function_name
                          ↓
                    Master.php receives request, executes function
                          ↓
                    Function reads POST data, validates, executes SQL
                          ↓
                    JSON response sent back (success, error, data)
                          ↓
                    Page handler receives response, updates DataTable/UI
                          ↓
                    Success/Error message displayed to user
                </div>
                
                <h3 class="section-subtitle">Core Classes & Files</h3>
                <table class="docs-table">
                    <tr>
                        <th style="width: 250px;">File</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><code>classes/Master.php</code></td>
                        <td>Main AJAX controller - all business save/update/delete endpoints</td>
                    </tr>
                    <tr>
                        <td><code>classes/DBConnection.php</code></td>
                        <td>Database connection & query execution</td>
                    </tr>
                    <tr>
                        <td><code>classes/Login.php</code></td>
                        <td>Authentication & login logic</td>
                    </tr>
                    <tr>
                        <td><code>classes/Users.php</code></td>
                        <td>User management & permissions</td>
                    </tr>
                    <tr>
                        <td><code>classes/SystemSettings.php</code></td>
                        <td>Global system configuration</td>
                    </tr>
                    <tr>
                        <td><code>classes/QRCodeGenerator.php</code></td>
                        <td>QR code generation utility</td>
                    </tr>
                    <tr>
                        <td><code>classes/SerialNumberGenerator.php</code></td>
                        <td>Serial &amp; unique number generation</td>
                    </tr>
                </table>
                
                <h3 class="section-subtitle">Session & Layout Helpers</h3>
                <table class="docs-table">
                    <tr>
                        <th style="width: 250px;">File</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><code>admin/inc/sess_auth.php</code></td>
                        <td>Login guard - checks session &amp; redirects to login if needed</td>
                    </tr>
                    <tr>
                        <td><code>admin/inc/navigation.php</code></td>
                        <td>Sidebar navigation - source of truth for visible modules</td>
                    </tr>
                    <tr>
                        <td><code>admin/inc/header.php</code></td>
                        <td>Shared page header (CSS, scripts, navbar)</td>
                    </tr>
                    <tr>
                        <td><code>admin/inc/footer.php</code></td>
                        <td>Shared footer &amp; global scripts</td>
                    </tr>
                </table>
                
                <h3 class="section-subtitle">Entry Points</h3>
                <table class="docs-table">
                    <tr>
                        <th style="width: 250px;">File</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><code>admin/index.php</code></td>
                        <td>Admin dashboard entry (protected)</td>
                    </tr>
                    <tr>
                        <td><code>admin/login.php</code></td>
                        <td>Login form</td>
                    </tr>
                    <tr>
                        <td><code>admin/home.php</code></td>
                        <td>Dashboard after login</td>
                    </tr>
                    <tr>
                        <td><code>index.php</code></td>
                        <td>Public/root entry point</td>
                    </tr>
                </table>
                
                <h3 class="section-subtitle">Key Architecture Decisions</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">1</span>
                    <div class="workflow-step-title">Page-Centric Design</div>
                    <div class="workflow-step-desc">
                        Each admin module has its own folder with listing, create/edit, and detail pages.
                        No API layer - pages directly call Master.php endpoints.
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">2</span>
                    <div class="workflow-step-title">Direct SQL (No ORM)</div>
                    <div class="workflow-step-desc">
                        MySQLi with raw SQL queries. Fast, simple, direct control over database operations.
                        No model layer - database queries are in Master.php and page handlers.
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">3</span>
                    <div class="workflow-step-title">AJAX for UI Updates</div>
                    <div class="workflow-step-desc">
                        jQuery handles all form submissions to Master.php as AJAX calls.
                        Responses are JSON - DataTables refresh automatically after save/delete.
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">4</span>
                    <div class="workflow-step-title">AdminLTE 3 UI Framework</div>
                    <div class="workflow-step-desc">
                        Consistent Bootstrap 4 styling. All pages use header.php and footer.php includes.
                        DataTables for any listing page - built-in search, sort, pagination.
                    </div>
                </div>
            </div>
            
            <!-- TAB 5: BUSINESS GUIDE -->
            <div class="tab-pane fade" id="business" role="tabpanel">
                <h2 class="section-header">Business Operations Guide</h2>
                
                <h3 class="section-subtitle"><i class="fas fa-handshake"></i> Sales & Client Management</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">1</span>
                    <div class="workflow-step-title">Lead Capture & Tracking</div>
                    <div class="workflow-step-desc">
                        New enquiry enters the <strong>Leads</strong> module with company name, contact, requirements.
                        Daily follow-up activities are logged with dates &amp; notes.
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">2</span>
                    <div class="workflow-step-title">Client Conversion</div>
                    <div class="workflow-step-desc">
                        Once the lead decides to purchase, it is converted to a <strong>Client</strong> record 
                        with billing &amp; shipping addresses, credit terms, etc.
                    </div>
                </div>
                
                <h3 class="section-subtitle"><i class="fas fa-money-bill"></i> Commercial Documents</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">3</span>
                    <div class="workflow-step-title">Proforma Invoice Creation</div>
                    <div class="workflow-step-desc">
                        <strong>Critical:** In this system, a Proforma Invoice (PI) = Confirmed Order.
                        Once PI is created and saved, it signals that you have a purchase commitment from the client.
                    </div>
                </div>
                
                <div class="info-box">
                    <strong><i class="fas fa-exclamation-triangle"></i> Business Rule:</strong><br>
                    A Proforma Invoice indicates that the ORDER is confirmed. The system treats it as permission 
                    to start procurement immediately. You don't need to wait for PO Factory Details to be filled 
                    before starting a Purchase Order to suppliers.
                </div>
                
                <h3 class="section-subtitle"><i class="fas fa-truck"></i> Procurement & Supplier Orders</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">4</span>
                    <div class="workflow-step-title">Purchase Order to Suppliers</div>
                    <div class="workflow-step-desc">
                        As soon as Proforma Invoice is confirmed, Procurement can create a <strong>Purchase Order (PO)</strong> 
                        with the supplier. This can happen immediately or in parallel with factory detail entry.
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">5</span>
                    <div class="workflow-step-title">Factory/Shipment Details (Parallel)</div>
                    <div class="workflow-step-desc">
                        <strong>PO Factory Details</strong> capture shipment references, eWayBills, tracking numbers, 
                        and delivery schedules. This runs in parallel with the Purchase Order and can be filled before or after.
                    </div>
                </div>
                
                <h3 class="section-subtitle"><i class="fas fa-warehouse"></i> Stock & Inventory</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">6</span>
                    <div class="workflow-step-title">Stock Receiving (Stock-In)</div>
                    <div class="workflow-step-desc">
                        As material arrives from the supplier, it is logged in <strong>Stock-In</strong> 
                        against the Purchase Order. Quantities verified, expiry dates recorded if applicable.
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">7</span>
                    <div class="workflow-step-title">Stock Utilization (Stock-Out)</div>
                    <div class="workflow-step-desc">
                        Material moves from inventory to project sites or is used for client delivery. 
                        Tracked as <strong>Stock-Out</strong> with project assignment &amp; quantity deduction.
                    </div>
                </div>
                
                <h3 class="section-subtitle"><i class="fas fa-clipboard-check"></i> Project & Task Execution</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">8</span>
                    <div class="workflow-step-title">Project Planning</div>
                    <div class="workflow-step-desc">
                        <strong>Project Planner</strong> organizes the client's project with phases, milestones, 
                        and allocated materials from stock.
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">9</span>
                    <div class="workflow-step-title">Task Assignment & Tracking</div>
                    <div class="workflow-step-desc">
                        Daily <strong>Tasks</strong> are assigned to team members with deadlines &amp; priorities. 
                        Task status is tracked from creation through completion.
                    </div>
                </div>
                
                <h3 class="section-subtitle">Master Data Management</h3>
                <div class="module-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-shopping-basket"></i> Items/Products</div>
                        <p style="font-size: 0.9em; color: #718096;">
                            Maintain the master list of products/materials your company deals with.
                            Includes dimensions, specifications, and unit of measurement.
                        </p>
                    </div>
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-industry"></i> Suppliers</div>
                        <p style="font-size: 0.9em; color: #718096;">
                            Vendor/supplier contact list with payment terms, GST numbers, 
                            and historical performance data.
                        </p>
                    </div>
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-cog"></i> Machine Items</div>
                        <p style="font-size: 0.9em; color: #718096;">
                            Equipment and machinery catalog with serial numbers, 
                            maintenance history, and allocation tracking.
                        </p>
                    </div>
                    <div class="module-card">
                        <div class="module-card-title"><i class="fas fa-tools"></i> Utility Suppliers</div>
                        <p style="font-size: 0.9em; color: #718096;">
                            Services &amp; utility vendors (electricity, water, logistics, etc.) 
                            for project cost tracking.
                        </p>
                    </div>
                </div>
                
                <h3 class="section-subtitle">Key Business Rules to Remember</h3>
                <div class="success-box">
                    <strong><i class="fas fa-check"></i> PI = Confirmed Order</strong><br>
                    Once a Proforma Invoice is saved, treat it as a confirmed purchase order from the client.
                    You can immediately create supplier POs.
                </div>
                
                <div class="success-box">
                    <strong><i class="fas fa-check"></i> PO ↔ Factory Details are Parallel</strong><br>
                    Purchase Order and Factory Details don't have a strict sequence. You can fill either one first
                    or even afterward. They're complementary data for the same procurement.
                </div>
                
                <div class="success-box">
                    <strong><i class="fas fa-check"></i> Stock Tracking is Mandatory</strong><br>
                    Every item received must be logged as Stock-In. Every item used must be logged as Stock-Out.
                    This keeps inventory accurate and traceable.
                </div>
            </div>
            
            <!-- TAB 6: DATABASE -->
            <div class="tab-pane fade" id="database" role="tabpanel">
                <h2 class="section-header">Database Schema & Tables</h2>
                
                <h3 class="section-subtitle">Active Business Tables</h3>
                
                <h4 style="color: #667eea; margin-top: 20px; margin-bottom: 10px;">CRM & Client Management</h4>
                <table class="docs-table">
                    <tr>
                        <th>Table Name</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><code>leads</code></td>
                        <td>Lead/enquiry records with company info, contact details</td>
                    </tr>
                    <tr>
                        <td><code>lead_activities</code></td>
                        <td>Follow-up history, notes, and activity status per lead</td>
                    </tr>
                    <tr>
                        <td><code>clients</code></td>
                        <td>Customer records (converted leads) with billing/shipping addresses</td>
                    </tr>
                </table>
                
                <h4 style="color: #667eea; margin-top: 20px; margin-bottom: 10px;">Commercial Documents</h4>
                <table class="docs-table">
                    <tr>
                        <th>Table Name</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><code>proforma_invoice_list</code></td>
                        <td>Header data for Proforma Invoices (PI number, date, client, total)</td>
                    </tr>
                    <tr>
                        <td><code>proforma_invoice_items</code></td>
                        <td>Line items in each PI (product, qty, rate, amount)</td>
                    </tr>
                </table>
                
                <h4 style="color: #667eea; margin-top: 20px; margin-bottom: 10px;">Procurement</h4>
                <table class="docs-table">
                    <tr>
                        <th>Table Name</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><code>purchase_order_list</code></td>
                        <td>PO headers (PO number, supplier, date, total amount)</td>
                    </tr>
                    <tr>
                        <td><code>po_items</code></td>
                        <td>Line items in each PO (item, quantity, rate)</td>
                    </tr>
                    <tr>
                        <td><code>supplier_list</code></td>
                        <td>Vendor master data (contact, GST, payment terms)</td>
                    </tr>
                </table>
                
                <h4 style="color: #667eea; margin-top: 20px; margin-bottom: 10px;">Inventory & Stock</h4>
                <table class="docs-table">
                    <tr>
                        <th>Table Name</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><code>item_list</code></td>
                        <td>Product/item master (item code, name, unit, specifications)</td>
                    </tr>
                    <tr>
                        <td><code>item_attributes</code></td>
                        <td>Detailed specs &amp; attributes for each item</td>
                    </tr>
                    <tr>
                        <td><code>stock_list</code></td>
                        <td>Current stock levels by item (warehouse inventory)</td>
                    </tr>
                    <tr>
                        <td><code>stock_movement</code></td>
                        <td>History of all stock-in &amp; stock-out transactions</td>
                    </tr>
                    <tr>
                        <td><code>machine_list</code></td>
                        <td>Equipment/machinery with serial numbers &amp; maintenance history</td>
                    </tr>
                </table>
                
                <h4 style="color: #667eea; margin-top: 20px; margin-bottom: 10px;">Projects & Tasks</h4>
                <table class="docs-table">
                    <tr>
                        <th>Table Name</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><code>project_planner</code></td>
                        <td>Project header (project name, client, start/end dates)</td>
                    </tr>
                    <tr>
                        <td><code>project_items</code></td>
                        <td>Items/stock allocated to a project</td>
                    </tr>
                    <tr>
                        <td><code>project_activities</code></td>
                        <td>Project milestone history &amp; updates</td>
                    </tr>
                    <tr>
                        <td><code>tasks</code></td>
                        <td>Task records (title, assignee, deadline, priority)</td>
                    </tr>
                </table>
                
                <h3 class="section-subtitle">Schema & Setup</h3>
                <div class="info-box">
                    <strong><i class="fas fa-database"></i> Main Database Dump:</strong><br>
                    Location: <code>database/if0_37987606_sms_db.sql</code><br>
                    This is the authoritative schema export. Use it to restore or set up a fresh database.
                </div>
                
                <h3 class="section-subtitle">Legacy Tables (Inactive)</h3>
                <p style="color: #718096; margin-bottom: 15px;">
                    These tables still exist in the database but are not part of the current operational workflow.
                    Do not use them for new features unless explicitly requested.
                </p>
                <ul style="color: #718096; margin-left: 20px;">
                    <li><code>quotations</code> - Quote documents (superseded by PI)</li>
                    <li><code>quotation_items</code> - Quote line items</li>
                    <li><code>back_order_list</code> - Back order tracking (not active)</li>
                    <li><code>users_c</code> - Legacy user table (use <code>classes/Users.php</code> instead)</li>
                </ul>
            </div>
            
            <!-- TAB 7: TRAINING -->
            <div class="tab-pane fade" id="training" role="tabpanel">
                <h2 class="section-header">Training & Onboarding Guide</h2>
                
                <div class="info-box">
                    <strong><i class="fas fa-graduation-cap"></i> Purpose:</strong><br>
                    This guide is designed for new team members joining the company. It provides a structured 
                    3-week onboarding path covering system basics, hands-on practice, and final sign-off.
                </div>
                
                <h3 class="section-subtitle">Suggested Timeline</h3>
                <p style="color: #718096; margin-bottom: 20px;">
                    <strong>Week 1:</strong> Understanding the system &amp; business flow<br>
                    <strong>Week 2:</strong> Hands-on practice with real (or test) data<br>
                    <strong>Week 3:</strong> Role-specific training &amp; final sign-off
                </p>
                
                <h3 class="section-subtitle">Phase 1: Orientation (Day 1)</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">System Introduction</div>
                    <div class="workflow-step-desc">Read this documentation portal • Overview the business flow diagram • Understand active vs legacy modules</div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">Admin Login & Navigation</div>
                    <div class="workflow-step-desc">Get credentials • Login to admin dashboard • Explore navigation/sidebar • Understand module layout</div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">Core Concept Walkthrough</div>
                    <div class="workflow-step-desc">
                        Walk through a sample: Lead → Client → Proforma Invoice → PO flow
                        Understand that PI = Confirmed Order (key concept!)
                    </div>
                </div>
                
                <h3 class="section-subtitle">Phase 2: Guided Walkthrough (Days 2-3)</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">CRM & Client Module</div>
                    <div class="workflow-step-desc">
                        <strong>Create:</strong> A test lead with fake company name &amp; contact<br>
                        <strong>Explore:</strong> Lead detail page, activity log area<br>
                        <strong>Convert:</strong> The lead to a client<br>
                        <strong>Update:</strong> Client billing address
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">Commercial Document Creation</div>
                    <div class="workflow-step-desc">
                        <strong>Create:</strong> A Proforma Invoice for the test client<br>
                        <strong>Add:</strong> 2-3 sample items (use Item Master if needed)<br>
                        <strong>Save:</strong> &amp; observe that PI is now in the list<br>
                        <strong>Understand:</strong> This PI = order confirmed
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">Procurement Flow</div>
                    <div class="workflow-step-desc">
                        <strong>Create:</strong> A Purchase Order against a supplier for same items<br>
                        <strong>Add:</strong> PO Factory Details (shipment reference, eWayBill if applicable)<br>
                        <strong>Observe:</strong> Both PO &amp; Factory Details can exist independently
                    </div>
                </div>
                
                <h3 class="section-subtitle">Phase 3: Hands-on Practice (Days 4-5)</h3>
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">Stock Movement</div>
                    <div class="workflow-step-desc">
                        <strong>Stock-In:</strong> Receive the purchased items against the PO (Qty = 10 units)<br>
                        <strong>Check:</strong> Stock list shows items in inventory<br>
                        <strong>Stock-Out:</strong> Allocate some items to a project (Qty = 5 units)<br>
                        <strong>Verify:</strong> Remaining stock = 5 units
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">Project Planning</div>
                    <div class="workflow-step-desc">
                        <strong>Create:</strong> A new project for the test client<br>
                        <strong>Add:</strong> Allocated items from stock<br>
                        <strong>Set:</strong> Start &amp; expected completion dates
                    </div>
                </div>
                
                <div class="workflow-step">
                    <span class="workflow-step-number">✓</span>
                    <div class="workflow-step-title">Task Assignment</div>
                    <div class="workflow-step-desc">
                        <strong>Create:</strong> 2-3 tasks for the project<br>
                        <strong>Assign:</strong> To yourself with priority &amp; deadline<br>
                        <strong>Update:</strong> Task status from Open → In Progress → Completed
                    </div>
                </div>
                
                <h3 class="section-subtitle">Phase 4: Role-Based Deep Dive</h3>
                
                <div class="info-box" style="margin-top: 20px;">
                    <strong>Select your role &amp; focus on the relevant section below:</strong>
                </div>
                
                <div class="module-card" style="margin-top: 15px;">
                    <div class="module-card-title"><i class="fas fa-phone"></i> Sales / CRM Role</div>
                    <ul style="color: #718096; margin: 15px 0;">
                        <li><strong>Key modules:</strong> Leads, Clients, Proforma Invoice</li>
                        <li><strong>Daily tasks:</strong>
                            <ul style="margin-left: 20px;">
                                <li>Capture new leads with company/contact details</li>
                                <li>Follow-up activities &amp; status updates</li>
                                <li>Convert qualified leads to clients</li>
                                <li>Create Proforma Invoices to confirm orders</li>
                            </ul>
                        </li>
                        <li><strong>Key rule:</strong> PI confirmation = you signal that PO can start</li>
                        <li><strong>Success metric:</strong> Lead-to-PI conversion rate, sales pipeline visibility</li>
                    </ul>
                </div>
                
                <div class="module-card">
                    <div class="module-card-title"><i class="fas fa-file-invoice"></i> Commercial / Ops Role</div>
                    <ul style="color: #718096; margin: 15px 0;">
                        <li><strong>Key modules:</strong> Proforma Invoice, Purchase Order, PO Factory Details</li>
                        <li><strong>Daily tasks:</strong>
                            <ul style="margin-left: 20px;">
                                <li>Review confirmed PIs</li>
                                <li>Create Purchase Orders to suppliers immediately after PI</li>
                                <li>Add factory details (eWay, shipping refs, etc.)</li>
                                <li>Track supplier response &amp; delivery status</li>
                            </ul>
                        </li>
                        <li><strong>Key rule:</strong> Don't wait for Factory Details to start PO</li>
                        <li><strong>Success metric:</strong> PO creation speed, on-time delivery</li>
                    </ul>
                </div>
                
                <div class="module-card">
                    <div class="module-card-title"><i class="fas fa-truck"></i> Procurement / Supply Chain Role</div>
                    <ul style="color: #718096; margin: 15px 0;">
                        <li><strong>Key modules:</strong> Purchase Order, Supplier List, Stock-In</li>
                        <li><strong>Daily tasks:</strong>
                            <ul style="margin-left: 20px;">
                                <li>Review active POs</li>
                                <li>Follow up with suppliers on delivery</li>
                                <li>Receive delivered items &amp; log Stock-In</li>
                                <li>Verify quantities &amp; quality</li>
                            </ul>
                        </li>
                        <li><strong>Key rule:</strong> Every received item must be logged in Stock-In</li>
                        <li><strong>Success metric:</strong> Stock accuracy, on-time receipts</li>
                    </ul>
                </div>
                
                <div class="module-card">
                    <div class="module-card-title"><i class="fas fa-warehouse"></i> Stores / Inventory Role</div>
                    <ul style="color: #718096; margin: 15px 0;">
                        <li><strong>Key modules:</strong> Stock-In, Stock-Out, Item List, Machine Items</li>
                        <li><strong>Daily tasks:</strong>
                            <ul style="margin-left: 20px;">
                                <li>Receive &amp; verify stock-in entries</li>
                                <li>Maintain stock bins &amp; organization</li>
                                <li>Process stock-out requests for projects</li>
                                <li>Monitor inventory levels &amp; reorder alerts</li>
                            </ul>
                        </li>
                        <li><strong>Key rule:</strong> Stock-in &amp; Stock-out must balance. Every item traced.</li>
                        <li><strong>Success metric:</strong> Stock accuracy, fast turnaround on allocations</li>
                    </ul>
                </div>
                
                <div class="module-card">
                    <div class="module-card-title"><i class="fas fa-chart-gantt"></i> Project / Execution Role</div>
                    <ul style="color: #718096; margin: 15px 0;">
                        <li><strong>Key modules:</strong> Project Planner, Tasks, Stock allocation</li>
                        <li><strong>Daily tasks:</strong>
                            <ul style="margin-left: 20px;">
                                <li>Set up project milestones from confirmed PIs</li>
                                <li>Allocate stock to project phases</li>
                                <li>Assign &amp; track daily tasks</li>
                                <li>Monitor timeline &amp; resource utilization</li>
                            </ul>
                        </li>
                        <li><strong>Key rule:</strong> Project planned → Tasks assigned → Stock allocated</li>
                        <li><strong>Success metric:</strong> On-time project delivery, resource efficiency</li>
                    </ul>
                </div>
                
                <h3 class="section-subtitle">Phase 5: Readiness Checklist (Sign-off)</h3>
                <p style="color: #718096; margin-bottom: 15px;">
                    By end of Week 3, you should be able to confidently answer:
                </p>
                
                <div class="success-box">
                    <strong><i class="fas fa-check"></i> Functional Knowledge</strong><br>
                    □ I can create a lead &amp; track follow-ups<br>
                    □ I know when to convert lead to client<br>
                    □ I understand that PI = order confirmed<br>
                    □ I can create &amp; manage purchase orders<br>
                    □ I can receive stock &amp; log stock-in<br>
                    □ I can allocate stock to projects<br>
                    □ I can create &amp; track tasks
                </div>
                
                <div class="success-box">
                    <strong><i class="fas fa-check"></i> Business Logic</strong><br>
                    □ I know the lead-to-delivery flow<br>
                    □ I understand PO Factory Details are parallel to PO, not sequential<br>
                    □ I know stock-in/out must be balanced<br>
                    □ I understand role boundaries (who owns what)<br>
                    □ I know which legacy modules to ignore
                </div>
                
                <div class="success-box">
                    <strong><i class="fas fa-check"></i> Support Resources</strong><br>
                    □ I can access this documentation portal<br>
                    □ I know where to find diagrams (Draw.io files)<br>
                    □ I have contact for questions during first month<br>
                    □ I understand the Master.php endpoint pattern for troubleshooting
                </div>
                
                <h3 class="section-subtitle">Common Mistakes to Avoid</h3>
                
                <div class="warning-box">
                    <strong><i class="fas fa-times-circle"></i> ❌ Don't wait for Factory Details before creating PO</strong><br>
                    Factory Details &amp; Purchase Order are parallel. Create PO as soon as PI is confirmed.
                </div>
                
                <div class="warning-box">
                    <strong><i class="fas fa-times-circle"></i> ❌ Don't forget to log Stock-In</strong><br>
                    Every received item must be logged in Stock-In. Skipping this breaks inventory accuracy.
                </div>
                
                <div class="warning-box">
                    <strong><i class="fas fa-times-circle"></i> ❌ Don't use legacy modules</strong><br>
                    Quotations, Quote Items, Back Order are not part of current flow. Ignore unless explicitly told.
                </div>
                
                <div class="warning-box">
                    <strong><i class="fas fa-times-circle"></i> ❌ Don't create tasks without assigning them</strong><br>
                    Tasks without assignee = lost accountability. Always assign to a person &amp; set deadline.
                </div>
                
                <div class="warning-box">
                    <strong><i class="fas fa-times-circle"></i> ❌ Don't allocate more stock than you have</strong><br>
                    The system won't prevent over-allocation. Track manually - Stock-out should never exceed Stock-in.
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="docs-footer">
            <p>
                <strong>SB Panchal CMS Documentation Portal</strong><br>
                Last Updated: March 18, 2026<br>
                <small>For technical details, see: <code>classes/Master.php</code> | 
                Database: <code>database/if0_37987606_sms_db.sql</code></small>
            </p>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Build an absolute URL to a local proxy endpoint that serves diagram XML with CORS.
        function openDiagram(filename) {
            const proxyUrl = new URL('drawio_file.php?file=' + encodeURIComponent(filename), window.location.href).href;
            const drawioUrl = 'https://app.diagrams.net/?lightbox=1&url=' + encodeURIComponent(proxyUrl);
            window.open(drawioUrl, '_blank');
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
