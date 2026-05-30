<?php

namespace App\Services;

class TOTPService
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        $randomBytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[ord($randomBytes[$i]) & 31];
        }
        return $secret;
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = (int) floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::compute($secret, $timestamp + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function compute(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        $msg = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $otp = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % (10 ** self::DIGITS);
        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $input): string
    {
        $input = strtoupper($input);
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos(self::BASE32_CHARS, $input[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }
        return $output;
    }
}
