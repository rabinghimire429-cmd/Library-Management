<?php
// File: tests/EmailValidationTest.php
// PHPUnit Test for Email Validation

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/validation.php';

class EmailValidationTest extends TestCase
{
    public function testValidEmail()
    {
        $this->assertTrue(validateEmail('member@test.com'));
        $this->assertTrue(validateEmail('user@domain.com'));
    }
    
    public function testInvalidEmail()
    {
        $this->assertFalse(validateEmail('invalid-email'));
        $this->assertFalse(validateEmail('missing@dot'));
        $this->assertFalse(validateEmail(''));
    }
    
    public function testEmailWithSpaces()
    {
        $this->assertTrue(validateEmail(' member@test.com '));
    }
}
?>