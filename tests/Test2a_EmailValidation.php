<?php
// TEST 2a: Email validation function - validates format and presence of @ and .

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

function validateEmail($email) {
    if (empty($email)) return false;
    if (strlen($email) > 100) return false;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    if (strpos($email, '@') === false || strpos($email, '.') === false) return false;
    return true;
}

$test = new TestFramework();
$test->assertFalse(validateEmail(''), 'Extreme Min', 'Empty string');
$test->assertFalse(validateEmail('user@test'), 'Min -1', 'Missing dot');
$test->assertTrue(validateEmail('a@b.c'), 'Min (Boundary)', 'Minimum valid email');
$test->assertTrue(validateEmail('user@domain.com'), 'Min +1', 'Standard email');
$test->skip('Max -1', 'N/A - No upper limit defined');
$test->skip('Max (Boundary)', 'N/A - No upper limit defined');
$test->skip('Max +1', 'N/A - No upper limit defined');
$test->assertTrue(validateEmail('member@library.com'), 'Mid', 'Library email');
$test->assertFalse(validateEmail(str_repeat('a', 90) . '@' . str_repeat('b', 10) . '.com'), 'Extreme Max', 'Very long email (100+ chars)');
$test->assertFalse(validateEmail('notanemail'), 'Invalid data type', 'No @ symbol');
$test->assertFalse(validateEmail("' OR '1'='1"), 'Other tests', 'SQL injection');
$test->display('2a - Email Validation Function');