<?php

namespace TPT\ERP\Core;

/**
 * Encryption Utilities
 *
 * Provides secure encryption/decryption methods for sensitive data.
 */
class Encryption
{
    private static string $key;
    private static string $cipher = 'aes-256-gcm';
    private static int $keyLength = 32; // 256 bits

    /**
     * Initialize encryption with key
     */
    public static function init(string $key = null): void
    {
        if ($key === null) {
            $key = getenv('ENCRYPTION_KEY') ?: self::generateKey();
        }

        self::$key = substr(hash('sha256', $key, true), 0, self::$keyLength);
    }

    /**
     * Encrypt data
     */
    public static function encrypt(string $data): string
    {
        if (!isset(self::$key)) {
            self::init();
        }

        $iv = random_bytes(openssl_cipher_iv_length(self::$cipher));
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($encrypted === false) {
            throw new \Exception('Encryption failed');
        }

        // Combine IV, tag, and encrypted data
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt data
     */
    public static function decrypt(string $encryptedData): string
    {
        if (!isset(self::$key)) {
            self::init();
        }

        $data = base64_decode($encryptedData);

        if ($data === false) {
            throw new \Exception('Invalid encrypted data');
        }

        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $tagLength = 16;

        if (strlen($data) < $ivLength + $tagLength) {
            throw new \Exception('Invalid encrypted data length');
        }

        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, $tagLength);
        $encrypted = substr($data, $ivLength + $tagLength);

        $decrypted = openssl_decrypt(
            $encrypted,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \Exception('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * Hash data using Argon2
     */
    public static function hash(string $data): string
    {
        return password_hash($data, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    /**
     * Verify hash
     */
    public static function verify(string $data, string $hash): bool
    {
        return password_verify($data, $hash);
    }

    /**
     * Generate secure random key
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(self::$keyLength));
    }

    /**
     * Generate secure token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash sensitive data for storage (one-way)
     */
    public static function hashSensitive(string $data): string
    {
        return hash('sha256', $data . getenv('HASH_SALT', 'default-salt'));
    }

    /**
     * Encrypt file
     */
    public static function encryptFile(string $inputFile, string $outputFile): void
    {
        if (!file_exists($inputFile)) {
            throw new \Exception('Input file does not exist');
        }

        $data = file_get_contents($inputFile);
        if ($data === false) {
            throw new \Exception('Failed to read input file');
        }

        $encrypted = self::encrypt($data);

        if (file_put_contents($outputFile, $encrypted) === false) {
            throw new \Exception('Failed to write encrypted file');
        }
    }

    /**
     * Decrypt file
     */
    public static function decryptFile(string $inputFile, string $outputFile): void
    {
        if (!file_exists($inputFile)) {
            throw new \Exception('Input file does not exist');
        }

        $encrypted = file_get_contents($inputFile);
        if ($encrypted === false) {
            throw new \Exception('Failed to read encrypted file');
        }

        $decrypted = self::decrypt($encrypted);

        if (file_put_contents($outputFile, $decrypted) === false) {
            throw new \Exception('Failed to write decrypted file');
        }
    }

    /**
     * Securely erase file
     */
    public static function secureErase(string $file): void
    {
        if (!file_exists($file)) {
            return;
        }

        $size = filesize($file);

        // Overwrite with random data multiple times
        for ($i = 0; $i < 3; $i++) {
            $random = random_bytes($size);
            file_put_contents($file, $random);
        }

        // Finally overwrite with zeros
        file_put_contents($file, str_repeat("\0", $size));

        unlink($file);
    }

    /**
     * Generate HMAC for data integrity
     */
    public static function generateHMAC(string $data, string $key = null): string
    {
        $key = $key ?: self::$key ?? self::generateKey();
        return hash_hmac('sha256', $data, $key);
    }

    /**
     * Verify HMAC
     */
    public static function verifyHMAC(string $data, string $hmac, string $key = null): bool
    {
        $expected = self::generateHMAC($data, $key);
        return hash_equals($expected, $hmac);
    }

    /**
     * Encrypt data with expiration
     */
    public static function encryptWithExpiration(string $data, int $expirationTime): string
    {
        $payload = json_encode([
            'data' => $data,
            'expires' => time() + $expirationTime
        ]);

        return self::encrypt($payload);
    }

    /**
     * Decrypt data with expiration check
     */
    public static function decryptWithExpiration(string $encryptedData): ?string
    {
        try {
            $payload = self::decrypt($encryptedData);
            $data = json_decode($payload, true);

            if (!$data || !isset($data['expires']) || !isset($data['data'])) {
                return null;
            }

            if (time() > $data['expires']) {
                return null; // Expired
            }

            return $data['data'];
        } catch (\Exception $e) {
            return null;
        }
    }
}
