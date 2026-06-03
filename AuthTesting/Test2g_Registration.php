<?php
// TEST 2g: New user registration with validation

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

class RegistrationManager {
    private $users = [];
    public function register($email, $password, $confirm) {
        if (empty($email) || empty($password) || empty($confirm)) return false;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        if ($password !== $confirm) return false;
        if (strlen($password) < 4) return false;
        if (isset($this->users[$email])) return false;
        $this->users[$email] = $password;
        return true;
    }
    public function addExisting($email) { $this->users[$email] = 'pass123'; }
}

$reg = new RegistrationManager();
$reg->addExisting('existing@example.com');

$test = new TestFramework();
$test->assertFalse($reg->register('', '', ''), 'Extreme Min', 'Empty all fields');
$test->assertFalse($reg->register('invalid-email', '1234', '1234'), 'Min -1', 'Invalid email format');
$test->assertTrue($reg->register('new@example.com', '1234', '1234'), 'Min (Boundary)', 'Valid email, short password (4 chars)');
$test->assertTrue($reg->register('new2@example.com', 'verylongpassword123', 'verylongpassword123'), 'Min +1', 'Valid email, long password');
$test->skip('Max -1', 'N/A - No defined maximum');
$test->skip('Max (Boundary)', 'N/A - No defined maximum');
$test->skip('Max +1', 'N/A - No defined maximum');
$test->assertTrue($reg->register('miduser@example.com', 'pass123', 'pass123'), 'Mid', 'Valid email, matching passwords');
$test->assertFalse($reg->register('existing@example.com', 'pass123', 'pass123'), 'Extreme Max', 'Duplicate email');
$test->assertFalse($reg->register('test@example.com', '1234', '5678'), 'Invalid data type', 'Password mismatch');
$test->assertTrue($reg->register('weak@example.com', '1234', '1234'), 'Other tests', 'Weak password allowed (min 4 chars)');
$test->display('2g - New User Registration');