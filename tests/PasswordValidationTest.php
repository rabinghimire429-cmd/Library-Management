<?php
// File: tests/PasswordValidationTest.php
// PHPUnit Test for Password Validation

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/validation.php';

class PasswordValidationTest extends TestCase
{
    public function testValidPassword()
    {
        $this->assertTrue(validatePassword('1234'));   // 4 chars - boundary
        $this->assertTrue(validatePassword('12345'));  // 5 chars
        $this->assertTrue(validatePassword('P@ssw0rd!')); // Special chars
    }
    
    public function testInvalidPassword()
    {
        $this->assertFalse(validatePassword('123'));   // 3 chars
        $this->assertFalse(validatePassword(''));      // Empty
    }
}
?>