<?php

namespace WobbleCode\SMSCounter\Tests;

use WobbleCode\SMSCounter\SMSCounter;

class SMSCounterTest extends \PHPUnit_Framework_TestCase
{
    public function testGSM()
    {
        $text = "a GSM Text";

        $smsCounter = new SMSCounter;
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT;
        $expected->length = 10;
        $expected->per_message= 160;
        $expected->remaining = 150;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testGSMSymbols()
    {
        $text = "a GSM +Text";
        $smsCounter = new SMSCounter;
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT;
        $expected->length = 11;
        $expected->per_message= 160;
        $expected->remaining = 149;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testGSMMultiPage()
    {
        $text = "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";

        $smsCounter = new SMSCounter;
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT;
        $expected->length = 170;
        $expected->per_message= 153;
        $expected->remaining = 153 * 2 - 170;
        $expected->messages = 2;

        $this->assertEquals($expected, $count);
    }

    public function testUnicodeMultiPage()
    {
        $text = "`";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";
        $text .= "1234567890";

        $smsCounter = new SMSCounter;
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::UTF16;
        $expected->length = 71;
        $expected->per_message= 67;
        $expected->remaining = 67 * 2 - 71;
        $expected->messages = 2;

        $this->assertEquals($expected, $count);
    }

    public function testCarriageReturn()
    {
        $text = "\n\r";
        $smsCounter = new SMSCounter;
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT;
        $expected->length = 2;
        $expected->per_message = 160;
        $expected->remaining = 158;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testUnicode()
    {
        $text = "`";
        $smsCounter = new SMSCounter;
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::UTF16;
        $expected->length = 1;
        $expected->per_message= 70;
        $expected->remaining = 69;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testRemoveNonGSMChars()
    {
        $text = "áno-unicode-remaining`";
        $expectedTExt = "no-unicode-remaining";

        $smsCounter = new SMSCounter;
        $output = $smsCounter->removeNonGsmChars($text);

        $this->assertEquals($expectedTExt, $output);
    }
}
