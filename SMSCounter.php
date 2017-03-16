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

        $messages = ceil($length / $perMessage);
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
            $text = utf8ToUnicode($text);
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
          chr(195).chr(128) => 'A',
          chr(195).chr(129) => 'A',
          chr(195).chr(130) => 'A',
          chr(195).chr(131) => 'A',
          chr(195).chr(132) => 'A',
          chr(195).chr(133) => 'A',
          // chr(195).chr(135) => 'C', // Ç
          chr(195).chr(136) => 'E',
          chr(195).chr(137) => 'E',
          chr(195).chr(138) => 'E',
          chr(195).chr(139) => 'E',
          chr(195).chr(140) => 'I',
          chr(195).chr(141) => 'I',
          chr(195).chr(142) => 'I',
          chr(195).chr(143) => 'I',
          // chr(195).chr(145) => 'N', // Ñ
          chr(195).chr(146) => 'O',
          chr(195).chr(147) => 'O',
          chr(195).chr(148) => 'O',
          chr(195).chr(149) => 'O',
          chr(195).chr(150) => 'O',
          chr(195).chr(153) => 'U',
          chr(195).chr(154) => 'U',
          chr(195).chr(155) => 'U',
          chr(195).chr(156) => 'U',
          chr(195).chr(157) => 'Y',
          chr(195).chr(159) => 's',
          // chr(195).chr(160) => 'a',
          chr(195).chr(161) => 'a',
          chr(195).chr(162) => 'a',
          chr(195).chr(163) => 'a',
          chr(195).chr(164) => 'a',
          chr(195).chr(165) => 'a',
          // chr(195).chr(167) => 'c', // ç
          chr(195).chr(168) => 'e',
          chr(195).chr(169) => 'e',
          chr(195).chr(170) => 'e',
          chr(195).chr(171) => 'e',
          chr(195).chr(172) => 'i',
          chr(195).chr(173) => 'i',
          chr(195).chr(174) => 'i',
          chr(195).chr(175) => 'i',
          // chr(195).chr(177) => 'n', // ñ
          chr(195).chr(178) => 'o',
          chr(195).chr(179) => 'o',
          chr(195).chr(180) => 'o',
          chr(195).chr(181) => 'o',
          chr(195).chr(182) => 'o',
          chr(195).chr(182) => 'o',
          chr(195).chr(185) => 'u',
          chr(195).chr(186) => 'u',
          chr(195).chr(187) => 'u',
          chr(195).chr(188) => 'u',
          chr(195).chr(189) => 'y',
          chr(195).chr(191) => 'y',
          // Decompositions for Latin Extended-A
          chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
          chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
          chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
          chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
          chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
          chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
          chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
          chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
          chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
          chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
          chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
          chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
          chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
          chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
          chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
          chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
          chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
          chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
          chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
          chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
          chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
          chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
          chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
          chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
          chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
          chr(196).chr(178) => 'IJ', chr(196).chr(179) => 'ij',
          chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
          chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
          chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
          chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
          chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
          chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
          chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
          chr(197).chr(130) => 'l',
          chr(197).chr(131) => 'N', // Ń
          chr(197).chr(132) => 'n', // ń
          chr(197).chr(133) => 'N', // Ņ
          chr(197).chr(134) => 'n', // ņ
          chr(197).chr(135) => 'N',
          chr(197).chr(136) => 'n',
          chr(197).chr(137) => 'N',
          chr(197).chr(138) => 'n',
          chr(197).chr(139) => 'N',
          chr(197).chr(140) => 'O',
          chr(197).chr(141) => 'o',
          chr(197).chr(142) => 'O',
          chr(197).chr(143) => 'o',
          chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
          chr(197).chr(146) => 'OE', chr(197).chr(147) => 'oe',
          chr(197).chr(148) => 'R', chr(197).chr(149) => 'r',
          chr(197).chr(150) => 'R', chr(197).chr(151) => 'r',
          chr(197).chr(152) => 'R', chr(197).chr(153) => 'r',
          chr(197).chr(154) => 'S', chr(197).chr(155) => 's',
          chr(197).chr(156) => 'S', chr(197).chr(157) => 's',
          chr(197).chr(158) => 'S', chr(197).chr(159) => 's',
          chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
          chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
          chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
          chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
          chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
          chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
          chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
          chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
          chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
          chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
          chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
          chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
          chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
          chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
          chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
          chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
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
