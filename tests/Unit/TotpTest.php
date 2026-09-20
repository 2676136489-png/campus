<?php

use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function testGenerateSecretUsesBase32Alphabet()
    {
        $secret = generateTotpSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testBase32RoundTrip()
    {
        $raw = 'campus-totp';
        $this->assertSame($raw, base32Decode(base32Encode($raw)));
    }

    public function testVerifyTotpAcceptsGeneratedCode()
    {
        $secret = generateTotpSecret();
        $this->assertTrue(verifyTotp($secret, totpCode($secret)));
    }

    public function testVerifyTotpRejectsWrongCode()
    {
        $secret = generateTotpSecret();
        $this->assertFalse(verifyTotp($secret, '000000'));
    }
}