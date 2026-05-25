<?php
// File: tests\test_email.php
// Complete Email Validation Test - All Boundary Cases

require_once __DIR__ . '/../includes/validation.php';

echo "===============================================\n";
echo "     EMAIL VALIDATION - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Test 1: Extreme Min (Empty string)
$result = validateEmail('');
echo "Test 1: Extreme Min (Empty string)\n";
echo "  Input: ''\n";
echo "  Expected: FALSE (reject)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 2: Min -1 (Invalid email - no dot)
$result = validateEmail('user@test');
echo "Test 2: Min -1 (Invalid email - missing dot)\n";
echo "  Input: 'user@test'\n";
echo "  Expected: FALSE (reject)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - Minimum valid email
$result = validateEmail('a@b.c');
echo "Test 3: Min (Boundary) - Minimum valid email\n";
echo "  Input: 'a@b.c'\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (Normal valid email)
$result = validateEmail('user@domain.com');
echo "Test 4: Min +1 (Normal valid email)\n";
echo "  Input: 'user@domain.com'\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (Another valid email)
$result = validateEmail('member@library.com');
echo "Test 5: Mid (Another valid email)\n";
echo "  Input: 'member@library.com'\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max (Very long email - 100+ chars)
$longEmail = str_repeat('a', 100) . '@test.com';
$result = validateEmail($longEmail);
echo "Test 6: Extreme Max (Very long email - 100+ chars)\n";
echo "  Input: '{$longEmail}'\n";
echo "  Expected: FALSE (reject - too long/invalid format)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Invalid data type (No @ symbol)
$result = validateEmail('notanemail');
echo "Test 7: Invalid data type (No @ symbol)\n";
echo "  Input: 'notanemail'\n";
echo "  Expected: FALSE (reject)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 8: Other tests (SQL injection attempt)
$sqlInjection = "' OR '1'='1";
$result = validateEmail($sqlInjection);
echo "Test 8: Other tests (SQL injection attempt)\n";
echo "  Input: '{$sqlInjection}'\n";
echo "  Expected: FALSE (reject - invalid email)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === false) {
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
    echo "✅ ALL EMAIL VALIDATION TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>