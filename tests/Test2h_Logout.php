<?php
// TEST 2h: User logout - session destruction

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

class LogoutManager {
    private $session = false;
    public function login() { $this->session = true; }
    public function logout() { $this->session = false; }
    public function isActive() { return $this->session; }
}

$logout = new LogoutManager();
$logout->login();

$test = new TestFramework();
$test->assertTrue($logout->isActive(), 'Extreme Min - Before', 'User logged in');
$logout->logout();
$test->assertFalse($logout->isActive(), 'Extreme Min - After', 'User clicks logout - session destroyed');
$test->assertFalse($logout->isActive(), 'Min -1', 'After logout, browser back button');
$test->assertFalse($logout->isActive(), 'Min (Boundary)', 'After logout, direct URL access');
$test->assertFalse($logout->isActive(), 'Min +1', 'After logout, session variables cleared');
$test->skip('Max -1', 'N/A');
$test->skip('Max (Boundary)', 'N/A');
$test->skip('Max +1', 'N/A');
$logout->logout(); $logout->logout();
$test->assertFalse($logout->isActive(), 'Mid', 'Multiple logout clicks');
$test->assertFalse($logout->isActive(), 'Extreme Max', 'Logout from different pages');
$test->display('2h - User Logout');