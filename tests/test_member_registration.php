<?php
// File: tests/test_member_registration.php
// Member Self-Registration - Boundary Value Tests
// Developer: Nabil Sarwar | Module: Member Management

echo "===============================================\n";
echo "  MEMBER SELF-REGISTRATION - TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

$existingEmails = ['existing@test.com', 'member@test.com'];

// Helper: simulate registration validation
function simulateMemberRegistration($fullName, $email, $phone, $password, &$existingEmails) {
    if (strlen(trim($fullName)) < 2) {
        return ['success' => false, 'message' => 'Full name must be at least 2 characters.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid email address is required.'];
    }
    if (!preg_match('/^\+?[\d\s\-]{7,15}$/', $phone)) {
        return ['success' => false, 'message' => 'Phone number must be 7-15 digits.'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }
    if (in_array($email, $existingEmails)) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }
    $existingEmails[] = $email;
    return ['success' => true, 'message' => 'Welcome! Your account has been created.'];
}

// Test 1: Extreme Min (All empty)
echo "Test 1: Extreme Min (All fields empty)\n";
$result = simulateMemberRegistration('', '', '', '', $existingEmails);
echo "  Expected: FALSE\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
echo "  Result: " . ($result['success'] === false ? "✅ PASS" : "❌ FAIL") . "\n\n";
$result['success'] === false ? $passed++ : $failed++;

// Test 2: Min -1 (Password 5 chars - below minimum)
echo "Test 2: Min -1 (Password too short - 5 characters)\n";
$result = simulateMemberRegistration('Jane Doe', 'jane@test.com', '12345678', '12345', $existingEmails);
echo "  Input: password='12345' (5 chars)\n";
echo "  Expected: FALSE (reject - minimum is 6)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
echo "  Result: " . ($result['success'] === false ? "✅ PASS" : "❌ FAIL") . "\n\n";
$result['success'] === false ? $passed++ : $failed++;

// Test 3: Min Boundary (Password exactly 6 chars)
echo "Test 3: Min Boundary (Password exactly 6 characters)\n";
$result = simulateMemberRegistration('Jane Doe', 'jane@test.com', '12345678', '123456', $existingEmails);
echo "  Input: password='123456' (6 chars)\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
echo "  Result: " . ($result['success'] === true ? "✅ PASS" : "❌ FAIL") . "\n\n";
$result['success'] === true ? $passed++ : $failed++;

// Test 4: Min +1 (Valid full registration)
echo "Test 4: Min +1 (Full valid registration)\n";
$result = simulateMemberRegistration('Bob Smith', 'bob@test.com', '+45 1234 5678', 'password123', $existingEmails);
echo "  Input: All valid fields\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
echo "  Result: " . ($result['success'] === true ? "✅ PASS" : "❌ FAIL") . "\n\n";
$result['success'] === true ? $passed++ : $failed++;

// Test 5: Mid (Duplicate email)
echo "Test 5: Mid (Duplicate email - already registered)\n";
$result = simulateMemberRegistration('Copy Cat', 'existing@test.com', '12345678', 'password123', $existingEmails);
echo "  Input: email='existing@test.com' (already exists)\n";
echo "  Expected: FALSE (reject - duplicate)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
echo "  Result: " . ($result['success'] === false ? "✅ PASS" : "❌ FAIL") . "\n\n";
$result['success'] === false ? $passed++ : $failed++;

// Test 6: Extreme Max (Very long valid password)
echo "Test 6: Extreme Max (Very long password - 50 characters)\n";
$longPwd = str_repeat('a', 50);
$result = simulateMemberRegistration('Alice Long', 'alice@test.com', '12345678', $longPwd, $existingEmails);
echo "  Input: password=50 chars\n";
echo "  Expected: TRUE (accept - no max limit)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
echo "  Result: " . ($result['success'] === true ? "✅ PASS" : "❌ FAIL") . "\n\n";
$result['success'] === true ? $passed++ : $failed++;

// Test 7: Invalid data type (Phone with letters)
echo "Test 7: Invalid data type (Phone number contains letters)\n";
$result = simulateMemberRegistration('Test User', 'testuser@test.com', 'abcdefgh', 'password123', $existingEmails);
echo "  Input: phone='abcdefgh'\n";
echo "  Expected: FALSE (reject - letters in phone)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
echo "  Result: " . ($result['success'] === false ? "✅ PASS" : "❌ FAIL") . "\n\n";
$result['success'] === false ? $passed++ : $failed++;

// Test 8: Other (SQL injection attempt in name)
echo "Test 8: Other (SQL injection attempt in name field)\n";
$result = simulateMemberRegistration("'; DROP TABLE member;--", 'sql@test.com', '12345678', 'password123', $existingEmails);
echo "  Input: full_name=\"'; DROP TABLE member;--\"\n";
echo "  Expected: TRUE (validation passes - DB layer uses prepared statements)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
echo "  Result: " . ($result['success'] === true ? "✅ PASS" : "❌ FAIL") . "\n\n";
$result['success'] === true ? $passed++ : $failed++;

echo "===============================================\n";
echo "              TEST SUMMARY\n";
echo "===============================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed:      $passed\n";
echo "Failed:      $failed\n";
echo "===============================================\n";
if ($failed === 0) {
    echo "✅ ALL MEMBER REGISTRATION TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>
