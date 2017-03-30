<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
Wedeto\Mail is published under the BSD 3-Clause License.

Wedeto\Mail\Mime\Mime was adapted from Zend\Mime\Mime.
The modifications are: Copyright 2017, Egbert van der Wal.

The original source code is copyright Zend Technologies USA Inc. The original
licence information is included below.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this
list of conditions and the following disclaimer. Redistributions in binary form
must reproduce the above copyright notice, this list of conditions and the
following disclaimer in the documentation and/or other materials provided with
the distribution. Neither the name of Zend or Rogue Wave Software, nor the
names of its contributors may be used to endorse or promote products derived
from this software without specific prior written permission. THIS SOFTWARE IS
PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
*/

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Wedeto\Mail\Mime;

/**
 * Support class for MultiPart Mime Messages
 */
class Mime
{
    const TYPE_OCTETSTREAM = 'application/octet-stream';
    const TYPE_TEXT = 'text/plain';
    const TYPE_HTML = 'text/html';
    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_QUOTEDPRINTABLE = 'quoted-printable';
    const ENCODING_BASE64 = 'base64';
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';
    const LINELENGTH = 72;
    const LINEEND = "\n";
    const MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const MULTIPART_MIXED = 'multipart/mixed';
    const MULTIPART_RELATED = 'multipart/related';
    const CHARSET_REGEX = '#=\?(?P<charset>[\x21\x23-\x26\x2a\x2b\x2d\x5e\5f\60'
                        . '\x7b-\x7ea-zA-Z0-9]+)\?(?P<encoding>[\x21\x23-\x26'
                        . '\x2a\x2b\x2d\x5e\5f\60\x7b-\x7ea-zA-Z0-9]+)\?(?P'
                        . '<text>[\x21-\x3e\x40-\x7e]+)#';

    protected $boundary;
    protected static $makeUnique = 0;

    // lookup-Tables for QuotedPrintable
    public static $qpKeys = array(
        "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07",
        "\x08", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F",
        "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17",
        "\x18", "\x19", "\x1A", "\x1B", "\x1C", "\x1D", "\x1E", "\x1F",
        "\x7F", "\x80", "\x81", "\x82", "\x83", "\x84", "\x85", "\x86",
        "\x87", "\x88", "\x89", "\x8A", "\x8B", "\x8C", "\x8D", "\x8E",
        "\x8F", "\x90", "\x91", "\x92", "\x93", "\x94", "\x95", "\x96",
        "\x97", "\x98", "\x99", "\x9A", "\x9B", "\x9C", "\x9D", "\x9E",
        "\x9F", "\xA0", "\xA1", "\xA2", "\xA3", "\xA4", "\xA5", "\xA6",
        "\xA7", "\xA8", "\xA9", "\xAA", "\xAB", "\xAC", "\xAD", "\xAE",
        "\xAF", "\xB0", "\xB1", "\xB2", "\xB3", "\xB4", "\xB5", "\xB6",
        "\xB7", "\xB8", "\xB9", "\xBA", "\xBB", "\xBC", "\xBD", "\xBE",
        "\xBF", "\xC0", "\xC1", "\xC2", "\xC3", "\xC4", "\xC5", "\xC6",
        "\xC7", "\xC8", "\xC9", "\xCA", "\xCB", "\xCC", "\xCD", "\xCE",
        "\xCF", "\xD0", "\xD1", "\xD2", "\xD3", "\xD4", "\xD5", "\xD6",
        "\xD7", "\xD8", "\xD9", "\xDA", "\xDB", "\xDC", "\xDD", "\xDE",
        "\xDF", "\xE0", "\xE1", "\xE2", "\xE3", "\xE4", "\xE5", "\xE6",
        "\xE7", "\xE8", "\xE9", "\xEA", "\xEB", "\xEC", "\xED", "\xEE",
        "\xEF", "\xF0", "\xF1", "\xF2", "\xF3", "\xF4", "\xF5", "\xF6",
        "\xF7", "\xF8", "\xF9", "\xFA", "\xFB", "\xFC", "\xFD", "\xFE",
        "\xFF"
    );

