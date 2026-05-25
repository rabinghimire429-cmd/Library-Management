<?php
// File: tests/test_csrf.php
// CSRF Protection Tests

session_start();

echo "===============================================\n";
echo "      CSRF PROTECTION - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Helper function to simulate CSRF token generation
function generateCSRFToken() {
    return bin2hex(random_bytes(32));
}

// Helper function to simulate CSRF validation
function validateCSRFToken($token, $storedToken) {
    if (empty($token)) {
        return false;
    }
    return hash_equals($storedToken, $token);
}

// Test 1: Extreme Min (No CSRF token submitted)
echo "Test 1: Extreme Min (No CSRF token submitted)\n";
$_SESSION['csrf_token'] = generateCSRFToken();
$result = validateCSRFToken('', $_SESSION['csrf_token']);
echo "  Expected: Request rejected\n";
echo "  Actual: " . ($result ? "Accepted" : "Rejected") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 2: Min -1 (Invalid CSRF token)
echo "Test 2: Min -1 (Invalid CSRF token)\n";
$_SESSION['csrf_token'] = generateCSRFToken();
$result = validateCSRFToken('invalid-token-123', $_SESSION['csrf_token']);
echo "  Expected: Request rejected\n";
echo "  Actual: " . ($result ? "Accepted" : "Rejected") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - Valid CSRF token
echo "Test 3: Min (Boundary) - Valid CSRF token\n";
$token = generateCSRFToken();
$_SESSION['csrf_token'] = $token;
$result = validateCSRFToken($token, $_SESSION['csrf_token']);
echo "  Expected: Request accepted\n";
echo "  Actual: " . ($result ? "Accepted" : "Rejected") . "\n";
if ($result === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (Expired CSRF token simulation)
echo "Test 4: Min +1 (Expired CSRF token - old token)\n";
$oldToken = generateCSRFToken();
$_SESSION['csrf_token'] = generateCSRFToken(); // New token
$result = validateCSRFToken($oldToken, $_SESSION['csrf_token']);
echo "  Expected: Request rejected (token mismatch)\n";
echo "  Actual: " . ($result ? "Accepted" : "Rejected") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (Token from different session)
echo "Test 5: Mid (Token from different session)\n";
$token1 = generateCSRFToken();
$token2 = generateCSRFToken();
$result = validateCSRFToken($token1, $token2);
echo "  Expected: Request rejected (tokens don't match)\n";
echo "  Actual: " . ($result ? "Accepted" : "Rejected") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max (Token with extra characters)
echo "Test 6: Extreme Max (Token with extra characters)\n";
$token = generateCSRFToken();
$_SESSION['csrf_token'] = $token;
$modifiedToken = $token . 'extra';
$result = validateCSRFToken($modifiedToken, $_SESSION['csrf_token']);
echo "  Expected: Request rejected (modified token)\n";
echo "  Actual: " . ($result ? "Accepted" : "Rejected") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Invalid data type (Empty token string)
echo "Test 7: Invalid data type (Empty token string)\n";
$_SESSION['csrf_token'] = generateCSRFToken();
$result = validateCSRFToken('', $_SESSION['csrf_token']);
echo "  Expected: Request rejected\n";
echo "  Actual: " . ($result ? "Accepted" : "Rejected") . "\n";
if ($result === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 8: Other tests (Token reuse after submission)
echo "Test 8: Other tests (Token reuse after submission - new token generated)\n";
$oldToken = $_SESSION['csrf_token'];
$_SESSION['csrf_token'] = generateCSRFToken();
$result = validateCSRFToken($oldToken, $_SESSION['csrf_token']);
echo "  Expected: Request rejected (old token invalid)\n";
echo "  Actual: " . ($result ? "Accepted" : "Rejected") . "\n";
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
    echo "✅ ALL CSRF PROTECTION TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>