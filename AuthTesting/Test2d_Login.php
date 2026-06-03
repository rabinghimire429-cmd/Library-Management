<?php
// TEST 2d: User login with email and password validation

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

class LoginManager {
    private $users = [];
    public function __construct() { $this->users['member@library.com'] = password_hash('member123', PASSWORD_BCRYPT); }
    public function authenticate($email, $password) { if (empty($email) || empty($password)) return false; if (!isset($this->users[$email])) return false; return password_verify($password, $this->users[$email]); }
}

$login = new LoginManager();

$test = new TestFramework();
$test->assertFalse($login->authenticate('', ''), 'Extreme Min', 'Empty email and password');
$test->assertFalse($login->authenticate('member@library.com', 'wrongpass'), 'Min -1', 'Valid email, wrong password');
$test->assertTrue($login->authenticate('member@library.com', 'member123'), 'Min (Boundary)', 'Valid email, correct password');
$test->assertTrue($login->authenticate('member@library.com', 'member123'), 'Min +1', 'Member email, correct password');
$test->skip('Max -1', 'N/A');
$test->skip('Max (Boundary)', 'N/A');
$test->skip('Max +1', 'N/A');
$test->assertFalse($login->authenticate('nonexistent@example.com', 'pass'), 'Invalid data type', 'Email not in database');
$test->display('2d - User Login');