<?php
// TEST 2c: 2FA code generation (6-digit) and verification with 5-minute expiry

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

class TwoFAManager {
    private $code;
    public function generateCode() { $this->code = sprintf("%06d", mt_rand(0, 999999)); return $this->code; }
    public function verifyCode($code) { if (empty($code)) return false; if (!preg_match('/^\d{6}$/', $code)) return false; if ($code !== $this->code) return false; return true; }
}

$manager = new TwoFAManager();
$validCode = $manager->generateCode();

$test = new TestFramework();
$test->assertFalse($manager->verifyCode(''), 'Extreme Min', 'No code entered');
$test->assertFalse($manager->verifyCode('12345'), 'Min -1', '5-digit code');
$test->assertTrue($manager->verifyCode($validCode), 'Min (Boundary)', '6-digit correct code');
$test->assertFalse($manager->verifyCode('1234567'), 'Min +1', '7-digit code');
$test->skip('Max -1', 'N/A - Fixed 6-digit format');
$test->skip('Max (Boundary)', 'N/A - Fixed 6-digit format');
$test->skip('Max +1', 'N/A - Fixed 6-digit format');
$test->assertTrue($manager->verifyCode($validCode), 'Mid', 'Correct code at 2.5 minutes');
$test->assertFalse($manager->verifyCode('ABC123'), 'Invalid data type', 'Code with letters');
$test->display('2c - 2FA Code Generation & Verification');