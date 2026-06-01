<?php
// TEST 2b: Password validation - minimum 4 characters, no maximum limit

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

function validatePassword($password) {
    if (empty($password)) return false;
    if (strlen($password) < 4) return false;
    return true;
}

$test = new TestFramework();
$test->assertFalse(validatePassword(''), 'Extreme Min', 'Empty string');
$test->assertFalse(validatePassword('123'), 'Min -1', '3 characters');
$test->assertTrue(validatePassword('1234'), 'Min (Boundary)', '4 characters (minimum)');
$test->assertTrue(validatePassword('12345'), 'Min +1', '5 characters');
$test->skip('Max -1', 'N/A - No maximum limit');
$test->skip('Max (Boundary)', 'N/A - No maximum limit');
$test->skip('Max +1', 'N/A - No maximum limit');
$test->assertTrue(validatePassword('password123'), 'Mid', '11 characters');
$test->assertTrue(validatePassword(str_repeat('a', 100)), 'Extreme Max', '100 characters (no max limit)');
$test->assertTrue(validatePassword('P@ssw0rd!'), 'Invalid data type', 'Special characters allowed');
$test->display('2b - Password Validation');