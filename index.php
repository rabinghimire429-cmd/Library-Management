<?php
require_once 'config.php';

if(isset($_SESSION['admin_id'])) {
    if($_SESSION['admin_role'] == 'Librarian') {
        header('Location: librarian-dashboard.php');
    } else {
        header('Location: member-dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibTech Solutions - Smart Library Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a2a; color: white; overflow-x: hidden; }
        .bg-animation { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; }
        .bg-animation .gradient { position: absolute; width: 100%; height: 100%; background: radial-gradient(circle at 20% 50%, rgba(99,102,241,0.3), transparent 50%), radial-gradient(circle at 80% 80%, rgba(236,72,153,0.3), transparent 50%); }
        .bg-animation .orb { position: absolute; border-radius: 50%; filter: blur(60px); animation: float 20s infinite; }
        .orb-1 { width: 400px; height: 400px; background: #6366f1; top: -100px; left: -100px; opacity: 0.2; }
        .orb-2 { width: 500px; height: 500px; background: #ec4899; bottom: -150px; right: -150px; opacity: 0.15; animation-delay: 5s; }
        .orb-3 { width: 300px; height: 300px; background: #06b6d4; top: 40%; left: 70%; opacity: 0.15; animation-delay: 10s; }
        @keyframes float { 0%,100% { transform: translate(0,0); } 33% { transform: translate(30px,-30px); } 66% { transform: translate(-20px,20px); } }
        .navbar { position: fixed; top: 0; left: 0; right: 0; background: rgba(10,10,42,0.9); backdrop-filter: blur(12px); padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-icon { font-size: 28px; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-links { display: flex; align-items: center; gap: 30px; }
        .nav-links a { color: rgba(255,255,255,0.8); text-decoration: none; font-weight: 500; transition: all 0.3s; font-size: 14px; }
        .nav-links a:hover { color: #818cf8; }
        .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 120px 40px 80px; }
        .hero-content { max-width: 900px; }
        .hero-badge { display: inline-block; background: rgba(99,102,241,0.2); padding: 8px 20px; border-radius: 40px; font-size: 14px; margin-bottom: 30px; border: 1px solid rgba(99,102,241,0.3); }
        .hero h1 { font-size: 64px; font-weight: 800; margin-bottom: 20px; line-height: 1.2; }
        .hero-gradient { background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { font-size: 18px; color: rgba(255,255,255,0.7); margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto; }
        .cta-buttons { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        .btn-primary { background: linear-gradient(135deg, #6366f1, #ec4899); padding: 14px 32px; border-radius: 40px; color: white; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(99,102,241,0.4); }
        .btn-secondary { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); padding: 14px 32px; border-radius: 40px; color: white; text-decoration: none; font-weight: 600; transition: all 0.3s; }
        .btn-secondary:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 2000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: rgba(20,20,50,0.98); backdrop-filter: blur(12px); border-radius: 30px; width: 480px; max-width: 90%; border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .modal-header { padding: 25px 30px 15px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .modal-header h2 { font-size: 28px; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .modal-body { padding: 25px 30px; }
        .role-selector { display: flex; gap: 15px; margin-bottom: 25px; }
        .role-btn { flex: 1; padding: 14px; background: rgba(255,255,255,0.08); border: 2px solid rgba(255,255,255,0.1); border-radius: 50px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s; text-align: center; }
        .role-btn.active { background: linear-gradient(135deg, #6366f1, #ec4899); border-color: transparent; }
        .role-btn:hover { background: rgba(99,102,241,0.5); }
        .login-form { display: none; }
        .login-form.active { display: block; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 13px; color: rgba(255,255,255,0.7); }
        .input-group label i { margin-right: 8px; color: #818cf8; }
        .input-group input { width: 100%; padding: 14px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; font-size: 14px; transition: all 0.3s; }
        .input-group input:focus { outline: none; border-color: #6366f1; background: rgba(255,255,255,0.12); }
        .login-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 40px; color: white; font-weight: 600; font-size: 16px; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .login-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(99,102,241,0.4); }
        .alert-error { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 12px; margin-bottom: 20px; font-size: 13px; color: #f87171; text-align: center; }
        .modal-footer { padding: 20px 30px 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); }
        .modal-footer a { color: #818cf8; text-decoration: none; }
        .demo-info { font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 15px; text-align: center; }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: rgba(255,255,255,0.5); transition: all 0.3s; }
        .close-modal:hover { color: white; }
        .features-section { padding: 100px 40px; background: rgba(0,0,0,0.3); }
        .section-title { text-align: center; font-size: 42px; font-weight: 700; margin-bottom: 20px; }
        .section-subtitle { text-align: center; color: rgba(255,255,255,0.6); margin-bottom: 60px; font-size: 18px; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }
        .feature-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 35px; text-align: center; transition: all 0.3s; }
        .feature-card:hover { transform: translateY(-8px); border-color: #6366f1; background: rgba(255,255,255,0.08); }
        .feature-icon { font-size: 55px; margin-bottom: 20px; }
        .feature-card h3 { font-size: 22px; margin-bottom: 15px; }
        .feature-card p { color: rgba(255,255,255,0.6); line-height: 1.6; }
        .about-section { padding: 100px 40px; display: flex; flex-wrap: wrap; gap: 50px; max-width: 1200px; margin: 0 auto; align-items: center; }
        .about-content { flex: 1; }
        .about-content h2 { font-size: 36px; margin-bottom: 20px; }
        .about-content p { color: rgba(255,255,255,0.7); line-height: 1.7; margin-bottom: 20px; }
        .team-badge { display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
        .team-badge span { background: rgba(99,102,241,0.2); padding: 8px 16px; border-radius: 40px; font-size: 13px; border: 1px solid rgba(99,102,241,0.3); }
        .about-stats { flex: 1; display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .stat-box { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 30px; text-align: center; }
        .stat-number { font-size: 42px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .contact-section { padding: 100px 40px; background: rgba(0,0,0,0.2); }
        .contact-container { display: flex; flex-wrap: wrap; gap: 50px; max-width: 1200px; margin: 0 auto; }
        .contact-info { flex: 1; }
        .contact-info h3 { font-size: 28px; margin-bottom: 25px; }
        .contact-detail { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
        .contact-icon { width: 50px; height: 50px; background: rgba(99,102,241,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .contact-map { flex: 1; background: rgba(255,255,255,0.05); border-radius: 30px; padding: 30px; text-align: center; }
        .map-placeholder { background: rgba(255,255,255,0.1); border-radius: 20px; padding: 60px; margin-top: 20px; }
        .footer { background: rgba(0,0,0,0.5); padding: 40px; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); }
        .footer p { color: rgba(255,255,255,0.5); font-size: 14px; }
        .social-links { display: flex; justify-content: center; gap: 20px; margin-top: 20px; }
        .social-links a { color: rgba(255,255,255,0.5); font-size: 20px; transition: all 0.3s; }
        .social-links a:hover { color: #818cf8; }
        @media (max-width: 768px) { .navbar { flex-direction: column; gap: 15px; padding: 15px 20px; } .hero h1 { font-size: 36px; } .features-section, .about-section, .contact-section { padding: 60px 20px; } .section-title { font-size: 32px; } }
    </style>
</head>
<body>
    <div class="bg-animation"><div class="gradient"></div><div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div></div>
    <nav class="navbar">
        <div class="logo"><span class="logo-icon">📚</span><span class="logo-text">LibTech Solutions</span></div>
        <div class="nav-links"><a href="#home">Home</a><a href="#features">Features</a><a href="#about">About</a><a href="#contact">Contact</a></div>
    </nav>
    <section id="home" class="hero">
        <div class="hero-content">
            <div class="hero-badge"><i class="fas fa-rocket"></i> Next-Gen Library Management</div>
            <h1>Smart <span class="hero-gradient">Library Management</span> for the Digital Age</h1>
            <p>Manage books, members, borrowing, and fines all in one place. Built for modern libraries.</p>
            <div class="cta-buttons"><button class="btn-primary" onclick="openLoginModal()"><i class="fas fa-sign-in-alt"></i> Login Now</button><a href="#features" class="btn-secondary"><i class="fas fa-arrow-down"></i> Explore Features</a></div>
        </div>
    </section>
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <div class="close-modal" onclick="closeLoginModal()">&times;</div>
            <div class="modal-header"><h2><i class="fas fa-sign-in-alt"></i> Welcome Back</h2></div>
            <div class="modal-body">
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?php if($_GET['error']=='wrong_role'){if(isset($_GET['role']) && $_GET['role']=='member') echo '❌ This email belongs to a <strong>Librarian</strong>. Please select <strong>Librarian Login</strong>.'; elseif(isset($_GET['role']) && $_GET['role']=='librarian') echo '❌ This email belongs to a <strong>Member</strong>. Please select <strong>Member Login</strong>.'; else echo '❌ Invalid role.';} else echo '❌ Invalid email or password.'; ?></div>
                <?php endif; ?>
                <div class="role-selector"><div class="role-btn active" onclick="selectRole('member')">👤 Member Login</div><div class="role-btn" onclick="selectRole('librarian')">📚 Librarian Login</div></div>
                <form id="memberForm" class="login-form active" method="POST" action="auth/login-process.php"><input type="hidden" name="role" value="Member"><div class="input-group"><label><i class="fas fa-envelope"></i> Email Address</label><input type="email" name="email" placeholder="Enter your email" required></div><div class="input-group"><label><i class="fas fa-lock"></i> Password</label><input type="password" name="password" placeholder="Enter your password" required></div><button type="submit" class="login-submit">Login as Member <i class="fas fa-arrow-right"></i></button></form>
                <form id="librarianForm" class="login-form" method="POST" action="auth/login-process.php"><input type="hidden" name="role" value="Librarian"><div class="input-group"><label><i class="fas fa-envelope"></i> Librarian Email</label><input type="email" name="email" placeholder="Enter your email" required></div><div class="input-group"><label><i class="fas fa-lock"></i> Password</label><input type="password" name="password" placeholder="Enter your password" required></div><button type="submit" class="login-submit">Login as Librarian <i class="fas fa-arrow-right"></i></button></form>
            </div>
            <div class="modal-footer"><p>New to LibTech Solutions? <a href="register.php">Register Here <i class="fas fa-user-plus"></i></a></p><div class="demo-info"><i class="fas fa-flask"></i> Demo Credentials:<br><strong>Member:</strong> member@test.com / 1234<br><strong>Librarian:</strong> librarian@test.com / 1234</div></div>
        </div>
    </div>
    <section id="features" class="features-section">
        <h2 class="section-title">Powerful <span class="hero-gradient">Features</span></h2>
        <p class="section-subtitle">Everything you need to manage a modern library efficiently</p>
        <div class="features-grid">
            <div class="feature-card"><div class="feature-icon">📖</div><h3>Book Catalog</h3><p>Add, edit, delete, and search books. Track availability and manage inventory with ease.</p></div>
            <div class="feature-card"><div class="feature-icon">👥</div><h3>Member Management</h3><p>Register new members, manage profiles, block/unblock members, and track member history.</p></div>
            <div class="feature-card"><div class="feature-icon">🔄</div><h3>Borrow & Return</h3><p>Easy book checkout and return process with automatic due date calculation.</p></div>
            <div class="feature-card"><div class="feature-icon">💰</div><h3>Fine Calculation</h3><p>Automatic fine calculation at $0.50 per day for overdue books. Track payments.</p></div>
            <div class="feature-card"><div class="feature-icon">📧</div><h3>Email Notifications</h3><p>Send automatic notifications for borrow confirmations, returns, and overdue reminders.</p></div>
            <div class="feature-card"><div class="feature-icon">📊</div><h3>Reports & Analytics</h3><p>View overdue reports, popular books, and library statistics at a glance.</p></div>
        </div>
    </section>
    <section id="about" class="about-section">
        <div class="about-content"><h2>About <span class="hero-gradient">LibTech Solutions</span></h2><p>LibTech Solutions is a comprehensive web-based library management system designed to streamline library operations. Our system allows librarians to manage books, members, and borrowing activities efficiently while providing members with an easy way to search, borrow, and return books online.</p><p>The system automatically calculates fines at $0.50 per day for overdue returns and sends email notifications for borrow confirmations, return receipts, and overdue reminders.</p><div class="team-badge"><span><i class="fas fa-user-graduate"></i> Student Project</span><span><i class="fas fa-users"></i> Team of 5 Developers</span><span><i class="fas fa-code"></i> Built with PHP & MySQL</span></div></div>
        <div class="about-stats"><div class="stat-box"><div class="stat-number">500+</div><div>Books Managed</div></div><div class="stat-box"><div class="stat-number">100+</div><div>Active Members</div></div><div class="stat-box"><div class="stat-number">24/7</div><div>Online Access</div></div><div class="stat-box"><div class="stat-number">$0.50</div><div>Daily Fine Rate</div></div></div>
    </section>
    <section id="contact" class="contact-section">
        <div class="contact-container">
            <div class="contact-info"><h3>Get in <span class="hero-gradient">Touch</span></h3><div class="contact-detail"><div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div><div>Niels Brock College, Copenhagen, Denmark</div></div><div class="contact-detail"><div class="contact-icon"><i class="fas fa-envelope"></i></div><div>support@libtechsolutions.com</div></div><div class="contact-detail"><div class="contact-icon"><i class="fas fa-phone"></i></div><div>+45 1234 5678</div></div></div>
            <div class="contact-map"><h4><i class="fas fa-globe"></i> Our Location</h4><div class="map-placeholder"><i class="fas fa-map-pin" style="font-size:30px; color:#ec4899;"></i><p style="margin-top:10px;">📍 Niels Brock College<br>Copenhagen, Denmark</p></div></div>
        </div>
    </section>
    <footer class="footer"><div class="social-links"><a href="#"><i class="fab fa-facebook"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-linkedin"></i></a><a href="#"><i class="fab fa-github"></i></a></div><p>© 2026 LibTech Solutions | Built with <i class="fas fa-heart" style="color:#ec4899;"></i> by De Montfort University Students</p><p style="margin-top:10px; font-size:12px;">Library Management System for CTEC2713 Project</p></footer>
    <script>
        function openLoginModal() { document.getElementById('loginModal').classList.add('active'); }
        function closeLoginModal() { document.getElementById('loginModal').classList.remove('active'); }
        function selectRole(role) {
            var memberForm = document.getElementById('memberForm');
            var librarianForm = document.getElementById('librarianForm');
            var btns = document.querySelectorAll('.role-btn');
            if(role === 'member') { memberForm.classList.add('active'); librarianForm.classList.remove('active'); btns[0].classList.add('active'); btns[1].classList.remove('active'); }
            else { memberForm.classList.remove('active'); librarianForm.classList.add('active'); btns[0].classList.remove('active'); btns[1].classList.add('active'); }
        }
        window.onclick = function(event) { var modal = document.getElementById('loginModal'); if(event.target == modal) modal.classList.remove('active'); }
        document.querySelectorAll('a[href^="#"]').forEach(a => a.addEventListener('click', function(e) { e.preventDefault(); var t = document.querySelector(this.getAttribute('href')); if(t) t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }));
    </script>
</body>
</html>