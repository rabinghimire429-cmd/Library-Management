<?php
// File: tests/test_member_edit.php
// Member Edit/Update - Boundary Value Tests
// Developer: Nabil Sarwar | Module: Member Management

echo "===============================================\n";
echo "     MEMBER EDIT/UPDATE - TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Simulated existing member record
$existingMember = [
    'member_id'       => 1,
    'full_name'       => 'John Member',
    'email'           => 'member@test.com',
    'phone'           => '55501010',
    'membership_date' => '2026-01-01',
];

// Other members in DB (for duplicate email check)
$otherEmails = ['other@test.com', 'librarian@test.com'];

// Helper: simulate update validation
function simulateUpdateMember($memberId, $fullName, $email, $phone, $date, $otherEmails) {
    if ($memberId <= 0) {
        return ['success' => false, 'message' => 'Invalid member ID.'];
    }
    if (strlen(trim($fullName)) < 2) {
        return ['success' => false, 'message' => 'Full name must be at least 2 characters.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid email address is required.'];
    }
    if (!preg_match('/^\+?[\d\s\-]{7,15}$/', $phone)) {
        return ['success' => false, 'message' => 'Phone number must be 7-15 digits.'];
    }
    if (empty($date) || !strtotime($date)) {
        return ['success' => false, 'message' => 'A valid membership date is required.'];
    }
    if (in_array($email, $otherEmails)) {
        return ['success' => false, 'message' => 'Another member already uses this email.'];
    }
    return ['success' => true, 'message' => "Member \"$fullName\" updated successfully."];
}

// -----------------------------------------------
// Test 1: Extreme Min (Invalid member ID = 0)
// -----------------------------------------------
echo "Test 1: Extreme Min (Invalid member ID = 0)\n";
$result = simulateUpdateMember(0, 'John Member', 'member@test.com', '55501010', '2026-01-01', $otherEmails);
echo "  Input: member_id=0\n";
echo "  Expected: FALSE (reject - invalid ID)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 2: Min -1 (Name too short - 1 character)
// -----------------------------------------------
echo "Test 2: Min -1 (Name too short - 1 character)\n";
$result = simulateUpdateMember(1, 'J', 'member@test.com', '55501010', '2026-01-01', $otherEmails);
echo "  Input: full_name='J'\n";
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
// Test 3: Min Boundary (Valid update - all correct)
// -----------------------------------------------
echo "Test 3: Min Boundary (Valid update - all fields correct)\n";
$result = simulateUpdateMember(1, 'John Member', 'member@test.com', '55501010', '2026-01-01', $otherEmails);
echo "  Input: All valid fields\n";
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
// Test 4: Min +1 (Update name to longer valid name)
// -----------------------------------------------
echo "Test 4: Min +1 (Update name to longer valid name)\n";
$result = simulateUpdateMember(1, 'Jonathan Member Smith', 'member@test.com', '55501010', '2026-01-01', $otherEmails);
echo "  Input: full_name='Jonathan Member Smith'\n";
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
// Test 5: Mid (Update phone number)
// -----------------------------------------------
echo "Test 5: Mid (Update with new valid phone number)\n";
$result = simulateUpdateMember(1, 'John Member', 'member@test.com', '+45 9876 5432', '2026-01-01', $otherEmails);
echo "  Input: phone='+45 9876 5432'\n";
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
// Test 6: Extreme Max (Email taken by another member)
// -----------------------------------------------
echo "Test 6: Extreme Max (Email already used by another member)\n";
$result = simulateUpdateMember(1, 'John Member', 'other@test.com', '55501010', '2026-01-01', $otherEmails);
echo "  Input: email='other@test.com' (used by another member)\n";
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
// Test 7: Invalid data type (Invalid date format)
// -----------------------------------------------
echo "Test 7: Invalid data type (Invalid date format)\n";
$result = simulateUpdateMember(1, 'John Member', 'member@test.com', '55501010', 'not-a-date', $otherEmails);
echo "  Input: membership_date='not-a-date'\n";
echo "  Expected: FALSE (reject - invalid date)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 8: Other (Keep same email - should pass)
// -----------------------------------------------
echo "Test 8: Other (Member keeps their own email - should be allowed)\n";
$ownEmail  = 'member@test.com';
$checkList = array_diff($otherEmails, [$ownEmail]); // exclude own email from check
$result    = simulateUpdateMember(1, 'John Member', $ownEmail, '55501010', '2026-01-01', $checkList);
echo "  Input: email='member@test.com' (own existing email)\n";
echo "  Expected: TRUE (accept - keeping own email)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === true) {
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
echo "Passed:      $passed\n";
echo "Failed:      $failed\n";
echo "===============================================\n";
if ($failed === 0) {
    echo "✅ ALL MEMBER EDIT/UPDATE TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>
