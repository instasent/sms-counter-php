<?php

namespace Instasent\SMSCounter;

class SMSCounter
{
    /**
     * GSM 7BIT ecoding name.
     *
     * @var string
     */
    const GSM_7BIT = 'GSM_7BIT';

    /**
     * GSM 7BIT Extended ecoding name.
     *
     * @var string
     */
    const GSM_7BIT_EX = 'GSM_7BIT_EX';

    /**
     * UTF16 or UNICODE ecoding name.
     *
     * @var string
     */
    const UTF16 = 'UTF16';

    /**
     * Message length for GSM 7 Bit charset.
     *
     * @var int
     */
    const GSM_7BIT_LEN = 160;

    /**
     * Message length for GSM 7 Bit charset with extended characters.
     *
     * @var int
     */
    const GSM_7BIT_EX_LEN = 160;

    /**
     * Message length for UTF16/Unicode charset.
     *
     * @var int
     */
    const UTF16_LEN = 70;

    /**
     * Message length for multipart message in GSM 7 Bit encoding.
     *
     * @var int
     */
    const GSM_7BIT_LEN_MULTIPART = 153;

    /**
     * Message length for multipart message in GSM 7 Bit encoding.
     *
     * @var int
     */
    const GSM_7BIT_EX_LEN_MULTIPART = 153;

    /**
     * Message length for multipart message in GSM 7 Bit encoding.
     *
     * @var int
     */
    const UTF16_LEN_MULTIPART = 67;

    public function getGsm7bitMap()
    {
        return [
            10, 12, 13, 32, 33, 34, 35, 36,
            37, 38, 39, 40, 41, 42, 43, 44,
            45, 46, 47, 48, 49, 50, 51, 52,
            53, 54, 55, 56, 57, 58, 59, 60,
            61, 62, 63, 64, 65, 66, 67, 68,
            69, 70, 71, 72, 73, 74, 75, 76,
            77, 78, 79, 80, 81, 82, 83, 84,
            85, 86, 87, 88, 89, 90, 91, 92,
            93,  94, 95, 97, 98, 99, 100, 101,
            102, 103, 104, 105, 106, 107, 108,
            109, 110, 111, 112, 113, 114, 115,
            116, 117, 118, 119, 120, 121, 122,
            123, 124, 125, 126, 161, 163, 164,
            165, 167, 191, 196, 197, 198, 199,
            201, 209, 214, 216, 220, 223, 224,
            228, 229, 230, 232, 233, 236, 241,
            242, 246, 248, 249, 252, 915, 916,
            920, 923, 926, 928, 931, 934, 936,
            937, 8364,
        ];
    }

    public function getAddedGsm7bitExMap()
    {
        return [12, 91, 92, 93, 94, 123, 124, 125, 126, 8364];
    }

    public function getGsm7bitExMap()
    {
        return array_merge(
            $this->getGsm7bitMap(),
            $this->getAddedGsm7bitExMap()
        );
    }

    /**
     * Detects the encoding, Counts the characters, message length, remaining characters.
     *
     * @return \stdClass Object with params encoding,length, per_message, remaining, messages
     */
    public function count($text)
    {
        $unicodeArray = $this->utf8ToUnicode($text);

        // variable to catch if any ex chars while encoding detection.
        $exChars = [];
        $encoding = $this->detectEncoding($unicodeArray, $exChars);
        $length = count($unicodeArray);

        if ($encoding === self::GSM_7BIT_EX) {
            $lengthExchars = count($exChars);
            // Each exchar in the GSM 7 Bit encoding takes one more space
            // Hence the length increases by one char for each of those Ex chars.
            $length += $lengthExchars;
        }

        // Select the per message length according to encoding and the message length
        switch ($encoding) {
            case self::GSM_7BIT:
                $perMessage = self::GSM_7BIT_LEN;
                if ($length > self::GSM_7BIT_LEN) {
                    $perMessage = self::GSM_7BIT_LEN_MULTIPART;
                }
                break;

            case self::GSM_7BIT_EX:
                $perMessage = self::GSM_7BIT_EX_LEN;
                if ($length > self::GSM_7BIT_EX_LEN) {
                    $perMessage = self::GSM_7BIT_EX_LEN_MULTIPART;
                }
                break;

            default:
                $perMessage = self::UTF16_LEN;
                if ($length > self::UTF16_LEN) {
                    $perMessage = self::UTF16_LEN_MULTIPART;
                }

                break;
        }

        $messages = (int) ceil($length / $perMessage);
        $remaining = ($perMessage * $messages) - $length;

        $returnset = new \stdClass();

        $returnset->encoding = $encoding;
        $returnset->length = $length;
        $returnset->per_message = $perMessage;
        $returnset->remaining = $remaining;
        $returnset->messages = $messages;

        return $returnset;
    }

