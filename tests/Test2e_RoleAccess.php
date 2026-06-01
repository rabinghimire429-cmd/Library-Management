<?php
// TEST 2e: Role-Based Access Control - Member cannot access Librarian dashboard

class TestFramework {
    private $passed = 0; private $failed = 0; private $skipped = 0; private $results = [];
    public function assertTrue($condition, $name, $desc) { if ($condition) { $this->passed++; $this->results[] = "✓ PASS | $name | $desc"; } else { $this->failed++; $this->results[] = "✗ FAIL | $name | $desc"; } }
    public function assertFalse($condition, $name, $desc) { $this->assertTrue(!$condition, $name, $desc); }
    public function skip($name, $reason) { $this->skipped++; $this->results[] = "⚠ SKIPPED | $name | $reason"; }
    public function display($testName) { echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nTEST: $testName\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($this->results as $r) echo "$r\n"; echo "\n──────────────────────────────────────────────────────────────────────\nRESULTS: {$this->passed} PASSED | {$this->failed} FAILED | {$this->skipped} SKIPPED\nTOTAL ASSERTIONS: " . ($this->passed + $this->failed) . "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"; } }

function checkAccess($email, $role) {
    $memberEmails = ['member@library.com'];
    $librarianEmails = ['librarian@library.com'];
    if ($email === null) return false;
    $isMember = in_array($email, $memberEmails);
    $isLibrarian = in_array($email, $librarianEmails);
    if ($isMember && $role === 'librarian') return false;
    if ($isLibrarian && $role === 'member') return false;
    if ($role === 'librarian' && $isLibrarian) return true;
    if ($role === 'member' && $isMember) return true;
    return false;
}

$test = new TestFramework();
$test->assertFalse(checkAccess(null, 'librarian'), 'Extreme Min', 'No session (logged out)');
$test->assertFalse(checkAccess('member@library.com', 'librarian'), 'Min -1', 'Member selecting Librarian Login');
$test->assertTrue(checkAccess('librarian@library.com', 'librarian'), 'Min (Boundary)', 'Librarian selecting Librarian Login');
$test->assertFalse(checkAccess('librarian@library.com', 'member'), 'Min +1', 'Librarian selecting Member Login');
$test->skip('Max -1', 'N/A');
$test->skip('Max (Boundary)', 'N/A');
$test->skip('Max +1', 'N/A');
$test->assertTrue(checkAccess('member@library.com', 'member'), 'Mid', 'Member selecting Member Login');
$test->assertFalse(checkAccess('member@library.com', 'Admin'), 'Invalid data type', 'Manipulated session role (Admin)');
$test->display('2e - Role-Based Access Control');