<?php
echo "========================================\n";
echo "EMAIL VALIDATION TEST RESULTS\n";
echo "========================================\n\n";

function validateEmail($email) {
    if (empty($email)) return false;
    if (strlen($email) > 100) return false;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    if (strpos($email, '@') === false || strpos($email, '.') === false) return false;
    return true;
}

$tests = [
    ['Extreme Min - Empty string', '', false],
    ['Min -1 - Missing dot', 'user@test', false],
    ['Min (Boundary) - Minimum valid', 'a@b.c', true],
    ['Min +1 - Standard email', 'user@domain.com', true],
    ['Max -1 - N/A', 'N/A', null],
    ['Max (Boundary) - N/A', 'N/A', null],
    ['Max +1 - N/A', 'N/A', null],
    ['Mid - Library email', 'member@library.com', true],
    ['Extreme Max - Very long (100+ chars)', str_repeat('a', 90) . '@' . str_repeat('b', 10) . '.com', false],
    ['Invalid data type - No @ symbol', 'notanemail', false],
    ['Other tests - SQL injection', "' OR '1'='1", false],
];

$passed = 0;
$failed = 0;
$skipped = 0;

foreach ($tests as $test) {
    $name = $test[0];
    $input = $test[1];
    $expected = $test[2];
    
    if ($expected === null) {
        echo "⚠ SKIPPED: $name (N/A - preserved in documentation)\n";
        $skipped++;
        continue;
    }
    
    $result = validateEmail($input);
    if ($result === $expected) {
        echo "✓ PASS: $name\n";
        $passed++;
    } else {
        echo "✗ FAIL: $name (Expected: " . ($expected ? 'true' : 'false') . ", Got: " . ($result ? 'true' : 'false') . ")\n";
        $failed++;
    }
}

echo "\n========================================\n";
echo "RESULTS: $passed PASSED, $failed FAILED, $skipped SKIPPED\n";
echo "========================================\n";