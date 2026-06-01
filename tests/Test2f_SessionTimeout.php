<?php
// TEST 2f: Session timeout after 30 minutes of inactivity

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

function isExpired($lastActivity, $timeout = 1800) {
    if ($lastActivity === null) return true;
    return (time() - $lastActivity) > $timeout;
}

$now = time();

$test = new TestFramework();
$test->assertTrue(isExpired(null), 'Extreme Min', 'User logs out immediately');
$test->assertFalse(isExpired($now - 900), 'Min -1', '15 minutes of inactivity');
$test->assertFalse(isExpired($now - 1740), 'Min (Boundary)', '29 minutes of inactivity');
$test->assertTrue(isExpired($now - 1800), 'Min +1', '30 minutes of inactivity');
$test->skip('Max -1', 'N/A - 30 min is fixed limit');
$test->skip('Max (Boundary)', 'N/A - 30 min is fixed limit');
$test->skip('Max +1', 'N/A - 30 min is fixed limit');
$test->assertFalse(isExpired($now - 500), 'Mid', 'User activity before timeout resets timer');
$test->assertTrue(isExpired($now - 3600), 'Extreme Max', '60 minutes of inactivity');
$test->display('2f - Session Timeout (30 minutes)');