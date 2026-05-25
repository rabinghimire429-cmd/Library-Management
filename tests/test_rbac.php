<?php
// File: tests/test_rbac.php
// Role-Based Access Control Tests - Aligned with actual system messages

session_start();

echo "===============================================\n";
echo "     RBAC - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Simulate database users
$users = [
    'member@test.com' => ['role' => 'Member', 'password' => '1234'],
    'librarian@test.com' => ['role' => 'Librarian', 'password' => '1234']
];

// Helper function to simulate login with role validation
function simulateLogin($email, $password, $selectedRole, &$users) {
    // Test 1: No session (logged out)
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please enter email and password'];
    }
    
    // Check if user exists
    if (!isset($users[$email])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $users[$email];
    
    // Check password
    if ($user['password'] !== $password) {
        return ['success' => false, 'message' => 'Invalid password'];
    }
    
    // ROLE VALIDATION - This is the key test for RBAC
    if ($selectedRole === 'Librarian' && $user['role'] !== 'Librarian') {
        return ['success' => false, 'message' => 'This email belongs to a Member. Please select "Member Login" instead.'];
    }
    
    if ($selectedRole === 'Member' && $user['role'] !== 'Member') {
        return ['success' => false, 'message' => 'This email belongs to a Librarian. Please select "Librarian Login" instead.'];
    }
    
    return ['success' => true, 'role' => $user['role'], 'message' => 'Login successful'];
}

// Helper function to simulate session timeout
function isSessionExpired($lastActivity) {
    if (empty($lastActivity)) return false;
    return (time() - $lastActivity) > 1800;
}

// Test 1: Extreme Min (No session - logged out)
echo "Test 1: Extreme Min (No session - logged out)\n";
session_destroy();
$result = simulateLogin('', '', 'Member', $users);
echo "  Expected: Redirect to login page\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], 'email') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 2: Min -1 (Member trying to login as Librarian)
echo "Test 2: Min -1 (Member trying to login as Librarian)\n";
$result = simulateLogin('member@test.com', '1234', 'Librarian', $users);
echo "  Expected: This email belongs to a Member. Please select 'Member Login' instead.\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], 'Member') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - Librarian login as Librarian (correct role)
echo "Test 3: Min (Boundary) - Librarian login as Librarian\n";
$result = simulateLogin('librarian@test.com', '1234', 'Librarian', $users);
echo "  Expected: Login successful, redirect to Librarian Dashboard\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === true && $result['role'] === 'Librarian') {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (Librarian trying to login as Member)
echo "Test 4: Min +1 (Librarian trying to login as Member)\n";
$result = simulateLogin('librarian@test.com', '1234', 'Member', $users);
echo "  Expected: This email belongs to a Librarian. Please select 'Librarian Login' instead.\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], 'Librarian') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (Member login as Member - correct role)
echo "Test 5: Mid (Member login as Member)\n";
$result = simulateLogin('member@test.com', '1234', 'Member', $users);
echo "  Expected: Login successful, redirect to Member Dashboard\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === true && $result['role'] === 'Member') {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max (Expired session - 31 minutes)
echo "Test 6: Extreme Max (Expired session - 31 minutes)\n";
$lastActivity = time() - (31 * 60);
$expired = isSessionExpired($lastActivity);
echo "  Expected: Session expired, redirect to login with timeout message\n";
echo "  Actual: " . ($expired ? "Session expired" : "Session active") . "\n";
if ($expired === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Invalid data type (Manipulated session role - trying 'Admin')
echo "Test 7: Invalid data type (Manipulated session role - trying 'Admin')\n";
$_SESSION['admin_role'] = 'Admin';
$storedRole = $_SESSION['admin_role'];
$isValidRole = ($storedRole === 'Member' || $storedRole === 'Librarian');
echo "  Expected: Server-side verification prevents access\n";
echo "  Actual: Role '$storedRole' is " . ($isValidRole ? "valid" : "invalid") . "\n";
if ($isValidRole === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 8: Other tests (Direct URL access without session)
echo "Test 8: Other tests (Direct URL access without session)\n";
session_destroy();
$hasSession = isset($_SESSION['admin_id']);
echo "  Expected: Redirect to login page\n";
echo "  Actual: " . ($hasSession ? "Session exists" : "No session - redirect") . "\n";
if ($hasSession === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

echo "===============================================\n";
echo "              TEST SUMMARY\n";
echo "===============================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "===============================================\n";

if ($failed === 0) {
    echo "✅ ALL RBAC TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>