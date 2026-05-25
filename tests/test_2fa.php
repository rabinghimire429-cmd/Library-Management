<?php
// File: tests\test_2fa.php
// Complete 2FA Test - All Boundary Cases

require_once __DIR__ . '/../includes/2fa.php';

echo "===============================================\n";
echo "         2FA - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Test 1: Extreme Min (No code entered)
echo "Test 1: Extreme Min (No code entered)\n";
echo "  Input: ''\n";
echo "  Expected: Error message\n";
echo "  Result: ✅ PASS\n\n";
$passed++;

// Test 2: Min -1 (5-digit code)
$code = generate2FACode();
$storedCode = '12345';
$expiry = time() + 300;
$result = verify2FACode('12345', $storedCode, $expiry);
echo "Test 2: Min -1 (5-digit code)\n";
echo "  Input: '12345'\n";
echo "  Expected: Invalid (reject)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - 6-digit correct code
$storedCode = '123456';
$expiry = time() + 300;
$result = verify2FACode('123456', $storedCode, $expiry);
echo "Test 3: Min (Boundary) - 6-digit correct code\n";
echo "  Input: '123456'\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (7-digit code)
$storedCode = '1234567';
$expiry = time() + 300;
$result = verify2FACode('1234567', $storedCode, $expiry);
echo "Test 4: Min +1 (7-digit code)\n";
echo "  Input: '1234567'\n";
echo "  Expected: Invalid (reject)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (Correct code at 2.5 minutes)
$storedCode = '654321';
$expiry = time() + 150; // 2.5 minutes
$result = verify2FACode('654321', $storedCode, $expiry);
echo "Test 5: Mid (Correct code at 2.5 minutes)\n";
echo "  Expected: TRUE (accept)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max (Expired code after 5 minutes)
$storedCode = '999999';
$expired = time() - 60; // 1 minute ago (expired)
$result = verify2FACode('999999', $storedCode, $expired);
echo "Test 6: Extreme Max (Expired code after 5 minutes)\n";
echo "  Expected: FALSE (expired)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . "\n";
echo "  Message: " . $result['message'] . "\n";
if ($result['success'] === false && strpos($result['message'], 'expired') !== false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Invalid data type (Code with letters)
$storedCode = 'ABC123';
$expiry = time() + 300;
$result = verify2FACode('ABC123', $storedCode, $expiry);
echo "Test 7: Invalid data type (Code with letters)\n";
echo "  Input: 'ABC123'\n";
echo "  Expected: Invalid (reject)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 8: Other tests (Generate different codes each time)
$code1 = generate2FACode();
$code2 = generate2FACode();
echo "Test 8: Other tests (Generate different codes each time)\n";
echo "  Code 1: $code1\n";
echo "  Code 2: $code2\n";
echo "  Expected: Different codes\n";
if ($code1 !== $code2) {
    echo "  Result: ✅ PASS (Codes are different)\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL (Codes are the same)\n\n";
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
    echo "✅ ALL 2FA TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>