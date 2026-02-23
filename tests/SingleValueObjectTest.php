<?php

declare (strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSpecException;
use Tests\SingleValueObjects\GmailSVO;
use Tests\SingleValueObjects\InvalidSpecSVO;
use Tests\SingleValueObjects\MailSVO;

class SingleValueObjectTest extends TestCase
{
    public array $email = [
        'example@mail.com',
        'example@gmail.com',
    ];
    public function testBasic()
    {
        $mail = MailSVO::from($this->email[0]);
        $this->assertEquals($mail->value, $this->email[0]);
        $this->assertEquals($mail(), $this->email[0]);
        $this->assertEquals((string) $mail, $this->email[0]);

        $mail2 = MailSVO::from($this->email[0]);
        $this->assertTrue($mail->equals($mail2));

        GmailSVO::from($this->email[1]);
    }
    public function testInvalidSpecThrowInvalidSpecException()
    {
        $this->expectException(InvalidSpecException::class);
        $this->expectExceptionMessage('#[Spec] value for Tests\SingleValueObjects\InvalidSpecSVO is required, must be a string, and cannot be empty.');
        InvalidSpecSVO::from('');
    }
}
