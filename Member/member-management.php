<?php
/**
 * Member Management - Librarian Interface
 * =========================================
 * PRESENTATION LAYER (Front-End / View)
 * 
 * This page provides the librarian with a full dashboard to:
 *  - List all members with sorting options
 *  - Search members by name or email
 *  - Filter members by status (blocked / active) and membership date
 *  - Add a new member (modal form)
 *  - Edit an existing member (modal form pre-filled via AJAX)
 *  - Block / Unblock a member
 *  - Delete a member (soft-delete flow)
 *  - View member borrowing statistics
 *
 * Connects to: ../api/member-api.php  (BUSINESS LOGIC + DATA LAYER)
 */

// ── Layer 1: Data / Session Setup ───────────────────────────────────────────
require_once '../config.php';   // Database connection + session start

// Access control – only librarians may manage members
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Management | LibTech Solutions</title>

    <!-- Google Fonts & Font Awesome icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* ── Global Reset & Base ─────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f1419;          /* dark background */
            color: #e2e8f0;
            min-height: 100vh;
        }

        /* ── Sidebar Navigation ──────────────────────────────────────── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: 260px; height: 100vh;
            background: #1c1e26;
            border-right: 1px solid rgba(255,255,255,0.08);
            display: flex; flex-direction: column;
            z-index: 100;
        }
        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-size: 20px; font-weight: 800;
            background: linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .sidebar-menu { flex: 1; padding: 20px 0; }
        .menu-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 20px; color: rgba(255,255,255,0.6);
            text-decoration: none; transition: all 0.3s; font-size: 14px;
        }
        .menu-item:hover, .menu-item.active {
            background: rgba(99,102,241,0.15);
            color: #818cf8; border-left: 3px solid #818cf8;
        }
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .logout-btn {
            display: flex; align-items: center; gap: 10px;
            color: #f87171; text-decoration: none;
            font-size: 14px; padding: 10px;
            border-radius: 10px; transition: all 0.3s;
        }
        .logout-btn:hover { background: rgba(239,68,68,0.15); }

        /* ── Main Content Area ───────────────────────────────────────── */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }

        /* ── Page Header ─────────────────────────────────────────────── */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px;
        }
        .page-title { font-size: 26px; font-weight: 700; }
        .page-title span {
            background: linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .btn-add {
            background: linear-gradient(135deg,#6366f1,#ec4899);
            color: #fff; border: none; padding: 12px 24px;
            border-radius: 30px; cursor: pointer; font-weight: 600;
            display: flex; align-items: center; gap: 8px;
            transition: all 0.3s;
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99,102,241,0.4); }

        /* ── Stats Row ───────────────────────────────────────────────── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .stat-card {
            background: #1c1e26;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px; padding: 20px; text-align: center;
        }
        .stat-number {
            font-size: 32px; font-weight: 800;
            background: linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .stat-label { font-size: 13px; color: rgba(255,255,255,0.5); margin-top: 6px; }

        /* ── Search / Filter Bar ─────────────────────────────────────── */
        .filter-bar {
            background: #1c1e26;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px; padding: 20px;
            display: flex; flex-wrap: wrap; gap: 15px;
            align-items: center; margin-bottom: 25px;
        }
        .filter-bar input, .filter-bar select {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px; padding: 10px 16px;
            color: #e2e8f0; font-size: 14px;
            outline: none; transition: border 0.3s;
        }
        .filter-bar input:focus, .filter-bar select:focus {
            border-color: #6366f1;
        }
        .filter-bar input { flex: 1; min-width: 200px; }
        .btn-search {
            background: #6366f1; color: #fff; border: none;
            padding: 10px 20px; border-radius: 10px;
            cursor: pointer; font-weight: 600; transition: all 0.3s;
        }
        .btn-search:hover { background: #4f46e5; }
        .btn-reset {
            background: rgba(255,255,255,0.08); color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.12);
            padding: 10px 20px; border-radius: 10px;
            cursor: pointer; font-weight: 600; transition: all 0.3s;
        }
        .btn-reset:hover { background: rgba(255,255,255,0.15); }

        /* ── Members Table ───────────────────────────────────────────── */
        .table-container {
            background: #1c1e26;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; overflow: hidden;
        }
        .table-header {
            padding: 18px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; justify-content: space-between; align-items: center;
        }
        .table-title { font-size: 16px; font-weight: 600; }
        table {
            width: 100%; border-collapse: collapse;
        }
        thead th {
            padding: 14px 18px; text-align: left;
            background: rgba(255,255,255,0.04);
            font-size: 12px; font-weight: 600;
            color: rgba(255,255,255,0.5); text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        /* Sortable column header */
        thead th.sortable { cursor: pointer; }
        thead th.sortable:hover { color: #818cf8; }
        tbody tr {
            border-top: 1px solid rgba(255,255,255,0.05);
            transition: background 0.2s;
        }
        tbody tr:hover { background: rgba(255,255,255,0.03); }
        tbody td { padding: 14px 18px; font-size: 14px; vertical-align: middle; }

        /* ── Status Badges ───────────────────────────────────────────── */
        .badge {
            display: inline-block; padding: 4px 12px;
            border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .badge-active  { background: rgba(34,197,94,0.15); color: #4ade80; }
        .badge-blocked { background: rgba(239,68,68,0.15);  color: #f87171; }

        /* ── Action Buttons ──────────────────────────────────────────── */
        .action-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-icon {
            width: 34px; height: 34px; border: none;
            border-radius: 8px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; transition: all 0.3s;
        }
        .btn-view   { background: rgba(6,182,212,0.15);  color: #06b6d4; }
        .btn-edit   { background: rgba(99,102,241,0.15); color: #818cf8; }
        .btn-block  { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .btn-delete { background: rgba(239,68,68,0.15);  color: #f87171; }
        .btn-icon:hover { transform: scale(1.15); }

        /* ── Pagination ──────────────────────────────────────────────── */
        .pagination {
            display: flex; justify-content: center; gap: 8px;
            padding: 20px;
        }
        .page-btn {
            width: 36px; height: 36px; border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.05); color: #e2e8f0;
            border-radius: 8px; cursor: pointer; font-size: 14px;
            transition: all 0.3s;
        }
        .page-btn.active, .page-btn:hover {
            background: #6366f1; border-color: #6366f1;
        }

        /* ── Modal Overlay ───────────────────────────────────────────── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.75); backdrop-filter: blur(6px);
            z-index: 500; align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #1c1e26;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px; width: 540px; max-width: 95%;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-head {
            padding: 22px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-head h3 { font-size: 18px; font-weight: 700; }
        .modal-close {
            background: none; border: none; color: rgba(255,255,255,0.5);
            font-size: 22px; cursor: pointer; transition: color 0.3s;
        }
        .modal-close:hover { color: #fff; }
        .modal-body-content { padding: 28px; }

        /* ── Form Fields inside Modals ───────────────────────────────── */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 13px; font-weight: 600;
            color: rgba(255,255,255,0.7); margin-bottom: 8px;
        }
        .form-group label i { color: #818cf8; margin-right: 6px; }
        .form-group input, .form-group select {
            width: 100%; padding: 12px 16px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px; color: #e2e8f0;
            font-size: 14px; outline: none; transition: border 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #6366f1;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field-error { color: #f87171; font-size: 12px; margin-top: 4px; display: none; }
        .form-footer {
            display: flex; gap: 12px; justify-content: flex-end;
            margin-top: 10px;
        }
        .btn-cancel {
            background: rgba(255,255,255,0.08); color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.12);
            padding: 11px 22px; border-radius: 10px;
            cursor: pointer; font-weight: 600; transition: all 0.3s;
        }
        .btn-cancel:hover { background: rgba(255,255,255,0.15); }
        .btn-save {
            background: linear-gradient(135deg,#6366f1,#ec4899);
            color: #fff; border: none;
            padding: 11px 28px; border-radius: 10px;
            cursor: pointer; font-weight: 600; transition: all 0.3s;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99,102,241,0.4); }

        /* ── Toast Notification ──────────────────────────────────────── */
        .toast {
            position: fixed; bottom: 28px; right: 28px;
            background: #1c1e26; border-radius: 14px;
            padding: 16px 22px; display: flex; align-items: center; gap: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.5);
            border-left: 4px solid #4ade80;
            transform: translateX(130%); transition: transform 0.4s;
            z-index: 999; font-size: 14px;
        }
        .toast.show { transform: translateX(0); }
        .toast.error { border-left-color: #f87171; }

        /* ── View Member Modal (read-only stats) ─────────────────────── */
        .stat-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; }
        .mini-stat {
            background: rgba(255,255,255,0.05); border-radius: 12px;
            padding: 16px; text-align: center;
        }
        .mini-stat-val {
            font-size: 22px; font-weight: 700;
            background: linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .mini-stat-lbl { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 4px; }
        .detail-row { display: flex; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .detail-row:last-child { border-bottom: none; }
        .detail-key { color: rgba(255,255,255,0.5); font-size: 13px; }
        .detail-val { font-size: 13px; font-weight: 500; }

        /* ── Responsive ──────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; padding: 20px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════════════════════════════ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-book-open"></i> LibTech
    </div>
    <nav class="sidebar-menu">
        <!-- Navigation links to other module dashboards -->
        <a href="../librarian-dashboard.php"  class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="member-management.php"       class="menu-item active"><i class="fas fa-users"></i> Members</a>
        <a href="../Books/book-management.php" class="menu-item"><i class="fas fa-book"></i> Books</a>
        <a href="../overdue-reports.php"       class="menu-item"><i class="fas fa-exclamation-circle"></i> Overdue</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<!-- ═══════════════════════════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════════════════════════════ -->
<main class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <div class="page-title">Member <span>Management</span></div>
            <p style="color:rgba(255,255,255,0.4);font-size:13px;margin-top:4px;">
                Manage library members – add, edit, block, search & filter
            </p>
        </div>
        <!-- Button opens the Add-Member modal -->
        <button class="btn-add" onclick="openAddModal()">
            <i class="fas fa-user-plus"></i> Add Member
        </button>
    </div>

    <!-- ── Stats Row (loaded via AJAX on page load) ──────────────── -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number" id="statTotal">–</div>
            <div class="stat-label">Total Members</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="statActive">–</div>
            <div class="stat-label">Active Members</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="statBlocked">–</div>
            <div class="stat-label">Blocked Members</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="statOverdue">–</div>
            <div class="stat-label">With Overdue Books</div>
        </div>
    </div>

    <!-- ── Search & Filter Bar ───────────────────────────────────── -->
    <div class="filter-bar">
        <!-- Search input – filters by name or email -->
        <input type="text" id="searchInput" placeholder="🔍 Search by name or email…" oninput="debounceSearch()">

        <!-- Status filter dropdown -->
        <select id="filterStatus" onchange="loadMembers()">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="blocked">Blocked</option>
        </select>

        <!-- Date range filter -->
        <input type="date" id="filterDateFrom" placeholder="Joined from" onchange="loadMembers()">
        <input type="date" id="filterDateTo"   placeholder="Joined to"   onchange="loadMembers()">

        <!-- Sort options -->
        <select id="sortBy" onchange="loadMembers()">
            <option value="member_id">Sort: ID</option>
            <option value="full_name">Sort: Name A-Z</option>
            <option value="membership_date">Sort: Join Date</option>
            <option value="total_overdue_count">Sort: Overdue</option>
        </select>

        <button class="btn-search" onclick="loadMembers()">
            <i class="fas fa-search"></i> Search
        </button>
        <button class="btn-reset" onclick="resetFilters()">
            <i class="fas fa-redo"></i> Reset
        </button>
    </div>

    <!-- ── Members Table ─────────────────────────────────────────── -->
    <div class="table-container">
        <div class="table-header">
            <span class="table-title"><i class="fas fa-list"></i> Members List</span>
            <span id="resultCount" style="font-size:13px;color:rgba(255,255,255,0.4);">Loading…</span>
        </div>
        <div id="tableBody">
            <!-- Rows injected by renderTable() -->
            <div style="padding:40px;text-align:center;color:rgba(255,255,255,0.3);">
                <i class="fas fa-spinner fa-spin" style="font-size:28px;"></i><br>
                Loading members…
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
    </div>
</main>

<!-- ═══════════════════════════════════════════════════════════
     MODAL – ADD / EDIT MEMBER
═══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="memberModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modalTitle"><i class="fas fa-user-plus"></i> Add New Member</h3>
            <button class="modal-close" onclick="closeModal('memberModal')">&times;</button>
        </div>
        <div class="modal-body-content">
            <!-- Hidden ID field – empty means "add", populated means "edit" -->
            <input type="hidden" id="memberId">

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" id="fullName" placeholder="e.g. Jane Doe" maxlength="100">
                    <div class="field-error" id="errName">Full name is required (min 2 chars).</div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" id="email" placeholder="jane@example.com" maxlength="100">
                    <div class="field-error" id="errEmail">Enter a valid email address.</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number *</label>
                    <input type="text" id="phone" placeholder="+45 1234 5678" maxlength="15">
                    <div class="field-error" id="errPhone">Phone must be 7–15 digits (+ allowed).</div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Membership Date *</label>
                    <input type="date" id="membershipDate">
                    <div class="field-error" id="errDate">Please select a membership date.</div>
                </div>
            </div>

            <div class="form-footer">
                <button class="btn-cancel" onclick="closeModal('memberModal')">Cancel</button>
                <button class="btn-save" onclick="saveMember()">
                    <i class="fas fa-save"></i> Save Member
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL – VIEW MEMBER DETAILS
═══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="fas fa-id-card"></i> Member Profile</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body-content" id="viewModalBody">
            <!-- Populated dynamically by viewMember() -->
        </div>
    </div>
</div>

<!-- ── Toast Notification ─────────────────────────────────────────── -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle" id="toastIcon"></i>
    <span id="toastMsg">Success</span>
</div>

<!-- ═══════════════════════════════════════════════════════════
     JAVASCRIPT – PRESENTATION & BUSINESS LOGIC CALLS
═══════════════════════════════════════════════════════════════ -->
<script>
/* ────────────────────────────────────────────────────────────
   STATE
──────────────────────────────────────────────────────────── */
let currentPage   = 1;       // pagination tracker
const perPage     = 10;      // rows per page
let searchTimer   = null;    // debounce timer for search input

/* ────────────────────────────────────────────────────────────
   INITIALISE on page load
──────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    // Set today as default for membership date in add form
    document.getElementById('membershipDate').value = new Date().toISOString().split('T')[0];
    loadStats();    // fetch summary statistics
    loadMembers();  // fetch member list
});

/* ────────────────────────────────────────────────────────────
   LOAD STATS  (GET api/member-api.php?action=stats)
──────────────────────────────────────────────────────────── */
function loadStats() {
    fetch('../api/member-api.php?action=stats')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('statTotal').textContent   = data.stats.total;
                document.getElementById('statActive').textContent  = data.stats.active;
                document.getElementById('statBlocked').textContent = data.stats.blocked;
                document.getElementById('statOverdue').textContent = data.stats.with_overdue;
            }
        })
        .catch(() => console.error('Failed to load stats'));
}

/* ────────────────────────────────────────────────────────────
   LOAD MEMBERS  (GET api/member-api.php?action=list&…)
   Sends current search/filter/sort/page params to API
──────────────────────────────────────────────────────────── */
function loadMembers(page = 1) {
    currentPage = page;

    // Build query string from filter inputs
    const params = new URLSearchParams({
        action:   'list',
        search:   document.getElementById('searchInput').value.trim(),
        status:   document.getElementById('filterStatus').value,
        dateFrom: document.getElementById('filterDateFrom').value,
        dateTo:   document.getElementById('filterDateTo').value,
        sortBy:   document.getElementById('sortBy').value,
        page:     currentPage,
        perPage:  perPage
    });

    document.getElementById('tableBody').innerHTML =
        '<div style="padding:40px;text-align:center;color:rgba(255,255,255,0.3);"><i class="fas fa-spinner fa-spin" style="font-size:28px;"></i></div>';

    fetch('../api/member-api.php?' + params)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderTable(data.members);
                renderPagination(data.total, data.perPage);
                document.getElementById('resultCount').textContent =
                    data.total + ' member(s) found';
            } else {
                showTableError(data.message);
            }
        })
        .catch(() => showTableError('Connection error – please try again.'));
}

/* ────────────────────────────────────────────────────────────
   RENDER TABLE ROWS
──────────────────────────────────────────────────────────── */
function renderTable(members) {
    if (!members || members.length === 0) {
        document.getElementById('tableBody').innerHTML =
            '<div style="padding:50px;text-align:center;color:rgba(255,255,255,0.3);">' +
            '<i class="fas fa-users" style="font-size:40px;"></i><br><br>No members found.</div>';
        return;
    }

    /* Build table HTML */
    let html = '<table><thead><tr>' +
        '<th>ID</th><th>Full Name</th><th>Email</th>' +
        '<th>Phone</th><th>Joined</th><th>Status</th>' +
        '<th>Overdue</th><th>Actions</th>' +
        '</tr></thead><tbody>';

    members.forEach(m => {
        const badge    = m.member_is_blocked == 1
            ? '<span class="badge badge-blocked"><i class="fas fa-ban"></i> Blocked</span>'
            : '<span class="badge badge-active"><i class="fas fa-check"></i> Active</span>';

        const blockLbl = m.member_is_blocked == 1 ? 'Unblock' : 'Block';
        const blockIco = m.member_is_blocked == 1 ? 'fa-lock-open' : 'fa-ban';

        html += `<tr>
            <td>#${m.member_id}</td>
            <td><strong>${escHtml(m.full_name)}</strong></td>
            <td>${escHtml(m.email)}</td>
            <td>${escHtml(m.phone)}</td>
            <td>${m.membership_date}</td>
            <td>${badge}</td>
            <td><span style="color:${m.total_overdue_count > 0 ? '#f87171' : '#4ade80'}">
                ${m.total_overdue_count}</span></td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon btn-view"   title="View"   onclick="viewMember(${m.member_id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon btn-edit"   title="Edit"   onclick="editMember(${m.member_id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-block"  title="${blockLbl}" onclick="toggleBlock(${m.member_id},${m.member_is_blocked})"><i class="fas ${blockIco}"></i></button>
                    <button class="btn-icon btn-delete" title="Delete" onclick="deleteMember(${m.member_id},'${escHtml(m.full_name)}')"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    document.getElementById('tableBody').innerHTML = html;
}

/* ────────────────────────────────────────────────────────────
   RENDER PAGINATION BUTTONS
──────────────────────────────────────────────────────────── */
function renderPagination(total, perPage) {
    const pages = Math.ceil(total / perPage);
    let html    = '';
    for (let i = 1; i <= pages; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}"
                    onclick="loadMembers(${i})">${i}</button>`;
    }
    document.getElementById('pagination').innerHTML = html;
}

/* ────────────────────────────────────────────────────────────
   ADD MEMBER – open blank form modal
──────────────────────────────────────────────────────────── */
function openAddModal() {
    // Clear form fields
    document.getElementById('memberId').value        = '';
    document.getElementById('fullName').value        = '';
    document.getElementById('email').value           = '';
    document.getElementById('phone').value           = '';
    document.getElementById('membershipDate').value  = new Date().toISOString().split('T')[0];
    clearErrors();

    document.getElementById('modalTitle').innerHTML =
        '<i class="fas fa-user-plus"></i> Add New Member';
    openModal('memberModal');
}

/* ────────────────────────────────────────────────────────────
   EDIT MEMBER – fetch existing data, pre-fill form
──────────────────────────────────────────────────────────── */
function editMember(id) {
    fetch(`../api/member-api.php?action=find&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast(data.message, true); return; }
            const m = data.member;

            // Pre-fill form with existing values
            document.getElementById('memberId').value        = m.member_id;
            document.getElementById('fullName').value        = m.full_name;
            document.getElementById('email').value           = m.email;
            document.getElementById('phone').value           = m.phone;
            document.getElementById('membershipDate').value  = m.membership_date;
            clearErrors();

            document.getElementById('modalTitle').innerHTML =
                '<i class="fas fa-edit"></i> Edit Member – ' + escHtml(m.full_name);
            openModal('memberModal');
        })
        .catch(() => showToast('Could not load member data.', true));
}

/* ────────────────────────────────────────────────────────────
   SAVE MEMBER  (POST api/member-api.php)
   Validates inputs client-side, then sends to server
──────────────────────────────────────────────────────────── */
function saveMember() {
    // ── CLIENT-SIDE VALIDATION ──────────────────────────────
    const id      = document.getElementById('memberId').value;
    const name    = document.getElementById('fullName').value.trim();
    const email   = document.getElementById('email').value.trim();
    const phone   = document.getElementById('phone').value.trim();
    const date    = document.getElementById('membershipDate').value;

    let valid = true;
    clearErrors();

    // Validate full name – must be at least 2 characters
    if (name.length < 2) {
        document.getElementById('errName').style.display = 'block'; valid = false;
    }
    // Validate email – simple regex check
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById('errEmail').style.display = 'block'; valid = false;
    }
    // Validate phone – 7-15 digits, optional leading +
    if (!/^\+?[\d\s\-]{7,15}$/.test(phone)) {
        document.getElementById('errPhone').style.display = 'block'; valid = false;
    }
    // Validate date – must be selected
    if (!date) {
        document.getElementById('errDate').style.display = 'block'; valid = false;
    }

    if (!valid) return;   // stop if any validation failed

    // ── SEND TO API ──────────────────────────────────────────
    const action = id ? 'update' : 'add';
    const body   = { action, full_name: name, email, phone, membership_date: date };
    if (id) body.member_id = id;

    fetch('../api/member-api.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(body)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeModal('memberModal');
            showToast(data.message);
            loadMembers(currentPage);   // refresh table
            loadStats();                // refresh stats
        } else {
            showToast(data.message, true);
        }
    })
    .catch(() => showToast('Save failed – connection error.', true));
}

/* ────────────────────────────────────────────────────────────
   VIEW MEMBER – display read-only profile in modal
──────────────────────────────────────────────────────────── */
function viewMember(id) {
    fetch(`../api/member-api.php?action=find&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast(data.message, true); return; }
            const m   = data.member;
            const blk = m.member_is_blocked == 1;

            document.getElementById('viewModalBody').innerHTML = `
                <div class="detail-row"><span class="detail-key">Member ID</span>  <span class="detail-val">#${m.member_id}</span></div>
                <div class="detail-row"><span class="detail-key">Full Name</span>  <span class="detail-val">${escHtml(m.full_name)}</span></div>
                <div class="detail-row"><span class="detail-key">Email</span>      <span class="detail-val">${escHtml(m.email)}</span></div>
                <div class="detail-row"><span class="detail-key">Phone</span>      <span class="detail-val">${escHtml(m.phone)}</span></div>
                <div class="detail-row"><span class="detail-key">Joined</span>     <span class="detail-val">${m.membership_date}</span></div>
                <div class="detail-row"><span class="detail-key">Status</span>
                    <span class="detail-val">
                        <span class="badge ${blk ? 'badge-blocked' : 'badge-active'}">
                            ${blk ? '🚫 Blocked' : '✅ Active'}
                        </span>
                    </span>
                </div>
                <div class="stat-row">
                    <div class="mini-stat">
                        <div class="mini-stat-val">${m.books_borrowed_current}</div>
                        <div class="mini-stat-lbl">Currently Borrowed</div>
                    </div>
                    <div class="mini-stat">
                        <div class="mini-stat-val" style="color:${m.total_overdue_count>0?'#f87171':'inherit'}">${m.total_overdue_count}</div>
                        <div class="mini-stat-lbl">Total Overdue</div>
                    </div>
                </div>`;
            openModal('viewModal');
        })
        .catch(() => showToast('Could not load member profile.', true));
}

/* ────────────────────────────────────────────────────────────
   TOGGLE BLOCK / UNBLOCK  (POST api/member-api.php)
──────────────────────────────────────────────────────────── */
function toggleBlock(id, isBlocked) {
    const newState = isBlocked == 1 ? 0 : 1;
    const action   = newState ? 'block' : 'unblock';
    const label    = newState ? 'block' : 'unblock';

    if (!confirm(`Are you sure you want to ${label} this member?`)) return;

    fetch('../api/member-api.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ action: 'toggle_block', member_id: id, blocked: newState })
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, !data.success);
        if (data.success) { loadMembers(currentPage); loadStats(); }
    })
    .catch(() => showToast('Action failed – connection error.', true));
}

/* ────────────────────────────────────────────────────────────
   DELETE MEMBER  (POST api/member-api.php)
──────────────────────────────────────────────────────────── */
function deleteMember(id, name) {
    if (!confirm(`Delete member "${name}"? This cannot be undone.`)) return;

    fetch('../api/member-api.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ action: 'delete', member_id: id })
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, !data.success);
        if (data.success) { loadMembers(currentPage); loadStats(); }
    })
    .catch(() => showToast('Delete failed – connection error.', true));
}