    public static $qpReplaceValues = array(
        "=00", "=01", "=02", "=03", "=04", "=05", "=06", "=07",
        "=08", "=09", "=0A", "=0B", "=0C", "=0D", "=0E", "=0F",
        "=10", "=11", "=12", "=13", "=14", "=15", "=16", "=17",
        "=18", "=19", "=1A", "=1B", "=1C", "=1D", "=1E", "=1F",
        "=7F", "=80", "=81", "=82", "=83", "=84", "=85", "=86",
        "=87", "=88", "=89", "=8A", "=8B", "=8C", "=8D", "=8E",
        "=8F", "=90", "=91", "=92", "=93", "=94", "=95", "=96",
        "=97", "=98", "=99", "=9A", "=9B", "=9C", "=9D", "=9E",
        "=9F", "=A0", "=A1", "=A2", "=A3", "=A4", "=A5", "=A6",
        "=A7", "=A8", "=A9", "=AA", "=AB", "=AC", "=AD", "=AE",
        "=AF", "=B0", "=B1", "=B2", "=B3", "=B4", "=B5", "=B6",
        "=B7", "=B8", "=B9", "=BA", "=BB", "=BC", "=BD", "=BE",
        "=BF", "=C0", "=C1", "=C2", "=C3", "=C4", "=C5", "=C6",
        "=C7", "=C8", "=C9", "=CA", "=CB", "=CC", "=CD", "=CE",
        "=CF", "=D0", "=D1", "=D2", "=D3", "=D4", "=D5", "=D6",
        "=D7", "=D8", "=D9", "=DA", "=DB", "=DC", "=DD", "=DE",
        "=DF", "=E0", "=E1", "=E2", "=E3", "=E4", "=E5", "=E6",
        "=E7", "=E8", "=E9", "=EA", "=EB", "=EC", "=ED", "=EE",
        "=EF", "=F0", "=F1", "=F2", "=F3", "=F4", "=F5", "=F6",
        "=F7", "=F8", "=F9", "=FA", "=FB", "=FC", "=FD", "=FE",
        "=FF"
    );

    public static $qpKeysString =
         "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10"
       . "\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F\x80"
       . "\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F\x90\x91"
       . "\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F\xA0\xA1\xA2"
       . "\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF\xB0\xB1\xB2\xB3"
       . "\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF\xC0\xC1\xC2\xC3\xC4"
       . "\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5"
       . "\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF\xE0\xE1\xE2\xE3\xE4\xE5\xE6"
       . "\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7"
       . "\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";

    /**
     * Check if the given string is "printable"
     *
     * Checks that a string contains no unprintable characters. If this returns
     * false, encode the string for secure delivery.
     *
     * @param string $str
     * @return bool
     */
    public static function isPrintable(string $str)
    {
        return (strcspn($str, self::$qpKeysString) == strlen($str));
    }

    /**
     * Encode a given string with the QUOTED_PRINTABLE mechanism and wrap the lines.
     *
     * @param string $str
     * @param int $lineLength Defaults to {@link LINELENGTH}
     * @param string $lineEnd Defaults to {@link LINEEND}
     * @return string
     */
    public static function encodeQuotedPrintable(string $str, int $lineLength = self::LINELENGTH, string $lineEnd = self::LINEEND)
    {
        $out = '';
        $str = self::_encodeQuotedPrintable($str);

        // Split encoded text into separate lines
        while ($str) {
            $ptr = strlen($str);
            if ($ptr > $lineLength)
                $ptr = $lineLength;

            // Try to prevent that the first character of a line is a dot
            // Outlook Bug: http://engineering.como.com/ghost-vs-outlook/
            while ($ptr > 1 && $ptr < strlen($str) && $str[$ptr] === '.')
                --$ptr;

            // Ensure we are not splitting across an encoded character
            $pos = strrpos(substr($str, 0, $ptr), '=');
            if ($pos !== false && $pos >= $ptr - 2)
                $ptr = $pos;

            // Check if there is a space at the end of the line and rewind
            if ($ptr > 0 && $str[$ptr - 1] == ' ')
                --$ptr;

            // Add string and continue
            $out .= substr($str, 0, $ptr) . '=' . $lineEnd;
            $str = substr($str, $ptr);
        }

        $out = rtrim($out, $lineEnd);
        $out = rtrim($out, '=');
        return $out;
    }

    /**
     * Converts a string into quoted printable format.
     *
     * @param  string $str
     * @return string
     */
    private static function _encodeQuotedPrintable(string $str)
    {
        $str = str_replace('=', '=3D', $str);
        $str = str_replace(self::$qpKeys, self::$qpReplaceValues, $str);
        $str = rtrim($str);
        return $str;
    }

    /**
     * Encode a given string with the QUOTED_PRINTABLE mechanism for Mail Headers.
     *
     * Mail headers depend on an extended quoted printable algorithm otherwise
     * a range of bugs can occur.
     *
     * @param string $str
     * @param string $charset
     * @param int $lineLength Defaults to {@link LINELENGTH}
     * @param string $lineEnd Defaults to {@link LINEEND}
     * @return string
     */
    public static function encodeQuotedPrintableHeader(
        string $str,
        string $charset,
        int $lineLength = self::LINELENGTH, 
        string $lineEnd = self::LINEEND
    )
    {
        // Reduce line-length by the length of the required delimiter, charsets and encoding
        $prefix = sprintf('=?%s?Q?', $charset);
        $lineLength = $lineLength - strlen($prefix) - 3;

        $str = self::_encodeQuotedPrintable($str);

        // Mail-Header required chars have to be encoded also:
        $str = str_replace(['?', ' ', '_'], ['=3F', '=20', '=5F'], $str);

        // initialize first line, we need it anyways
        $lines = [0 => ''];

        // Split encoded text into separate lines
        $tmp = '';
        while (strlen($str) > 0)
        {
            $currentLine = max(count($lines) - 1, 0);
            $token = self::getNextQuotedPrintableToken($str);
            $substr = substr($str, strlen($token));
            $str = (false === $substr) ? '' : $substr;

            $tmp .= $token;
            if ($token === '=20')
            {
                // Only if we have a single char token or space, we can append
                // the tempstring it to the current line or start a new line if
                // necessary.
                $lineLimitReached = (strlen($lines[$currentLine] . $tmp) > $lineLength);
                $noCurrentLine = ($lines[$currentLine] === '');
                if ($noCurrentLine && $lineLimitReached)
                {
                    $lines[$currentLine] = $tmp;
                    $lines[$currentLine + 1] = '';
                }
                elseif ($lineLimitReached)
                {
                    $lines[$currentLine + 1] = $tmp;
                }
                else
                {
                    $lines[$currentLine] .= $tmp;
                }
                $tmp = '';
            }

            // Don't forget to append the rest to the last line
            if (strlen($str) === 0)
                $lines[$currentLine] .= $tmp;
        }

        // Assemble the lines together by pre- and appending delimiters,
        // charset, encoding.
        for ($i = 0, $count = count($lines); $i < $count; ++$i)
            $lines[$i] = " " . $prefix . $lines[$i] . "?=";
        $str = trim(implode($lineEnd, $lines));
        return $str;
    }

