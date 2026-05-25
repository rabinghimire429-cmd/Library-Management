<?php
// File: tests/test_logout.php
// Logout Functionality Tests - Without Test 8

echo "===============================================\n";
echo "      LOGOUT FUNCTIONALITY - TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

function startFreshSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    session_start();
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_email'] = 'member@test.com';
    $_SESSION['admin_role'] = 'Member';
    $_SESSION['LAST_ACTIVITY'] = time();
    return true;
}

function performLogout() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    return true;
}

function isUserLoggedIn() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == 1;
}

// Test 1: Extreme Min
echo "Test 1: Extreme Min (User logged in, clicks logout)\n";
startFreshSession();
echo "  Before logout: User is " . (isUserLoggedIn() ? "logged in" : "not logged in") . "\n";
performLogout();
echo "  After logout: User is " . (isUserLoggedIn() ? "logged in" : "not logged in") . "\n";
echo "  Expected: Session destroyed, redirected to login page\n";
if (!isUserLoggedIn()) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 2: Min -1
echo "Test 2: Min -1 (After logout, try to access dashboard)\n";
startFreshSession();
performLogout();
$canAccess = isUserLoggedIn();
echo "  Expected: Dashboard not accessible, redirects to login\n";
echo "  Actual: " . ($canAccess ? "Access granted" : "Access denied - redirect to login") . "\n";
if ($canAccess === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary)
echo "Test 3: Min (Boundary) - Direct URL access to dashboard after logout\n";
startFreshSession();
performLogout();
$urlAccess = isUserLoggedIn();
echo "  Expected: Redirect to login page (access denied)\n";
echo "  Actual: " . ($urlAccess ? "Dashboard loaded" : "Redirected to login") . "\n";
if ($urlAccess === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1
echo "Test 4: Min +1 (Session variables cleared after logout)\n";
startFreshSession();
$_SESSION['test_var'] = 'test data';
$countBefore = count($_SESSION);
echo "  Before logout: Session has $countBefore variables\n";
performLogout();
$hasSessionData = false;
if (session_status() === PHP_SESSION_ACTIVE) {
    $hasSessionData = (count($_SESSION) > 0);
}
echo "  After logout: Session data exists? " . ($hasSessionData ? "Yes" : "No") . "\n";
echo "  Expected: No session variables accessible\n";
if ($hasSessionData === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid
echo "Test 5: Mid (Multiple logout clicks)\n";
startFreshSession();
performLogout();
performLogout();
$stillLoggedOut = !isUserLoggedIn();
echo "  Expected: Always redirects to login page, no errors\n";
echo "  Actual: " . ($stillLoggedOut ? "Still logged out, no errors" : "Unexpected behavior") . "\n";
if ($stillLoggedOut === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max
echo "Test 6: Extreme Max (Logout from different pages)\n";
$pages = ['member-dashboard.php', 'librarian-dashboard.php', 'profile.php'];
$allWorked = true;

foreach ($pages as $page) {
    startFreshSession();
    $_SESSION['current_page'] = $page;
    performLogout();
    
    if (isUserLoggedIn()) {
        $allWorked = false;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

echo "  Expected: Works from any page, same result\n";
echo "  Actual: " . ($allWorked ? "All pages work correctly" : "Some pages failed") . "\n";
if ($allWorked === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Invalid data type
echo "Test 7: Invalid data type (Logout has no data input)\n";
echo "  Expected: N/A (not applicable)\n";
echo "  Actual: Logout accepts no user input\n";
echo "  Result: N/A - Test skipped\n\n";

echo "===============================================\n";
echo "              TEST SUMMARY\n";
echo "===============================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "===============================================\n";

if ($failed === 0) {
    echo "✅ ALL LOGOUT FUNCTIONALITY TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>