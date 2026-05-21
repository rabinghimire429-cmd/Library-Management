<?php
/**
 * Member Profile Page (Self-Service)
 * ====================================
 * PRESENTATION LAYER
 *
 * Logged-in members can:
 *  - View their profile details & borrowing statistics
 *  - Edit their contact details (name, phone)
 *  - Request account closure (sets cancellation flag)
 *
 * Data is fetched/saved via api/member-profile-api.php
 */

require_once '../config.php';

// Only members may access this page
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Member') {
    header('Location: ../index.php');
    exit();
}

$memberId = (int)$_SESSION['member_id']; // stored in session at login
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | LibTech Solutions</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* ── Reset & Body ────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f1419;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ─────────────────────────────────────────────────── */
        .sidebar {
            width: 240px; background: #1c1e26;
            border-right: 1px solid rgba(255,255,255,0.08);
            display: flex; flex-direction: column; min-height: 100vh;
        }
        .sidebar-logo {
            padding: 22px 20px; font-size: 18px; font-weight: 800;
            background: linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-menu { flex: 1; padding: 18px 0; }
        .menu-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 20px; color: rgba(255,255,255,0.6);
            text-decoration: none; font-size: 14px; transition: all 0.3s;
        }
        .menu-item.active, .menu-item:hover {
            background: rgba(99,102,241,0.15);
            color: #818cf8; border-left: 3px solid #818cf8;
        }
        .logout-btn {
            display: flex; align-items: center; gap: 10px;
            color: #f87171; text-decoration: none;
            font-size: 14px; padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            transition: all 0.3s;
        }
        .logout-btn:hover { background: rgba(239,68,68,0.1); }

        /* ── Main Content ────────────────────────────────────────────── */
        .main { flex: 1; padding: 34px; }
        .page-title {
            font-size: 24px; font-weight: 700; margin-bottom: 28px;
        }
        .page-title span {
            background: linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        /* ── Profile Card ────────────────────────────────────────────── */
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card {
            background: #1c1e26;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px; padding: 28px;
        }
        .card-title {
            font-size: 15px; font-weight: 700; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px; color: #818cf8;
        }

        /* ── Details list ────────────────────────────────────────────── */
        .detail-row {
            display: flex; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 14px;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-key { color: rgba(255,255,255,0.5); }
        .detail-val { font-weight: 500; }

        /* ── Stats mini-cards ────────────────────────────────────────── */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 4px; }
        .mini-stat {
            background: rgba(255,255,255,0.05);
            border-radius: 12px; padding: 18px; text-align: center;
        }
        .mini-val {
            font-size: 26px; font-weight: 800;
            background: linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .mini-lbl { font-size: 12px; color: rgba(255,255,255,0.45); margin-top: 4px; }

        /* ── Edit Form ───────────────────────────────────────────────── */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block; font-size: 12px; font-weight: 600;
            color: rgba(255,255,255,0.6); margin-bottom: 7px;
        }
        .form-group label i { color: #818cf8; margin-right: 5px; }
        .form-group input {
            width: 100%; padding: 11px 14px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px; color: #e2e8f0;
            font-size: 14px; outline: none; transition: border 0.3s;
        }
        .form-group input:focus { border-color: #6366f1; }
        .form-group input[readonly] { opacity: 0.5; cursor: not-allowed; }
        .field-error { color: #f87171; font-size: 11px; margin-top: 4px; display: none; }
        .btn-save {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg,#6366f1,#ec4899);
            border: none; border-radius: 10px; color: #fff;
            font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(99,102,241,0.4); }

        /* ── Danger Zone ─────────────────────────────────────────────── */
        .danger-zone {
            grid-column: 1 / -1;
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 20px; padding: 24px;
            background: rgba(239,68,68,0.05);
        }
        .danger-zone h4 { color: #f87171; margin-bottom: 10px; font-size: 15px; }
        .danger-zone p  { color: rgba(255,255,255,0.5); font-size: 13px; margin-bottom: 16px; }
        .btn-danger {
            background: rgba(239,68,68,0.15); color: #f87171;
            border: 1px solid rgba(239,68,68,0.3);
            padding: 10px 22px; border-radius: 10px;
            cursor: pointer; font-weight: 600; transition: all 0.3s;
        }
        .btn-danger:hover { background: rgba(239,68,68,0.25); }

        /* ── Alert Banner ────────────────────────────────────────────── */
        .alert {
            border-radius: 12px; padding: 12px 16px; font-size: 13px;
            margin-bottom: 22px; display: none;
        }
        .alert-success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }
        .alert-error   { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }

        /* ── Loading Spinner ─────────────────────────────────────────── */
        #loadingState {
            text-align: center; padding: 60px;
            color: rgba(255,255,255,0.3);
        }

        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
            .sidebar { width: 180px; }
            .main { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
<aside class="sidebar">
    <div class="sidebar-logo"><i class="fas fa-book-open"></i> LibTech</div>
    <nav class="sidebar-menu">
        <a href="../member-dashboard.php"         class="menu-item"><i class="fas fa-home"></i> Home</a>
        <a href="member-profile.php"              class="menu-item active"><i class="fas fa-user"></i> My Profile</a>
        <a href="../Books/book-catalog.php"        class="menu-item"><i class="fas fa-book"></i> Browse Books</a>
    </nav>
    <a href="../auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</aside>

<!-- ── Main Content ─────────────────────────────────────────────────────── -->
<main class="main">
    <div class="page-title">My <span>Profile</span></div>

    <!-- Alert banners -->
    <div class="alert alert-success" id="alertSuccess"><i class="fas fa-check-circle"></i> <span id="succMsg"></span></div>
    <div class="alert alert-error"   id="alertError">  <i class="fas fa-exclamation-triangle"></i> <span id="errMsg"></span></div>

    <!-- Loading state -->
    <div id="loadingState">
        <i class="fas fa-spinner fa-spin" style="font-size:32px;"></i><br><br>Loading profile…
    </div>

    <!-- Profile Grid (hidden until data loads) -->
    <div class="profile-grid" id="profileGrid" style="display:none;">

        <!-- ── Profile Details (read-only) ───────────────────────────── -->
        <div class="card">
            <div class="card-title"><i class="fas fa-id-card"></i> Account Details</div>
            <div id="detailsPane"><!-- populated by JS --></div>
        </div>

        <!-- ── Borrowing Statistics ──────────────────────────────────── -->
        <div class="card">
            <div class="card-title"><i class="fas fa-chart-bar"></i> My Statistics</div>
            <div class="stats-grid" id="statsPane"><!-- populated by JS --></div>
        </div>

        <!-- ── Edit Contact Details ──────────────────────────────────── -->
        <div class="card">
            <div class="card-title"><i class="fas fa-edit"></i> Edit Contact Details</div>

            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name *</label>
                <input type="text" id="editName" placeholder="Full Name" maxlength="100">
                <div class="field-error" id="errName">Name must be at least 2 characters.</div>
            </div>

            <!-- Email is read-only (used for login) -->
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email (cannot change)</label>
                <input type="email" id="editEmail" readonly>
            </div>

            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone Number *</label>
                <input type="text" id="editPhone" placeholder="+45 1234 5678" maxlength="15">
                <div class="field-error" id="errPhone">Phone must be 7–15 digits.</div>
            </div>

            <button class="btn-save" onclick="saveProfile()">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>

        <!-- ── Danger Zone – Cancel Membership ───────────────────────── -->
        <div class="danger-zone">
            <h4><i class="fas fa-exclamation-triangle"></i> Cancel Membership</h4>
            <p>Requesting cancellation will flag your account for closure. A librarian will
               review and confirm. You will still have access until the librarian processes it.</p>
            <button class="btn-danger" onclick="requestCancellation()">
                <i class="fas fa-times-circle"></i> Request Account Cancellation
            </button>
        </div>

    </div><!-- /profile-grid -->
</main>

<!-- ── JavaScript ───────────────────────────────────────────────────────── -->
<script>
// Member ID passed from PHP session
const MEMBER_ID = <?= $memberId ?>;

/* ──────────────────────────────────────────────────────────────────────────
   LOAD PROFILE on page-ready
────────────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', loadProfile);

function loadProfile() {
    fetch(`api/member-profile-api.php?action=get&id=${MEMBER_ID}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingState').style.display = 'none';
            if (!data.success) { showAlert(data.message, true); return; }

            const m = data.member;

            // ── Details pane (read-only) ──────────────────────────────
            document.getElementById('detailsPane').innerHTML = `
                <div class="detail-row"><span class="detail-key">Member ID</span>  <span class="detail-val">#${m.member_id}</span></div>
                <div class="detail-row"><span class="detail-key">Full Name</span>  <span class="detail-val">${esc(m.full_name)}</span></div>
                <div class="detail-row"><span class="detail-key">Email</span>      <span class="detail-val">${esc(m.email)}</span></div>
                <div class="detail-row"><span class="detail-key">Phone</span>      <span class="detail-val">${esc(m.phone)}</span></div>
                <div class="detail-row"><span class="detail-key">Member Since</span><span class="detail-val">${m.membership_date}</span></div>
                <div class="detail-row"><span class="detail-key">Status</span>
                    <span class="detail-val">
                        <span style="color:${m.member_is_blocked==1?'#f87171':'#4ade80'}">
                            ${m.member_is_blocked==1 ? '🚫 Blocked' : '✅ Active'}
                        </span>
                    </span>
                </div>`;

            // ── Stats pane ────────────────────────────────────────────
            document.getElementById('statsPane').innerHTML = `
                <div class="mini-stat">
                    <div class="mini-val">${m.books_borrowed_current}</div>
                    <div class="mini-lbl">Currently Borrowed</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-val" style="${m.total_overdue_count>0?'color:#f87171':''}">${m.total_overdue_count}</div>
                    <div class="mini-lbl">Total Overdue</div>
                </div>`;

            // ── Pre-fill edit form ────────────────────────────────────
            document.getElementById('editName').value  = m.full_name;
            document.getElementById('editEmail').value = m.email;
            document.getElementById('editPhone').value = m.phone;

            document.getElementById('profileGrid').style.display = 'grid';
        })
        .catch(() => {
            document.getElementById('loadingState').style.display = 'none';
            showAlert('Could not load your profile. Please refresh the page.', true);
        });
}

/* ──────────────────────────────────────────────────────────────────────────
   SAVE PROFILE – validate then POST to API
────────────────────────────────────────────────────────────────────────── */
function saveProfile() {
    // Clear existing errors
    document.querySelectorAll('.field-error').forEach(e => e.style.display = 'none');

    const name  = document.getElementById('editName').value.trim();
    const phone = document.getElementById('editPhone').value.trim();
    let valid   = true;

    // Validate name
    if (name.length < 2) {
        document.getElementById('errName').style.display = 'block'; valid = false;
    }
    // Validate phone
    if (!/^\+?[\d\s\-]{7,15}$/.test(phone)) {
        document.getElementById('errPhone').style.display = 'block'; valid = false;
    }

    if (!valid) return;

    // Send update request to profile-specific API
    fetch('api/member-profile-api.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', member_id: MEMBER_ID, full_name: name, phone: phone })
    })
    .then(r => r.json())
    .then(data => {
        showAlert(data.message, !data.success);
        if (data.success) loadProfile();   // refresh display
    })
    .catch(() => showAlert('Save failed. Please try again.', true));
}

/* ──────────────────────────────────────────────────────────────────────────
   REQUEST CANCELLATION
────────────────────────────────────────────────────────────────────────── */
function requestCancellation() {
    if (!confirm('Are you sure you want to request account cancellation? A librarian will review your request.')) return;

    fetch('api/member-profile-api.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'request_cancellation', member_id: MEMBER_ID })
    })
    .then(r => r.json())
    .then(data => showAlert(data.message, !data.success))
    .catch(() => showAlert('Request failed. Please try again.', true));
}

/* ── Utility helpers ──────────────────────────────────────────────────── */
function showAlert(msg, isError = false) {
    document.getElementById('alertSuccess').style.display = 'none';
    document.getElementById('alertError').style.display   = 'none';

    if (isError) {
        document.getElementById('errMsg').textContent  = msg;
        document.getElementById('alertError').style.display = 'block';
    } else {
        document.getElementById('succMsg').textContent  = msg;
        document.getElementById('alertSuccess').style.display = 'block';
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}
</script>
</body>
</html>
