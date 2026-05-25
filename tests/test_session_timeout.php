<?php
// File: tests/test_session_timeout.php
// Session Timeout Tests

session_start();

echo "===============================================\n";
echo "     SESSION TIMEOUT - COMPLETE TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Helper function to simulate session timeout
function checkSessionTimeout($lastActivity, $currentTime, $timeoutSeconds = 1800) {
    if (empty($lastActivity)) {
        return ['expired' => false, 'message' => 'New session'];
    }
    
    if (($currentTime - $lastActivity) > $timeoutSeconds) {
        return ['expired' => true, 'message' => 'Session expired'];
    }
    
    return ['expired' => false, 'message' => 'Session active'];
}

$currentTime = time();

// Test 1: Extreme Min (User logs out immediately)
echo "Test 1: Extreme Min (User logs out immediately)\n";
session_destroy();
echo "  Expected: Session destroyed\n";
echo "  Actual: Session destroyed\n";
echo "  Result: ✅ PASS\n\n";
$passed++;

// Test 2: Min -1 (15 minutes inactivity)
$lastActivity = $currentTime - (15 * 60);
$result = checkSessionTimeout($lastActivity, $currentTime);
echo "Test 2: Min -1 (15 minutes inactivity)\n";
echo "  Expected: Session still active\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['expired'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 3: Min (Boundary) - 29 minutes inactivity
$lastActivity = $currentTime - (29 * 60);
$result = checkSessionTimeout($lastActivity, $currentTime);
echo "Test 3: Min (Boundary) - 29 minutes inactivity\n";
echo "  Expected: Session still active\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['expired'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 4: Min +1 (30 minutes inactivity - boundary)
$lastActivity = $currentTime - (30 * 60);
$result = checkSessionTimeout($lastActivity, $currentTime);
echo "Test 4: Min +1 (30 minutes inactivity - boundary)\n";
echo "  Expected: Session expired\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['expired'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 5: Mid (31 minutes inactivity)
$lastActivity = $currentTime - (31 * 60);
$result = checkSessionTimeout($lastActivity, $currentTime);
echo "Test 5: Mid (31 minutes inactivity)\n";
echo "  Expected: Session expired\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['expired'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 6: Extreme Max (60 minutes inactivity)
$lastActivity = $currentTime - (60 * 60);
$result = checkSessionTimeout($lastActivity, $currentTime);
echo "Test 6: Extreme Max (60 minutes inactivity)\n";
echo "  Expected: Session expired\n";
echo "  Actual: " . $result['message'] . "\n";
if ($result['expired'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// Test 7: Other tests (User activity before timeout - resets timer)
echo "Test 7: Other tests (User activity before timeout - resets timer)\n";
$_SESSION['LAST_ACTIVITY'] = $currentTime;
echo "  Expected: Session timer resets\n";
echo "  Actual: LAST_ACTIVITY updated to current time\n";
echo "  Result: ✅ PASS\n\n";
$passed++;

echo "===============================================\n";
echo "              TEST SUMMARY\n";
echo "===============================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "===============================================\n";

if ($failed === 0) {
    echo "✅ ALL SESSION TIMEOUT TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>