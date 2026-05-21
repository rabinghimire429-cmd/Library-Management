<?php
/**
 * Member Self-Registration Page
 * ================================
 * PRESENTATION LAYER
 *
 * Allows new users to register themselves as library members.
 * Submits to api/member-registration-api.php (POST).
 * Matching visual style as the rest of the LibTech project.
 */

// Include config for session + DB connection
require_once '../config.php';

if (isset($_SESSION['admin_id'])) {
    if ($_SESSION['admin_role'] === 'Member') {
        header('Location: ../member-dashboard.php');
        exit();
    }
    // Librarians are allowed to access this page to register members
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | LibTech Solutions</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* ── Reset & Base ────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a2a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }

        /* ── Animated Background ─────────────────────────────────────── */
        .bg {
            position: fixed; inset: 0; z-index: -1;
            background: radial-gradient(circle at 20% 50%, rgba(99,102,241,0.25), transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(236,72,153,0.25), transparent 50%);
        }

        /* ── Registration Card ───────────────────────────────────────── */
        .reg-card {
            background: rgba(28, 30, 38, 0.97);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 28px;
            width: 520px; max-width: 94%;
            padding: 42px;
        }

        /* ── Card Header ─────────────────────────────────────────────── */
        .card-logo {
            text-align: center; margin-bottom: 28px;
        }
        .card-logo h1 {
            font-size: 26px; font-weight: 800;
            background: linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .card-logo p {
            color: rgba(255,255,255,0.5); font-size: 14px; margin-top: 6px;
        }

        /* ── Form Inputs ─────────────────────────────────────────────── */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 13px; font-weight: 600;
            color: rgba(255,255,255,0.7); margin-bottom: 8px;
        }
        .form-group label i { color: #818cf8; margin-right: 6px; }
        .form-group input {
            width: 100%; padding: 13px 16px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px; color: #e2e8f0;
            font-size: 14px; outline: none; transition: border 0.3s;
        }
        .form-group input:focus { border-color: #6366f1; }

        /* ── Field-level error messages ──────────────────────────────── */
        .field-error {
            color: #f87171; font-size: 12px;
            margin-top: 5px; display: none;
        }

        /* ── Submit Button ───────────────────────────────────────────── */
        .btn-register {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg,#6366f1,#ec4899);
            border: none; border-radius: 40px;
            color: #fff; font-size: 16px; font-weight: 700;
            cursor: pointer; margin-top: 6px; transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99,102,241,0.4);
        }
        .btn-register:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* ── Alert Banners ───────────────────────────────────────────── */
        .alert {
            border-radius: 12px; padding: 13px 16px;
            font-size: 13px; margin-bottom: 20px; display: none;
        }
        .alert-success {
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.3);
            color: #4ade80;
        }
        .alert-error {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.3);
            color: #f87171;
        }

        /* ── Footer Link ─────────────────────────────────────────────── */
        .card-footer {
            text-align: center; margin-top: 24px;
            font-size: 13px; color: rgba(255,255,255,0.5);
        }
        .card-footer a { color: #818cf8; text-decoration: none; }
        .card-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="bg"></div>

<!-- ── Registration Card ──────────────────────────────────────────────── -->
<div class="reg-card">

    <!-- Header -->
    <div class="card-logo">
        <h1><i class="fas fa-book-open"></i> LibTech Solutions</h1>
        <p>Create your member account</p>
    </div>

    <!-- Alert banners (shown/hidden by JS) -->
    <div class="alert alert-success" id="alertSuccess">
        <i class="fas fa-check-circle"></i> <span id="successMsg"></span>
    </div>
    <div class="alert alert-error" id="alertError">
        <i class="fas fa-exclamation-triangle"></i> <span id="errorMsg"></span>
    </div>

    <!-- Registration Form -->
    <div class="form-group">
        <label><i class="fas fa-user"></i> Full Name *</label>
        <input type="text" id="fullName" placeholder="Jane Doe" maxlength="100">
        <div class="field-error" id="errName">Full name must be at least 2 characters.</div>
    </div>

    <div class="form-group">
        <label><i class="fas fa-envelope"></i> Email Address *</label>
        <input type="email" id="email" placeholder="jane@example.com" maxlength="100">
        <div class="field-error" id="errEmail">Please enter a valid email address.</div>
    </div>

    <div class="form-group">
        <label><i class="fas fa-phone"></i> Phone Number *</label>
        <input type="text" id="phone" placeholder="+45 1234 5678" maxlength="15">
        <div class="field-error" id="errPhone">Phone must be 7–15 digits.</div>
    </div>

    <div class="form-group">
        <label><i class="fas fa-lock"></i> Password *</label>
        <input type="password" id="password" placeholder="Minimum 6 characters">
        <div class="field-error" id="errPassword">Password must be at least 6 characters.</div>
    </div>

    <div class="form-group">
        <label><i class="fas fa-lock"></i> Confirm Password *</label>
        <input type="password" id="confirmPassword" placeholder="Repeat your password">
        <div class="field-error" id="errConfirm">Passwords do not match.</div>
    </div>

    <!-- Submit -->
    <button class="btn-register" id="regBtn" onclick="doRegister()">
        <i class="fas fa-user-plus"></i> Create Account
    </button>

    <!-- Login link -->
    <div class="card-footer">
        Already a member? <a href="../index.php">Log in here</a>
    </div>
</div>

<!-- ── JavaScript: Client-side Validation + AJAX Submit ──────────────── -->
<script>
/**
 * doRegister()
 * Called when user clicks "Create Account".
 * 1. Validates all fields client-side.
 * 2. POSTs to api/member-registration-api.php via fetch().
 * 3. Displays success or error feedback.
 */
function doRegister() {
    // Clear previous messages
    clearAll();

    const name    = document.getElementById('fullName').value.trim();
    const email   = document.getElementById('email').value.trim();
    const phone   = document.getElementById('phone').value.trim();
    const pwd     = document.getElementById('password').value;
    const confirm = document.getElementById('confirmPassword').value;

    // ── Client-side validation ──────────────────────────────────────────
    let valid = true;

    if (name.length < 2) {
        document.getElementById('errName').style.display = 'block'; valid = false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById('errEmail').style.display = 'block'; valid = false;
    }
    if (!/^\+?[\d\s\-]{7,15}$/.test(phone)) {
        document.getElementById('errPhone').style.display = 'block'; valid = false;
    }
    if (pwd.length < 6) {
        document.getElementById('errPassword').style.display = 'block'; valid = false;
    }
    if (pwd !== confirm) {
        document.getElementById('errConfirm').style.display = 'block'; valid = false;
    }

    if (!valid) return;   // abort if any field is invalid

    // ── Submit to API ───────────────────────────────────────────────────
    const btn = document.getElementById('regBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering…';

    fetch('../api/member-registration-api.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            full_name:  name,
            email:      email,
            phone:      phone,
            password:   pwd
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';

        if (data.success) {
            // Show success and redirect to login after 2s
            showSuccess(data.message + ' Redirecting to login…');
            setTimeout(() => { window.location.href = '../index.php'; }, 2200);
        } else {
            showError(data.message);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
        showError('Connection error. Please try again.');
    });
}

/* ── Utility helpers ──────────────────────────────────────────────────── */
function clearAll() {
    document.querySelectorAll('.field-error').forEach(e => e.style.display = 'none');
    document.getElementById('alertSuccess').style.display = 'none';
    document.getElementById('alertError').style.display   = 'none';
}

function showSuccess(msg) {
    document.getElementById('successMsg').textContent = msg;
    document.getElementById('alertSuccess').style.display = 'block';
}

function showError(msg) {
    document.getElementById('errorMsg').textContent = msg;
    document.getElementById('alertError').style.display = 'block';
}

/* Allow pressing Enter to submit */
document.addEventListener('keydown', e => { if (e.key === 'Enter') doRegister(); });
</script>
</body>
</html>
