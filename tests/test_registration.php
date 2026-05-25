<?php
// File: tests/test_registration.php
// User Registration Tests

echo "===============================================\n";
echo "     USER REGISTRATION - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Simulate existing users in database
$existingUsers = [
    'existing@test.com'
];

// Helper function to simulate registration
function simulateRegistration($email, $password, $confirmPassword, $fullName, $phone, &$existingUsers) {
    // Test 1: Empty fields
    if (empty($email) || empty($password) || empty($confirmPassword) || empty($fullName)) {
        return ['success' => false, 'message' => 'All fields required'];
    }
    
    // Test 2: Email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Test 3: Password match
    if ($password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    // Test 4: Password length
    if (strlen($password) < 4) {
        return ['success' => false, 'message' => 'Password must be at least 4 characters'];
    }
    
    // Test 5: Duplicate email
    if (in_array($email, $existingUsers)) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    return ['success' => true, 'message' => 'Registration successful'];
}

// Test 1: Extreme Min (Empty all fields)
echo "Test 1: Extreme Min (Empty all fields)\n";
$result = simulateRegistration('', '', '', '', '', $existingUsers);
echo "  Expected: Error - All fields required\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], 'required') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 2: Min -1 (Invalid email format)
echo "Test 2: Min -1 (Invalid email format)\n";
$result = simulateRegistration('invalid-email', '1234', '1234', 'Test User', '12345678', $existingUsers);
echo "  Expected: Error - Invalid email format\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], 'email') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - Valid registration (4 chars password)
echo "Test 3: Min (Boundary) - Valid registration (4 chars password)\n";
$result = simulateRegistration('newuser@test.com', '1234', '1234', 'New User', '12345678', $existingUsers);
echo "  Expected: Registration successful\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
    // Add to existing users for next test
    $existingUsers[] = 'newuser@test.com';
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (Valid registration with long password)
echo "Test 4: Min +1 (Valid registration with long password)\n";
$result = simulateRegistration('newuser2@test.com', '1234567890', '1234567890', 'New User 2', '12345678', $existingUsers);
echo "  Expected: Registration successful\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (Duplicate email)
echo "Test 5: Mid (Duplicate email)\n";
$result = simulateRegistration('existing@test.com', '1234', '1234', 'Test User', '12345678', $existingUsers);
echo "  Expected: Error - Email already exists\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], 'already exists') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max (Password mismatch)
echo "Test 6: Extreme Max (Password mismatch)\n";
$result = simulateRegistration('test@test.com', '1234', '5678', 'Test User', '12345678', $existingUsers);
echo "  Expected: Error - Passwords do not match\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], 'match') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Invalid data type (Weak password - but 4 chars is minimum allowed)
echo "Test 7: Invalid data type (Weak password - 4 chars minimum)\n";
$result = simulateRegistration('weak@test.com', '1234', '1234', 'Test User', '12345678', $existingUsers);
echo "  Expected: Registration successful (minimum 4 chars allowed)\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 8: Other tests (Password too short - 3 chars)
echo "Test 8: Other tests (Password too short - 3 chars)\n";
$result = simulateRegistration('short@test.com', '123', '123', 'Test User', '12345678', $existingUsers);
echo "  Expected: Error - Password must be at least 4 characters\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], '4 characters') !== false) {
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
    echo "✅ ALL USER REGISTRATION TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>