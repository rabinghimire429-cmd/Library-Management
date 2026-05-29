<?php
// File: tests/TwoFATest.php
// PHPUnit Test for Two-Factor Authentication

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/2fa.php';

class TwoFATest extends TestCase
{
    public function testGenerateSixDigitCode()
    {
        $code = generate2FACode();
        
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
    }
    
    public function testDifferentCodesEachTime()
    {
        $code1 = generate2FACode();
        $code2 = generate2FACode();
        
        $this->assertNotEquals($code1, $code2);
    }
    
    public function testVerifyCorrectCode()
    {
        $storedCode = '123456';
        $expiry = time() + 300;
        $result = verify2FACode('123456', $storedCode, $expiry);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Code verified successfully', $result['message']);
    }
    
    public function testVerifyWrongCode()
    {
        $storedCode = '123456';
        $expiry = time() + 300;
        $result = verify2FACode('999999', $storedCode, $expiry);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid verification code', $result['message']);
    }
    
    public function testVerifyExpiredCode()
    {
        $storedCode = '123456';
        $expiry = time() - 60;
        $result = verify2FACode('123456', $storedCode, $expiry);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expired', $result['message']);
    }
}
?>