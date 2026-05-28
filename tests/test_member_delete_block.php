<?php
// File: tests/test_member_delete_block.php
// Member Delete & Block/Unblock - Boundary Value Tests
// Developer: Nabil Sarwar | Module: Member Management

echo "===============================================\n";
echo "  MEMBER DELETE & BLOCK/UNBLOCK - TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Simulated member database
$members = [
    1 => ['member_id' => 1, 'full_name' => 'John Member',  'is_blocked' => 0, 'active_borrows' => 0],
    2 => ['member_id' => 2, 'full_name' => 'Jane Borrower', 'is_blocked' => 0, 'active_borrows' => 2],
    3 => ['member_id' => 3, 'full_name' => 'Blocked User',  'is_blocked' => 1, 'active_borrows' => 0],
];

// Helper: simulate delete
function simulateDelete($memberId, &$members) {
    if ($memberId <= 0) {
        return ['success' => false, 'message' => 'Invalid member ID.'];
    }
    if (!isset($members[$memberId])) {
        return ['success' => false, 'message' => 'Member not found.'];
    }
    if ($members[$memberId]['active_borrows'] > 0) {
        $cnt = $members[$memberId]['active_borrows'];
        return ['success' => false, 'message' => "Cannot delete: member has $cnt unreturned book(s)."];
    }
    unset($members[$memberId]);
    return ['success' => true, 'message' => 'Member deleted successfully.'];
}

// Helper: simulate block/unblock
function simulateToggleBlock($memberId, $blockState, &$members) {
    if ($memberId <= 0) {
        return ['success' => false, 'message' => 'Invalid member ID.'];
    }
    if (!isset($members[$memberId])) {
        return ['success' => false, 'message' => 'Member not found.'];
    }
    $members[$memberId]['is_blocked'] = $blockState;
    $label = $blockState ? 'blocked' : 'unblocked';
    return ['success' => true, 'message' => "Member has been $label."];
}

// -----------------------------------------------
// Test 1: Extreme Min (Delete with ID = 0)
// -----------------------------------------------
echo "Test 1: Extreme Min (Delete with invalid ID = 0)\n";
$result = simulateDelete(0, $members);
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
// Test 2: Min -1 (Delete non-existent member)
// -----------------------------------------------
echo "Test 2: Min -1 (Delete non-existent member ID)\n";
$result = simulateDelete(999, $members);
echo "  Input: member_id=999 (does not exist)\n";
echo "  Expected: FALSE (reject - not found)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 3: Min Boundary (Delete member with no active borrows)
// -----------------------------------------------
echo "Test 3: Min Boundary (Delete member with no active borrows)\n";
$result = simulateDelete(1, $members);
echo "  Input: member_id=1 (0 active borrows)\n";
echo "  Expected: TRUE (accept - safe to delete)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === true) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 4: Min +1 (Delete member with active borrows)
// -----------------------------------------------
echo "Test 4: Min +1 (Delete member with active borrows - should be blocked)\n";
$result = simulateDelete(2, $members);
echo "  Input: member_id=2 (2 active borrows)\n";
echo "  Expected: FALSE (reject - has unreturned books)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 5: Mid (Block an active member)
// -----------------------------------------------
echo "Test 5: Mid (Block an active member)\n";
$result = simulateToggleBlock(2, 1, $members);
echo "  Input: member_id=2, blocked=1\n";
echo "  Expected: TRUE (member blocked)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === true && $members[2]['is_blocked'] === 1) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 6: Extreme Max (Unblock a blocked member)
// -----------------------------------------------
echo "Test 6: Extreme Max (Unblock a blocked member)\n";
$result = simulateToggleBlock(3, 0, $members);
echo "  Input: member_id=3 (currently blocked), blocked=0\n";
echo "  Expected: TRUE (member unblocked)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === true && $members[3]['is_blocked'] === 0) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 7: Invalid data type (Block with invalid ID)
// -----------------------------------------------
echo "Test 7: Invalid data type (Block with invalid ID = -1)\n";
$result = simulateToggleBlock(-1, 1, $members);
echo "  Input: member_id=-1\n";
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
// Test 8: Other (Block non-existent member)
// -----------------------------------------------
echo "Test 8: Other (Block non-existent member)\n";
$result = simulateToggleBlock(999, 1, $members);
echo "  Input: member_id=999 (does not exist)\n";
echo "  Expected: FALSE (reject - not found)\n";
echo "  Actual: " . ($result['success'] ? "TRUE" : "FALSE") . " - " . $result['message'] . "\n";
if ($result['success'] === false) {
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
    echo "✅ ALL MEMBER DELETE & BLOCK TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>
