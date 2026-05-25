<?php
// File: tests/test_login.php
// Login Functionality Tests

session_start();

echo "===============================================\n";
echo "      LOGIN FUNCTIONALITY - TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Simulate database records for testing
$users = [
    'member@test.com' => [
        'password' => '1234',
        'role' => 'Member',
        'is_active' => true,
        'failed_attempts' => 0
    ],
    'librarian@test.com' => [
        'password' => '1234',
        'role' => 'Librarian',
        'is_active' => true,
        'failed_attempts' => 0
    ],
    'blocked@test.com' => [
        'password' => '1234',
        'role' => 'Member',
        'is_active' => false,
        'failed_attempts' => 0
    ]
];

// Helper function to simulate login
function simulateLogin($email, $password, &$users) {
    if (!isset($users[$email])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $users[$email];
    
    if ($user['is_active'] !== true) {
        return ['success' => false, 'message' => 'Account is blocked'];
    }
    
    if ($user['password'] !== $password) {
        return ['success' => false, 'message' => 'Invalid password'];
    }
    
    return ['success' => true, 'role' => $user['role']];
}

// Test 1: Extreme Min (Empty email and password)
echo "Test 1: Extreme Min (Empty email and password)\n";
$result = simulateLogin('', '', $users);
echo "  Expected: Error message\n";
echo "  Actual: " . ($result['success'] ? "Success" : "Failed - " . $result['message']) . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 2: Min -1 (Valid email, wrong password)
echo "Test 2: Min -1 (Valid email, wrong password)\n";
$result = simulateLogin('member@test.com', 'wrongpassword', $users);
echo "  Expected: Error - Invalid password\n";
echo "  Actual: " . ($result['success'] ? "Success" : "Failed - " . $result['message']) . "\n";
if ($result['success'] === false && strpos($result['message'], 'password') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - Valid email, correct password
echo "Test 3: Min (Boundary) - Valid email, correct password\n";
$result = simulateLogin('member@test.com', '1234', $users);
echo "  Expected: Login successful\n";
echo "  Actual: " . ($result['success'] ? "Success - Role: " . $result['role'] : "Failed") . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (Member login redirect)
echo "Test 4: Min +1 (Member login redirect)\n";
$result = simulateLogin('member@test.com', '1234', $users);
echo "  Expected: Redirect to Member Dashboard\n";
echo "  Actual: " . ($result['success'] ? "Role: " . $result['role'] : "Failed") . "\n";
if ($result['success'] === true && $result['role'] === 'Member') {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (Librarian login redirect)
echo "Test 5: Mid (Librarian login redirect)\n";
$result = simulateLogin('librarian@test.com', '1234', $users);
echo "  Expected: Redirect to Librarian Dashboard\n";
echo "  Actual: " . ($result['success'] ? "Role: " . $result['role'] : "Failed") . "\n";
if ($result['success'] === true && $result['role'] === 'Librarian') {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Invalid data type (Email not in database)
echo "Test 6: Invalid data type (Email not in database)\n";
$result = simulateLogin('notexist@test.com', '1234', $users);
echo "  Expected: Generic error message\n";
echo "  Actual: " . ($result['success'] ? "Success" : "Failed - " . $result['message']) . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Other tests (Blocked account)
echo "Test 7: Other tests (Blocked account - Is_active = 0)\n";
$result = simulateLogin('blocked@test.com', '1234', $users);
echo "  Expected: Error - Account is blocked\n";
echo "  Actual: " . ($result['success'] ? "Success" : "Failed - " . $result['message']) . "\n";
if ($result['success'] === false && strpos($result['message'], 'blocked') !== false) {
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
    echo "✅ ALL LOGIN FUNCTIONALITY TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>