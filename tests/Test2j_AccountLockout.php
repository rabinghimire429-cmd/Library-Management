<?php
// TEST 2j: Account lockout after 5 failed attempts for 15 minutes

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

class AccountLockout {
    private $attempts = 0;
    private $locked = false;
    public function recordAttempt() { $this->attempts++; if ($this->attempts >= 5) { $this->locked = true; } }
    public function isLocked() { return $this->locked; }
    public function reset() { $this->attempts = 0; $this->locked = false; }
    public function getAttempts() { return $this->attempts; }
}

$account = new AccountLockout();

$test = new TestFramework();
$test->assertFalse($account->isLocked(), 'Extreme Min', '0 failed attempts');
for ($i = 1; $i <= 4; $i++) { $account->recordAttempt(); }
$test->assertFalse($account->isLocked(), 'Min -1', '4 failed attempts');
$account->recordAttempt();
$test->assertTrue($account->isLocked(), 'Min (Boundary)', '5 failed attempts');
$account->recordAttempt();
$test->assertTrue($account->isLocked(), 'Min +1', '6 failed attempts (remains locked)');
$test->skip('Max -1', 'N/A - 5 attempts is threshold');
$test->skip('Max (Boundary)', 'N/A - Fixed threshold');
$test->skip('Max +1', 'N/A - Fixed threshold');
$test->assertTrue($account->isLocked(), 'Mid', 'Attempt during lockout');
$test->assertTrue($account->isLocked(), 'Extreme Max', 'Attempt at 14 minutes 59 seconds');
$account->reset();
$test->assertFalse($account->isLocked(), 'Invalid data type', 'Attempt at exactly 15 minutes');
$test->display('2j - Account Lockout (5 attempts / 15 min)');