    /**
     * Retrieves the first token from a quoted printable string.
     *
     * @param string $str The string to get the next token from
     * @return string The first token
     */
    private static function getNextQuotedPrintableToken(string $str)
    {
        if (substr($str, 0, 1) === "=")
            $token = substr($str, 0, 3);
        else
            $token = substr($str, 0, 1);

        return $token;
    }

    /**
     * Encode a given string in mail header compatible base64 encoding.
     *
     * @param string $str
     * @param string $charset
     * @param int $lineLength Defaults to {@link LINELENGTH}
     * @param string $lineEnd Defaults to {@link LINEEND}
     * @return string
     */
    public static function encodeBase64Header($str, $charset, $lineLength = self::LINELENGTH, $lineEnd = self::LINEEND)
    {
        $prefix = '=?' . $charset . '?B?';
        $suffix = '?=';
        $remainingLength = $lineLength - strlen($prefix) - strlen($suffix);

        $encodedValue = self::encodeBase64($str, $remainingLength, $lineEnd);
        $encodedValue = str_replace($lineEnd, $suffix . $lineEnd . ' ' . $prefix, $encodedValue);
        $encodedValue = $prefix . $encodedValue . $suffix;
        return $encodedValue;
    }

    /**
     * Encode a given string in base64 encoding and break lines
     * according to the maximum linelength.
     *
     * @param string $str
     * @param int $lineLength Defaults to {@link LINELENGTH}
     * @param string $lineEnd Defaults to {@link LINEEND}
     * @return string The encoded and wrapped string
     */
    public static function encodeBase64($str, $lineLength = self::LINELENGTH, $lineEnd = self::LINEEND)
    {
        $lineLength = $lineLength - ($lineLength % 4);
        return rtrim(chunk_split(base64_encode($str), $lineLength, $lineEnd));
    }

    /**
     * Create a new Mime encoding utility instance
     *
     * @param string $boundary
     */
    public function __construct($boundary = "")
    {
        // This string needs to be somewhat unique
        if (empty($boundary))
            $this->boundary = '=_' . md5(microtime(1) . self::$makeUnique++);
        else
            $this->boundary = $boundary;
    }

    /**
     * Encode the given string with the given encoding.
     *
     * @param string $str The string to encode
     * @param string $encoding The encoding to use
     * @param string $EOL EOL string; defaults to \r\n
     * @return string The encoded string
     */
    public static function encode(string $str, string $encoding, string $EOL = self::LINEEND)
    {
        switch ($encoding)
        {
            case self::ENCODING_BASE64:
                return self::encodeBase64($str, self::LINELENGTH, $EOL);
            case self::ENCODING_QUOTEDPRINTABLE:
                return self::encodeQuotedPrintable($str, self::LINELENGTH, $EOL);
            default:
                return $str;
        }
    }

    /**
     * Return the MIME boundary
     *
     * @return string The MIME boundary
     */
    public function boundary()
    {
        return $this->boundary;
    }

    /**
     * Return a MIME boundary line
     *
     * @param string $EOL Defaults to \r\n
     * @return string The MIME starting delimiter
     */
    public function boundaryLine(string $EOL = self::LINEEND)
    {
        return $EOL . '--' . $this->boundary . $EOL;
    }

    /**
     * Return MIME ending
     *
     * @param string $EOL Defaults to \r\n
     * @return string The MIME end delimiter
     */
    public function mimeEnd(string $EOL = self::LINEEND)
    {
        return $EOL . '--' . $this->boundary . '--' . $EOL;
    }

    /**
     * Detect MIME charset
     *
     * Extract parts according to https://tools.ietf.org/html/rfc2047#section-2
     *
     * @param string $str The string to perform detection on
     * @return string The mime charset
     */
    public static function mimeDetectCharset(string $str)
    {
        if (preg_match(self::CHARSET_REGEX, $str, $matches))
            return strtoupper($matches['charset']);

        return 'ASCII';
    }
}