/* ────────────────────────────────────────────────────────────
   RESET FILTERS
──────────────────────────────────────────────────────────── */
function resetFilters() {
    document.getElementById('searchInput').value   = '';
    document.getElementById('filterStatus').value  = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value   = '';
    document.getElementById('sortBy').value         = 'member_id';
    loadMembers(1);
}

/* ────────────────────────────────────────────────────────────
   DEBOUNCE for live search (waits 400ms after typing stops)
──────────────────────────────────────────────────────────── */
function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadMembers(1), 400);
}

/* ────────────────────────────────────────────────────────────
   UTILITY – Modal helpers
──────────────────────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

/* ────────────────────────────────────────────────────────────
   UTILITY – Clear all field-level error messages
──────────────────────────────────────────────────────────── */
function clearErrors() {
    document.querySelectorAll('.field-error').forEach(el => el.style.display = 'none');
}

/* ────────────────────────────────────────────────────────────
   UTILITY – Show toast notification
──────────────────────────────────────────────────────────── */
function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIcon').className  =
        isError ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';

    toast.className = 'toast' + (isError ? ' error' : '');
    // Small delay ensures CSS transition triggers
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => toast.classList.remove('show'), 3500);
}

/* ────────────────────────────────────────────────────────────
   UTILITY – Render a table-level error row
──────────────────────────────────────────────────────────── */
function showTableError(msg) {
    document.getElementById('tableBody').innerHTML =
        `<div style="padding:50px;text-align:center;color:#f87171;">
            <i class="fas fa-exclamation-triangle" style="font-size:30px;"></i><br><br>${msg}
         </div>`;
}

/* ────────────────────────────────────────────────────────────
   UTILITY – Escape HTML to prevent XSS
──────────────────────────────────────────────────────────── */
function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}

/* Close modal when clicking the dark overlay */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});
</script>
</body>
</html>