    /**
     * Detects the encoding of a particular text.
     *
     * @return string (GSM_7BIT|GSM_7BIT_EX|UTF16)
     */
    public function detectEncoding($text, &$exChars)
    {
        if (!is_array($text)) {
            $text = self::utf8ToUnicode($text);
        }

        $utf16Chars = array_diff($text, $this->getGsm7bitExMap());

        if (count($utf16Chars)) {
            return self::UTF16;
        }

        $exChars = array_intersect($text, $this->getAddedGsm7bitExMap());

        if (count($exChars)) {
            return self::GSM_7BIT_EX;
        }

        return self::GSM_7BIT;
    }

    /**
     * Generates array of unicode points for the utf8 string.
     *
     * @return array
     */
    public function utf8ToUnicode($str)
    {
        $unicode = [];
        $values = [];
        $lookingFor = 1;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $thisValue = ord($str[$i]);

            if ($thisValue < 128) {
                $unicode[] = $thisValue;
            }

            if ($thisValue >= 128) {
                if (count($values) == 0) {
                    $lookingFor = ($thisValue < 224) ? 2 : 3;
                }

                $values[] = $thisValue;

                if (count($values) == $lookingFor) {
                    $number = ($lookingFor == 3) ?
                    (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64) :
                    (($values[0] % 32) * 64) + ($values[1] % 64);

                    $unicode[] = $number;
                    $values = [];
                    $lookingFor = 1;
                }
            }
        }

