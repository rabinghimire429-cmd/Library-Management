<?php
// File: tests/test_account_lockout.php
// Account Lockout Tests

echo "===============================================\n";
echo "      ACCOUNT LOCKOUT - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Simulate user account
$account = [
    'failed_attempts' => 0,
    'locked_until' => null
];

// Helper function to simulate failed login
function simulateFailedLogin(&$account) {
    $account['failed_attempts']++;
    
    if ($account['failed_attempts'] >= 5) {
        $account['locked_until'] = time() + (15 * 60); // Lock for 15 minutes
        return ['success' => false, 'message' => 'Account locked for 15 minutes'];
    }
    
    $remaining = 5 - $account['failed_attempts'];
    return ['success' => false, 'message' => "Invalid password. $remaining attempt(s) remaining"];
}

// Helper function to simulate successful login
function simulateSuccessfulLogin(&$account) {
    $account['failed_attempts'] = 0;
    $account['locked_until'] = null;
    return ['success' => true, 'message' => 'Login successful'];
}

// Helper function to check lockout status
function isLocked($account) {
    if ($account['locked_until'] !== null && $account['locked_until'] > time()) {
        $remaining = ceil(($account['locked_until'] - time()) / 60);
        return ['locked' => true, 'remaining' => $remaining];
    }
    return ['locked' => false, 'remaining' => 0];
}

// Test 1: Extreme Min (0 failed attempts)
echo "Test 1: Extreme Min (0 failed attempts)\n";
$status = isLocked($account);
echo "  Expected: Account active\n";
echo "  Actual: " . ($status['locked'] ? "Locked" : "Active") . "\n";
if ($status['locked'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 2: Min -1 (4 failed attempts)
echo "Test 2: Min -1 (4 failed attempts)\n";
$account = ['failed_attempts' => 0, 'locked_until' => null];
for ($i = 1; $i <= 4; $i++) {
    $result = simulateFailedLogin($account);
}
echo "  Expected: Account active, 1 attempt remaining\n";
echo "  Actual: " . $result['message'] . "\n";
echo "  Failed attempts: " . $account['failed_attempts'] . "\n";
if ($account['failed_attempts'] === 4 && $status['locked'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - 5 failed attempts (lockout)
echo "Test 3: Min (Boundary) - 5 failed attempts\n";
$result = simulateFailedLogin($account);
echo "  Expected: Account locked for 15 minutes\n";
echo "  Actual: " . $result['message'] . "\n";
if ($account['failed_attempts'] === 5 && $account['locked_until'] !== null) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (6 failed attempts - remains locked)
echo "Test 4: Min +1 (6 failed attempts - remains locked)\n";
$result = simulateFailedLogin($account);
$status = isLocked($account);
echo "  Expected: Account remains locked\n";
echo "  Actual: " . ($status['locked'] ? "Locked" : "Active") . "\n";
if ($status['locked'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (Attempt during lockout - 7 minutes)
echo "Test 5: Mid (Attempt during lockout - 7 minutes)\n";
// Set lockout to expire in 7 minutes
$account['locked_until'] = time() + (7 * 60);
$status = isLocked($account);
echo "  Expected: Error with remaining time (7 minutes)\n";
echo "  Actual: " . ($status['locked'] ? "Locked, {$status['remaining']} minutes remaining" : "Not locked") . "\n";
if ($status['locked'] === true && $status['remaining'] <= 7) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max (Attempt at 14 minutes 59 seconds)
echo "Test 6: Extreme Max (Attempt at 14 minutes 59 seconds)\n";
$account['locked_until'] = time() + (15 * 60) - 1;
$status = isLocked($account);
echo "  Expected: Still locked\n";
echo "  Actual: " . ($status['locked'] ? "Locked" : "Not locked") . "\n";
if ($status['locked'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Invalid data type (Attempt at exactly 15 minutes)
echo "Test 7: Invalid data type (Attempt at exactly 15 minutes)\n";
$account['locked_until'] = time() - 1;
$status = isLocked($account);
echo "  Expected: Account unlocked\n";
echo "  Actual: " . ($status['locked'] ? "Locked" : "Unlocked") . "\n";
if ($status['locked'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 8: Other tests (Successful login after lockout resets counter)
echo "Test 8: Other tests (Successful login after lockout resets counter)\n";
$account['failed_attempts'] = 5;
$account['locked_until'] = null;
$result = simulateSuccessfulLogin($account);
echo "  Expected: failed_attempts reset to 0\n";
echo "  Actual: failed_attempts = " . $account['failed_attempts'] . "\n";
if ($account['failed_attempts'] === 0) {
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
    echo "✅ ALL ACCOUNT LOCKOUT TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>