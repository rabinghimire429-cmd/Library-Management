<?php
// File: tests/test_member_add.php
// Member Add Functionality - Boundary Value Tests
// Developer: Nabil Sarwar | Module: Member Management

echo "===============================================\n";
echo "     MEMBER ADD - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Simulate existing members in database
$existingEmails = ['existing@test.com'];

// Helper function to simulate adding a member (mirrors server-side validation)
function simulateAddMember($fullName, $email, $phone, $membershipDate, &$existingEmails) {
    // Full name validation - minimum 2 characters
    if (strlen(trim($fullName)) < 2) {
        return ['success' => false, 'message' => 'Full name must be at least 2 characters.'];
    }

    // Email validation - must be valid format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid email address is required.'];
    }

    // Phone validation - 7 to 15 digits, optional leading +
    if (!preg_match('/^\+?[\d\s\-]{7,15}$/', $phone)) {
        return ['success' => false, 'message' => 'Phone number must be 7-15 digits.'];
    }

    // Membership date validation - must be provided and valid
    if (empty($membershipDate) || !strtotime($membershipDate)) {
        return ['success' => false, 'message' => 'A valid membership date is required.'];
    }

    // Duplicate email check
    if (in_array($email, $existingEmails)) {
        return ['success' => false, 'message' => 'A member with this email already exists.'];
    }

    // Success - add to existing emails
    $existingEmails[] = $email;
    return ['success' => true, 'message' => 'Member added successfully.'];
}

// -----------------------------------------------
// Test 1: Extreme Min (All fields empty)
// -----------------------------------------------
echo "Test 1: Extreme Min (All fields empty)\n";
$result = simulateAddMember('', '', '', '', $existingEmails);
echo "  Input: name='', email='', phone='', date=''\n";
echo "  Expected: FALSE (reject)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 2: Min -1 (Name with only 1 character)
// -----------------------------------------------
echo "Test 2: Min -1 (Name too short - 1 character)\n";
$result = simulateAddMember('A', 'test@test.com', '12345678', '2026-01-01', $existingEmails);
echo "  Input: name='A'\n";
echo "  Expected: FALSE (reject - name too short)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 3: Min Boundary (Name with exactly 2 characters)
// -----------------------------------------------
echo "Test 3: Min Boundary (Name with exactly 2 characters)\n";
$result = simulateAddMember('Jo', 'jo@test.com', '12345678', '2026-01-01', $existingEmails);
echo "  Input: name='Jo'\n";
echo "  Expected: TRUE (accept - 2 chars is minimum)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 4: Min +1 (Phone with 6 digits - below minimum)
// -----------------------------------------------
echo "Test 4: Min -1 (Phone too short - 6 digits)\n";
$result = simulateAddMember('Jane Doe', 'jane@test.com', '123456', '2026-01-01', $existingEmails);
echo "  Input: phone='123456' (6 digits)\n";
echo "  Expected: FALSE (reject - phone below 7 digit minimum)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 5: Mid (Valid member - all fields correct)
// -----------------------------------------------
echo "Test 5: Mid (Valid member - all fields correct)\n";
$result = simulateAddMember('Jane Doe', 'janedoe@test.com', '+45 1234 5678', '2026-05-01', $existingEmails);
echo "  Input: name='Jane Doe', email='janedoe@test.com', phone='+45 1234 5678'\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 6: Extreme Max (Phone with 16 digits - above maximum)
// -----------------------------------------------
echo "Test 6: Extreme Max (Phone too long - 16 digits)\n";
$result = simulateAddMember('John Smith', 'john@test.com', '1234567890123456', '2026-01-01', $existingEmails);
echo "  Input: phone='1234567890123456' (16 digits)\n";
echo "  Expected: FALSE (reject - phone above 15 digit maximum)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 7: Invalid data type (Invalid email format)
// -----------------------------------------------
echo "Test 7: Invalid data type (Invalid email format)\n";
$result = simulateAddMember('Bob Brown', 'notanemail', '12345678', '2026-01-01', $existingEmails);
echo "  Input: email='notanemail'\n";
echo "  Expected: FALSE (reject - invalid email)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 8: Other (Duplicate email)
// -----------------------------------------------
echo "Test 8: Other (Duplicate email - already exists)\n";
$result = simulateAddMember('Another User', 'existing@test.com', '12345678', '2026-01-01', $existingEmails);
echo "  Input: email='existing@test.com' (already in database)\n";
echo "  Expected: FALSE (reject - duplicate email)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// SUMMARY
// -----------------------------------------------
echo "===============================================\n";
echo "              TEST SUMMARY\n";
echo "===============================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed:      $passed\n";
echo "Failed:      $failed\n";
echo "===============================================\n";
if ($failed === 0) {
    echo "✅ ALL MEMBER ADD TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>