        return $unicode;
    }

    /**
     * Unicode equivalent chr() function.
     *
     * @return array characters
     */
    public function utf8Chr($unicode)
    {
        $unicode = intval($unicode);

        $utf8char = chr(240 | ($unicode >> 18));
        $utf8char .= chr(128 | (($unicode >> 12) & 0x3F));
        $utf8char .= chr(128 | (($unicode >> 6) & 0x3F));
        $utf8char .= chr(128 | ($unicode & 0x3F));

        if ($unicode < 128) {
            $utf8char = chr($unicode);
        } elseif ($unicode >= 128 && $unicode < 2048) {
            $utf8char = chr(192 | ($unicode >> 6)).chr(128 | ($unicode & 0x3F));
        } elseif ($unicode >= 2048 && $unicode < 65536) {
            $utf8char = chr(224 | ($unicode >> 12)).chr(128 | (($unicode >> 6) & 0x3F)).chr(128 | ($unicode & 0x3F));
        }

        return $utf8char;
    }

    /**
     * Converts unicode code points array to a utf8 str.
     *
     * @param array $array unicode codepoints array
     *
     * @return string utf8 encoded string
     */
    public function unicodeToUtf8($array)
    {
        $str = '';
        foreach ($array as $a) {
            $str .= $this->utf8Chr($a);
        }

        return $str;
    }

    /**
     * Removes non GSM characters from a string.
     *
     * @return string
     */
    public function removeNonGsmChars($str)
    {
        return $this->replaceNonGsmChars($str, null);
    }

    /**
     * Replaces non GSM characters from a string.
     *
     * @param string $str         String to be replaced
     * @param string $replacement String of characters to be replaced with
     *
     * @return (string|false) if replacement string is more than 1 character
     *                        in length
     */
    public function replaceNonGsmChars($str, $replacement = null)
    {
        $validChars = $this->getGsm7bitExMap();
        $allChars = self::utf8ToUnicode($str);

        if (strlen($replacement) > 1) {
            return false;
        }

        $replacementArray = [];
        $unicodeArray = $this->utf8ToUnicode($replacement);
        $replacementUnicode = array_pop($unicodeArray);

        foreach ($allChars as $key => $char) {
            if (!in_array($char, $validChars)) {
                $replacementArray[] = $key;
            }
        }

        if ($replacement) {
            foreach ($replacementArray as $key) {
                $allChars[$key] = $replacementUnicode;
            }
        }

        if (!$replacement) {
            foreach ($replacementArray as $key) {
                unset($allChars[$key]);
            }
        }

        return $this->unicodeToUtf8($allChars);
    }

    public function sanitizeToGSM($str)
    {
        $str = $this->removeAccents($str);
        $str = $this->removeNonGsmChars($str);

        return $str;
    }

    /**
     * @param string $str Message text
     *
     * @return string Sanitized message text
     */
    public function removeAccents($str)
    {
        if (!preg_match('/[\x80-\xff]/', $str)) {
            return $str;
        }

        $chars = [
          // Decompositions for Latin-1 Supplement
          'ª' => 'a', 'º' => 'o',
          'À' => 'A', 'Á' => 'A',
          'Â' => 'A', 'Ã' => 'A',
          'È' => 'E',
          'Ê' => 'E', 'Ë' => 'E',
          'Ì' => 'I', 'Í' => 'I',
          'Î' => 'I', 'Ï' => 'I',
          'Ð' => 'D',
          'Ò' => 'O', 'Ó' => 'O',
          'Ô' => 'O', 'Õ' => 'O',
          'Ù' => 'U',
          'Ú' => 'U', 'Û' => 'U',
          'Ý' => 'Y',
          'Þ' => 'TH',
          'á' => 'a',
          'â' => 'a', 'ã' => 'a',
          'ç' => 'c',
          'ê' => 'e', 'ë' => 'e',
          'í' => 'i',
          'î' => 'i', 'ï' => 'i',
          'ð' => 'd',
          'ó' => 'o',
          'ô' => 'o', 'õ' => 'o',
          'ú' => 'u',
          'û' => 'u',
          'ý' => 'y', 'þ' => 'th',
          'ÿ' => 'y',
          // Decompositions for Latin Extended-A
          'Ā' => 'A', 'ā' => 'a',
          'Ă' => 'A', 'ă' => 'a',
          'Ą' => 'A', 'ą' => 'a',
          'Ć' => 'C', 'ć' => 'c',
          'Ĉ' => 'C', 'ĉ' => 'c',
          'Ċ' => 'C', 'ċ' => 'c',
          'Č' => 'C', 'č' => 'c',
          'Ď' => 'D', 'ď' => 'd',
          'Đ' => 'D', 'đ' => 'd',
          'Ē' => 'E', 'ē' => 'e',
          'Ĕ' => 'E', 'ĕ' => 'e',
          'Ė' => 'E', 'ė' => 'e',
          'Ę' => 'E', 'ę' => 'e',
          'Ě' => 'E', 'ě' => 'e',
          'Ĝ' => 'G', 'ĝ' => 'g',
          'Ğ' => 'G', 'ğ' => 'g',
          'Ġ' => 'G', 'ġ' => 'g',
          'Ģ' => 'G', 'ģ' => 'g',
          'Ĥ' => 'H', 'ĥ' => 'h',
          'Ħ' => 'H', 'ħ' => 'h',
          'Ĩ' => 'I', 'ĩ' => 'i',
          'Ī' => 'I', 'ī' => 'i',
          'Ĭ' => 'I', 'ĭ' => 'i',
          'Į' => 'I', 'į' => 'i',
          'İ' => 'I', 'ı' => 'i',
          'Ĳ' => 'IJ', 'ĳ' => 'ij',
          'Ĵ' => 'J', 'ĵ' => 'j',
          'Ķ' => 'K', 'ķ' => 'k',
          'ĸ' => 'k', 'Ĺ' => 'L',
          'ĺ' => 'l', 'Ļ' => 'L',
          'ļ' => 'l', 'Ľ' => 'L',
          'ľ' => 'l', 'Ŀ' => 'L',
          'ŀ' => 'l', 'Ł' => 'L',
          'ł' => 'l', 'Ń' => 'N',
          'ń' => 'n', 'Ņ' => 'N',
          'ņ' => 'n', 'Ň' => 'N',
          'ň' => 'n', 'ŉ' => 'n',
          'Ŋ' => 'N', 'ŋ' => 'n',
          'Ō' => 'O', 'ō' => 'o',
          'Ŏ' => 'O', 'ŏ' => 'o',
          'Ő' => 'O', 'ő' => 'o',
          'Œ' => 'OE', 'œ' => 'oe',
          'Ŕ' => 'R', 'ŕ' => 'r',
          'Ŗ' => 'R', 'ŗ' => 'r',
          'Ř' => 'R', 'ř' => 'r',
          'Ś' => 'S', 'ś' => 's',
          'Ŝ' => 'S', 'ŝ' => 's',
          'Ş' => 'S', 'ş' => 's',
          'Š' => 'S', 'š' => 's',
          'Ţ' => 'T', 'ţ' => 't',
          'Ť' => 'T', 'ť' => 't',
          'Ŧ' => 'T', 'ŧ' => 't',
          'Ũ' => 'U', 'ũ' => 'u',
          'Ū' => 'U', 'ū' => 'u',
          'Ŭ' => 'U', 'ŭ' => 'u',
          'Ů' => 'U', 'ů' => 'u',
          'Ű' => 'U', 'ű' => 'u',
          'Ų' => 'U', 'ų' => 'u',
          'Ŵ' => 'W', 'ŵ' => 'w',
          'Ŷ' => 'Y', 'ŷ' => 'y',
          'Ÿ' => 'Y', 'Ź' => 'Z',
          'ź' => 'z', 'Ż' => 'Z',
          'ż' => 'z', 'Ž' => 'Z',
          'ž' => 'z', 'ſ' => 's',
          // Decompositions for Latin Extended-B
          'Ș' => 'S', 'ș' => 's',
          'Ț' => 'T', 'ț' => 't',
          // Vowels with diacritic (Vietnamese)
          // unmarked
          'Ơ' => 'O', 'ơ' => 'o',
          'Ư' => 'U', 'ư' => 'u',
          // grave accent
          'Ầ' => 'A', 'ầ' => 'a',
          'Ằ' => 'A', 'ằ' => 'a',
          'Ề' => 'E', 'ề' => 'e',
          'Ồ' => 'O', 'ồ' => 'o',
          'Ờ' => 'O', 'ờ' => 'o',
          'Ừ' => 'U', 'ừ' => 'u',
          'Ỳ' => 'Y', 'ỳ' => 'y',
          // hook
          'Ả' => 'A', 'ả' => 'a',
          'Ẩ' => 'A', 'ẩ' => 'a',
          'Ẳ' => 'A', 'ẳ' => 'a',
          'Ẻ' => 'E', 'ẻ' => 'e',
          'Ể' => 'E', 'ể' => 'e',
          'Ỉ' => 'I', 'ỉ' => 'i',
          'Ỏ' => 'O', 'ỏ' => 'o',
          'Ổ' => 'O', 'ổ' => 'o',
          'Ở' => 'O', 'ở' => 'o',
          'Ủ' => 'U', 'ủ' => 'u',
          'Ử' => 'U', 'ử' => 'u',
          'Ỷ' => 'Y', 'ỷ' => 'y',
          // tilde
          'Ẫ' => 'A', 'ẫ' => 'a',
          'Ẵ' => 'A', 'ẵ' => 'a',
          'Ẽ' => 'E', 'ẽ' => 'e',
          'Ễ' => 'E', 'ễ' => 'e',
          'Ỗ' => 'O', 'ỗ' => 'o',
          'Ỡ' => 'O', 'ỡ' => 'o',
          'Ữ' => 'U', 'ữ' => 'u',
          'Ỹ' => 'Y', 'ỹ' => 'y',
          // acute accent
          'Ấ' => 'A', 'ấ' => 'a',
          'Ắ' => 'A', 'ắ' => 'a',
          'Ế' => 'E', 'ế' => 'e',
          'Ố' => 'O', 'ố' => 'o',
          'Ớ' => 'O', 'ớ' => 'o',
          'Ứ' => 'U', 'ứ' => 'u',
          // dot below
          'Ạ' => 'A', 'ạ' => 'a',
          'Ậ' => 'A', 'ậ' => 'a',
          'Ặ' => 'A', 'ặ' => 'a',
          'Ẹ' => 'E', 'ẹ' => 'e',
          'Ệ' => 'E', 'ệ' => 'e',
          'Ị' => 'I', 'ị' => 'i',
          'Ọ' => 'O', 'ọ' => 'o',
          'Ộ' => 'O', 'ộ' => 'o',
          'Ợ' => 'O', 'ợ' => 'o',
          'Ụ' => 'U', 'ụ' => 'u',
          'Ự' => 'U', 'ự' => 'u',
          'Ỵ' => 'Y', 'ỵ' => 'y',
          // Vowels with diacritic (Chinese, Hanyu Pinyin)
          'ɑ' => 'a',
          // macron
          'Ǖ' => 'U', 'ǖ' => 'u',
          // acute accent
          'Ǘ' => 'U', 'ǘ' => 'u',
          // caron
          'Ǎ' => 'A', 'ǎ' => 'a',
          'Ǐ' => 'I', 'ǐ' => 'i',
          'Ǒ' => 'O', 'ǒ' => 'o',
          'Ǔ' => 'U', 'ǔ' => 'u',
          'Ǚ' => 'U', 'ǚ' => 'u',
          // grave accent
          'Ǜ' => 'U', 'ǜ' => 'u',
          // spaces
          ' ' => ' ', ' ' => ' ',
        ];

        $str = strtr($str, $chars);

        return $str;
    }

    /**
     * Truncated to the limit of chars allowed by number of SMS. It will detect
     * the encoding an multipart limits to apply the truncate.
     *
     * @param string $str      Message text
     * @param int    $messages Number of SMS allowed
     *
     * @return string Truncated message
     */
    public function truncate($str, $limitSms)
    {
        $count = $this->count($str);

        if ($count->messages <= $limitSms) {
            return $str;
        }

        if ($count->encoding == 'UTF16') {
            $limit = self::UTF16_LEN;

            if ($limitSms > 2) {
                $limit = self::UTF16_LEN_MULTIPART;
            }
        }

        if ($count->encoding != 'UTF16') {
            $limit = self::GSM_7BIT_LEN;

            if ($limitSms > 2) {
                $limit = self::GSM_7BIT_LEN_MULTIPART;
            }
        }

        do {
            $str = mb_substr($str, 0, $limit * $limitSms);
            $count = $this->count($str);

            $limit = $limit - 1;
        } while ($count->messages > $limitSms);

        return $str;
    }
}
