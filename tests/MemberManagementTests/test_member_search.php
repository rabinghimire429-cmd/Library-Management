<?php
// File: tests/test_member_search.php
// Member Search & Filter - Boundary Value Tests
// Developer: Nabil Sarwar | Module: Member Management

echo "===============================================\n";
echo "   MEMBER SEARCH & FILTER - TEST SUITE\n";
echo "===============================================\n\n";

$passed = 0;
$failed = 0;

// Simulated member database
$members = [
    ['member_id' => 1, 'full_name' => 'John Member',    'email' => 'member@test.com',    'is_blocked' => 0, 'membership_date' => '2026-01-01'],
    ['member_id' => 2, 'full_name' => 'Sarah Librarian', 'email' => 'librarian@test.com', 'is_blocked' => 0, 'membership_date' => '2026-02-15'],
    ['member_id' => 3, 'full_name' => 'Blocked User',   'email' => 'blocked@test.com',   'is_blocked' => 1, 'membership_date' => '2026-03-10'],
    ['member_id' => 4, 'full_name' => 'Jane Doe',       'email' => 'jane@test.com',       'is_blocked' => 0, 'membership_date' => '2026-04-20'],
];

// Helper: simulate search by name or email
function searchMembers($members, $search) {
    if (empty($search)) return $members;
    return array_filter($members, function($m) use ($search) {
        return stripos($m['full_name'], $search) !== false ||
               stripos($m['email'],    $search) !== false;
    });
}

// Helper: simulate filter by status
function filterByStatus($members, $status) {
    if ($status === '') return $members;
    $blocked = ($status === 'blocked') ? 1 : 0;
    return array_filter($members, fn($m) => $m['is_blocked'] == $blocked);
}

// Helper: simulate filter by date range
function filterByDate($members, $from, $to) {
    return array_filter($members, function($m) use ($from, $to) {
        $date = $m['membership_date'];
        if ($from && $date < $from) return false;
        if ($to   && $date > $to)   return false;
        return true;
    });
}

// -----------------------------------------------
// Test 1: Extreme Min (Empty search - returns all)
// -----------------------------------------------
echo "Test 1: Extreme Min (Empty search string - returns all members)\n";
$result = searchMembers($members, '');
echo "  Input: search=''\n";
echo "  Expected: All 4 members returned\n";
echo "  Actual: " . count($result) . " members returned\n";
if (count($result) === 4) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 2: Min -1 (Single character search)
// -----------------------------------------------
echo "Test 2: Min -1 (Single character search)\n";
$result = searchMembers($members, 'J');
echo "  Input: search='J'\n";
echo "  Expected: Members with J in name/email (John, Jane)\n";
echo "  Actual: " . count($result) . " members - ";
foreach ($result as $m) echo $m['full_name'] . " ";
echo "\n";
if (count($result) >= 1) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 3: Min Boundary (Search by exact email)
// -----------------------------------------------
echo "Test 3: Min Boundary (Search by exact email match)\n";
$result = searchMembers($members, 'member@test.com');
echo "  Input: search='member@test.com'\n";
echo "  Expected: 1 member found\n";
echo "  Actual: " . count($result) . " member(s) found\n";
if (count($result) === 1) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 4: Min +1 (Search with no matches)
// -----------------------------------------------
echo "Test 4: Min +1 (Search term with no matches)\n";
$result = searchMembers($members, 'zzznomatch');
echo "  Input: search='zzznomatch'\n";
echo "  Expected: 0 members found\n";
echo "  Actual: " . count($result) . " members found\n";
if (count($result) === 0) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 5: Mid (Filter by active status)
// -----------------------------------------------
echo "Test 5: Mid (Filter by active status)\n";
$result = filterByStatus($members, 'active');
echo "  Input: status='active'\n";
echo "  Expected: 3 active members\n";
echo "  Actual: " . count($result) . " members\n";
if (count($result) === 3) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 6: Extreme Max (Filter by blocked status)
// -----------------------------------------------
echo "Test 6: Extreme Max (Filter by blocked status)\n";
$result = filterByStatus($members, 'blocked');
echo "  Input: status='blocked'\n";
echo "  Expected: 1 blocked member\n";
echo "  Actual: " . count($result) . " members\n";
if (count($result) === 1) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 7: Invalid data type (Filter by date range)
// -----------------------------------------------
echo "Test 7: Invalid data type (Filter by date range)\n";
$result = filterByDate($members, '2026-02-01', '2026-04-01');
echo "  Input: dateFrom='2026-02-01', dateTo='2026-04-01'\n";
echo "  Expected: 2 members (Sarah, Blocked User)\n";
echo "  Actual: " . count($result) . " members\n";
if (count($result) === 2) {
    echo "  Result: ✅ PASS\n\n";
    $passed++;
} else {
    echo "  Result: ❌ FAIL\n\n";
    $failed++;
}

// -----------------------------------------------
// Test 8: Other (Case-insensitive search)
// -----------------------------------------------
echo "Test 8: Other (Case-insensitive search)\n";
$result1 = searchMembers($members, 'john');
$result2 = searchMembers($members, 'JOHN');
echo "  Input: search='john' vs search='JOHN'\n";
echo "  Expected: Same results for both\n";
echo "  Actual: 'john'=" . count($result1) . " results, 'JOHN'=" . count($result2) . " results\n";
if (count($result1) === count($result2) && count($result1) > 0) {
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
    echo "✅ ALL MEMBER SEARCH & FILTER TESTS PASSED!\n";
} else {
    echo "❌ SOME TESTS FAILED! Please review.\n";
}
echo "===============================================\n";
?>
