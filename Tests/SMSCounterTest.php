<?php

namespace Instasent\SMSCounter\Tests;

use Instasent\SMSCounter\SMSCounter;
use PHPUnit\Framework\TestCase;

class SMSCounterTest extends TestCase
{
    public function testGSM()
    {
        $text = 'a GSM Text';

        $smsCounter = new SMSCounter();
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT;
        $expected->length = 10;
        $expected->per_message = 160;
        $expected->remaining = 150;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testGSM_TR()
    {
        $text = 'a GSM TR ç Text';

        $smsCounter = new SMSCounter();
        $count = $smsCounter->countWithShiftTables($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT_EX;
        $expected->length = 16;
        $expected->per_message = 160;
        $expected->remaining = 144;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testGSM_ES()
    {
        $text = 'a GSM ES Ú Text';

        $smsCounter = new SMSCounter();
        $count = $smsCounter->countWithShiftTables($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT_EX;
        $expected->length = 16;
        $expected->per_message = 160;
        $expected->remaining = 144;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testGSM_PT()
    {
        $text = 'a GSM PT Ã Text';

        $smsCounter = new SMSCounter();
        $count = $smsCounter->countWithShiftTables($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT_EX;
        $expected->length = 16;
        $expected->per_message = 160;
        $expected->remaining = 144;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testGSMSymbols()
    {
        $text = 'a GSM +Text';
        $smsCounter = new SMSCounter();
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT;
        $expected->length = 11;
        $expected->per_message = 160;
        $expected->remaining = 149;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testGSMMultiPage()
    {
        $text = '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';

        $smsCounter = new SMSCounter();
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT;
        $expected->length = 170;
        $expected->per_message = 153;
        $expected->remaining = 153 * 2 - 170;
        $expected->messages = 2;

        $this->assertEquals($expected, $count);
    }

    public function testUnicodeMultiPage()
    {
        $text = '`';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';
        $text .= '1234567890';

        $smsCounter = new SMSCounter();
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::UTF16;
        $expected->length = 71;
        $expected->per_message = 67;
        $expected->remaining = 67 * 2 - 71;
        $expected->messages = 2;

        $this->assertEquals($expected, $count);
    }

    public function testCarriageReturn()
    {
        $text = "\n\r";
        $smsCounter = new SMSCounter();
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::GSM_7BIT;
        $expected->length = 2;
        $expected->per_message = 160;
        $expected->remaining = 158;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testUnicodeChars()
    {
        $smsCounter = new SMSCounter();

        $this->assertEquals([96], $smsCounter->utf8ToUnicode('`')); // U+60
        $this->assertEquals([882], $smsCounter->utf8ToUnicode('Ͳ')); // U+0372
        $this->assertEquals([2210], $smsCounter->utf8ToUnicode('ࢢ')); // U+08A2
        $this->assertEquals([11821], $smsCounter->utf8ToUnicode('⸭')); // U+2E2D
        $this->assertEquals([128526], $smsCounter->utf8ToUnicode('😎')); // U+1F60E
    }

    public function testUnicode()
    {
        $text = '`';
        $smsCounter = new SMSCounter();
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::UTF16;
        $expected->length = 1;
        $expected->per_message = 70;
        $expected->remaining = 69;
        $expected->messages = 1;

        $this->assertEquals($expected, $count);
    }

    public function testUnicodeEmoji()
    {
        $text = '😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎😎';

        $smsCounter = new SMSCounter();
        $count = $smsCounter->count($text);

        $expected = new \stdClass();
        $expected->encoding = SMSCounter::UTF16;
        $expected->length = 77;
        $expected->per_message = 67;
        $expected->remaining = 57;
        $expected->messages = 2;

        $this->assertEquals($expected, $count);
    }

    public function testRemoveNonGSMChars()
    {
        $text = 'áno-unicode-remaining` ñ';
        $expectedTExt = 'no-unicode-remaining ñ';

        $smsCounter = new SMSCounter();
        $output = $smsCounter->removeNonGsmChars($text);

        $this->assertEquals($expectedTExt, $output);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSanitizeToGSM($text, $expectedText)
    {
        $smsCounter = new SMSCounter();
        $output = $smsCounter->sanitizeToGSM($text);

        $this->assertEquals($expectedText, $output);
    }

    public function testTruncate1SmsGSM7()
    {
        $text = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem.';
        $expectedTExt = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient ';

        $smsCounter = new SMSCounter();
        $output = $smsCounter->truncate($text, 1);

        $this->assertEquals($expectedTExt, $output);
    }

    public function testTruncate1SmsGSM7ShiftTable()
    {
        $text = 'ÚLorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem.';
        $expectedTExt = 'ÚLorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturien';

        $smsCounter = new SMSCounter();
        $output = $smsCounter->truncateWithShiftTables($text, 1);

        $this->assertEquals($expectedTExt, $output);
    }

    public function testTruncate2SmsGSM7()
    {
        $text = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient';
        $expectedTExt = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis d';

        $smsCounter = new SMSCounter();
        $output = $smsCounter->truncate($text, 2);

        $this->assertEquals($expectedTExt, $output);
    }

    public function testTruncate2SmsGSM7ShiftTable()
    {
        $text = 'çLorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturie';
        $expectedTExt = 'çLorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magni';

        $smsCounter = new SMSCounter();
        $output = $smsCounter->truncateWithShiftTables($text, 2);

        $this->assertEquals($expectedTExt, $output);
    }

    public function testTruncate1SmsUnicode()
    {
        $text = 'Snowman shows off! ☃ Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa';
        $expectedTExt = 'Snowman shows off! ☃ Lorem ipsum dolor sit amet, consectetuer adipisci';

        $smsCounter = new SMSCounter();
        $output = $smsCounter->truncate($text, 1);

        $this->assertEquals($expectedTExt, $output);
    }

    public function testTruncate2SmsUnicode()
    {
        $text = 'Snowman shows off! ☃ Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula e ☃get dolor. Aenean massa Lorem ipsum dolor sit amet, consectetuer adip eg';
        $expectedTExt = 'Snowman shows off! ☃ Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula e ☃get dolor. Aenean massa Lorem ';

        $smsCounter = new SMSCounter();
        $output = $smsCounter->truncate($text, 2);

        $this->assertEquals($expectedTExt, $output);
    }

    public function dataProvider()
    {
        return [
            ['@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞ^{}\[~]|€ÆæßÉ!\"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà', '@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞ^{}\[~]|€ÆæßÉ!\"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà'],
            ['Lhg jjjo fx 382 64237 12299 qmecb. Ç éæ+! -[Å*_ (¡)| ?Λ^ ~£;ΩΠ¿ ÑΔ #ΓüΘ¥ñ,É øΨì] ò= Ü. @å<: ö%\'Æ¤"Ö> Ø§Φ{ }/&Ä ùß\€ èà Ξ$äΣ.', 'Lhg jjjo fx 382 64237 12299 qmecb. Ç éæ+! -[Å*_ (¡)| ?Λ^ ~£;ΩΠ¿ ÑΔ #ΓüΘ¥ñ,É øΨì] ò= Ü. @å<: ö%\'Æ¤"Ö> Ø§Φ{ }/&Ä ùß\€ èà Ξ$äΣ.'],
            ['dadáó', 'dadao'],
            ["\xc2\xa0|\xe2\x80\x87|\xef\xbb\xbf", ' | |'],
        ];
    }
}
