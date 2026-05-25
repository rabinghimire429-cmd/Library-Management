<?php
// File: tests\test_password.php
// Complete Password Validation Test - All Boundary Cases

require_once __DIR__ . '/../includes/validation.php';

echo "===============================================\n";
echo "   PASSWORD VALIDATION - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Test 1: Extreme Min (Empty string)
$result = validatePassword('');
echo "Test 1: Extreme Min (Empty string)\n";
echo "  Expected: FALSE (reject)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 2: Min -1 (3 characters - below boundary)
$result = validatePassword('123');
echo "Test 2: Min -1 (3 characters - below boundary)\n";
echo "  Expected: FALSE (reject)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - 4 characters
$result = validatePassword('1234');
echo "Test 3: Min (Boundary) - 4 characters\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (5 characters)
$result = validatePassword('12345');
echo "Test 4: Min +1 (5 characters)\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (11 characters)
$result = validatePassword('password123');
echo "Test 5: Mid (11 characters)\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max (100 characters)
$longPassword = str_repeat('a', 100);
$result = validatePassword($longPassword);
echo "Test 6: Extreme Max (100 characters)\n";
echo "  Expected: TRUE (accept - no max limit)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Invalid data type (Special characters)
$result = validatePassword('P@ssw0rd!');
echo "Test 7: Invalid data type (Special characters)\n";
echo "  Expected: TRUE (accept - special chars allowed)\n";
echo "  Actual: " . ($result ? "TRUE" : "FALSE") . "\n";
if ($result === true) {
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
    echo "✅ ALL PASSWORD VALIDATION TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>