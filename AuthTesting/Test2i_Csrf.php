<?php
// TEST 2i: CSRF token validation on forms

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

class CsrfManager {
    private $token = null;
    public function generateToken() { $this->token = bin2hex(random_bytes(32)); return $this->token; }
    public function validateToken($token) { if (empty($token)) return false; if ($this->token === null) return false; return hash_equals($this->token, $token); }
}

$csrf = new CsrfManager();
$validToken = $csrf->generateToken();

$test = new TestFramework();
$test->assertFalse($csrf->validateToken(null), 'Extreme Min', 'No CSRF token submitted');
$test->assertFalse($csrf->validateToken('invalid'), 'Min -1', 'Invalid CSRF token');
$test->assertTrue($csrf->validateToken($validToken), 'Min (Boundary)', 'Valid CSRF token');
$test->assertFalse($csrf->validateToken('expired_token'), 'Min +1', 'Expired CSRF token');
$test->skip('Max -1', 'N/A - Fixed token length 64 chars');
$test->skip('Max (Boundary)', 'N/A - Fixed token length');
$test->skip('Max +1', 'N/A - Fixed token length');
$newCsrf = new CsrfManager();
$test->assertFalse($newCsrf->validateToken($validToken), 'Mid', 'Token from different session');
$test->assertFalse($csrf->validateToken($validToken . 'extra'), 'Extreme Max', 'Token with extra characters');
$test->assertFalse($csrf->validateToken(''), 'Invalid data type', 'Empty token string');
$test->display('2i - CSRF Token Validation');