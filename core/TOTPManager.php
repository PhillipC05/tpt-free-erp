<?php

namespace TPT\ERP\Core;

/**
 * TOTP (Time-based One-Time Password) Manager
 *
 * Handles TOTP authentication for authenticator apps like Google Authenticator, Authy, etc.
 */
class TOTPManager
{
    private const SECRET_LENGTH = 32;
    private const CODE_LENGTH = 6;
    private const TIME_STEP = 30; // 30 seconds
    private const WINDOW = 1; // Allow 1 time step tolerance

    /**
     * Generate a new TOTP secret
     */
    public function generateSecret(): string
    {
        return bin2hex(random_bytes(self::SECRET_LENGTH));
    }

    /**
     * Generate TOTP URI for QR code
     */
    public function generateTOTPURI(string $secret, string $accountName, string $issuer = 'TPT ERP'): string
    {
        $parameters = [
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::CODE_LENGTH,
            'period' => self::TIME_STEP
        ];

        $queryString = http_build_query($parameters);
        $encodedAccountName = rawurlencode($accountName);

        return "otpauth://totp/{$encodedAccountName}?{$queryString}";
    }

    /**
     * Generate QR code data URL
     */
    public function generateQRCodeDataURL(string $secret, string $accountName, string $issuer = 'TPT ERP'): string
    {
        $uri = $this->generateTOTPURI($secret, $accountName, $issuer);

        // Generate QR code using a simple approach (you might want to use a QR library)
        return $this->generateQRCodeFromURI($uri);
    }

    /**
     * Verify TOTP code
     */
    public function verifyCode(string $secret, string $code): bool
    {
        if (strlen($code) !== self::CODE_LENGTH || !ctype_digit($code)) {
            return false;
        }

        $currentTime = time();
        $secretBytes = $this->base32Decode($secret);

        // Check current time window and adjacent windows for tolerance
        for ($i = -$this::WINDOW; $i <= $this::WINDOW; $i++) {
            $time = $currentTime + ($i * self::TIME_STEP);
            $timeCounter = floor($time / self::TIME_STEP);

            if ($this->generateCode($secretBytes, $timeCounter) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate TOTP code for a given time counter
     */
    private function generateCode(string $secret, int $timeCounter): string
    {
        // Pack time counter as 8-byte big-endian
        $timeBytes = pack('J', $timeCounter);

        // Generate HMAC-SHA1
        $hmac = hash_hmac('sha1', $timeBytes, $secret, true);

        // Get offset from last byte
        $offset = ord($hmac[19]) & 0x0F;

        // Extract 4 bytes starting from offset
        $codeBytes = substr($hmac, $offset, 4);
        $codeInt = unpack('N', $codeBytes)[1];

        // Get 6-digit code
        $code = $codeInt & 0x7FFFFFFF; // Remove sign bit
        $code = $code % (10 ** self::CODE_LENGTH);

        return str_pad((string) $code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Enable TOTP for user
     */
    public function enableTOTP(int $userId, string $secret, string $verificationCode): bool
    {
        // Verify the code first
        if (!$this->verifyCode($secret, $verificationCode)) {
            return false;
        }

        $db = Database::getInstance();

        // Store TOTP secret (encrypted)
        $encryptedSecret = Encryption::encrypt($secret);

        $db->insert('user_auth_methods', [
            'user_id' => $userId,
            'method_type' => 'totp',
            'method_data' => json_encode([
                'secret' => $encryptedSecret,
                'enabled_at' => date('Y-m-d H:i:s'),
                'backup_codes_generated' => false
            ]),
            'is_enabled' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Generate backup codes
        $this->generateBackupCodes($userId);

        // Log the event
        $this->logAuthEvent($userId, 'totp_enabled', 'TOTP authentication enabled');

        return true;
    }

    /**
     * Disable TOTP for user
     */
    public function disableTOTP(int $userId): bool
    {
        $db = Database::getInstance();

        $result = $db->execute(
            "UPDATE user_auth_methods SET is_enabled = false WHERE user_id = ? AND method_type = 'totp'",
            [$userId]
        );

        if ($result > 0) {
            $this->logAuthEvent($userId, 'totp_disabled', 'TOTP authentication disabled');
        }

        return $result > 0;
    }

    /**
     * Check if TOTP is enabled for user
     */
    public function isTOTPEnabled(int $userId): bool
    {
        $db = Database::getInstance();

        $method = $db->queryOne(
            "SELECT id FROM user_auth_methods WHERE user_id = ? AND method_type = 'totp' AND is_enabled = true",
            [$userId]
        );

        return $method !== null;
    }

    /**
     * Get TOTP setup data for user
     */
    public function getTOTPSetupData(int $userId): ?array
    {
        $db = Database::getInstance();

        $user = $db->find('users', $userId);
        if (!$user) {
            return null;
        }

        $secret = $this->generateSecret();
        $accountName = $user['email'];
        $issuer = 'TPT ERP';

        return [
            'secret' => $secret,
            'qr_code_uri' => $this->generateTOTPURI($secret, $accountName, $issuer),
            'manual_entry' => $secret,
            'account_name' => $accountName,
            'issuer' => $issuer
        ];
    }

    /**
     * Validate TOTP code for user
     */
    public function validateUserCode(int $userId, string $code): bool
    {
        $db = Database::getInstance();

        $method = $db->queryOne(
            "SELECT method_data FROM user_auth_methods WHERE user_id = ? AND method_type = 'totp' AND is_enabled = true",
            [$userId]
        );

        if (!$method) {
            return false;
        }

        $methodData = json_decode($method['method_data'], true);
        if (!$methodData || !isset($methodData['secret'])) {
            return false;
        }

        $secret = Encryption::decrypt($methodData['secret']);

        $isValid = $this->verifyCode($secret, $code);

        if ($isValid) {
            // Update last used timestamp
            $db->execute(
                "UPDATE user_auth_methods SET last_used_at = ? WHERE user_id = ? AND method_type = 'totp'",
                [date('Y-m-d H:i:s'), $userId]
            );
        }

        return $isValid;
    }

    /**
     * Generate backup codes for user
     */
    private function generateBackupCodes(int $userId): void
    {
        $db = Database::getInstance();

        // Generate 10 backup codes
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $code = sprintf('%08d', random_int(10000000, 99999999));
            $codes[] = $code;
        }

        // Store hashed codes
        foreach ($codes as $code) {
            $db->insert('auth_backup_codes', [
                'user_id' => $userId,
                'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                'is_used' => false,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Mark backup codes as generated
        $db->execute(
            "UPDATE user_auth_methods SET method_data = jsonb_set(method_data, '{backup_codes_generated}', 'true') WHERE user_id = ? AND method_type = 'totp'",
            [$userId]
        );
    }

    /**
     * Get backup codes for user (one-time display)
     */
    public function getBackupCodes(int $userId): array
    {
        $db = Database::getInstance();

        $codes = $db->query(
            "SELECT id, code_hash FROM auth_backup_codes WHERE user_id = ? AND is_used = false ORDER BY created_at DESC",
            [$userId]
        );

        return array_column($codes, 'code_hash');
    }

    /**
     * Use backup code
     */
    public function useBackupCode(int $userId, string $code): bool
    {
        $db = Database::getInstance();

        $backupCode = $db->queryOne(
            "SELECT id FROM auth_backup_codes WHERE user_id = ? AND is_used = false",
            [$userId]
        );

        if (!$backupCode) {
            return false;
        }

        // Check all unused codes for this user
        $codes = $db->query(
            "SELECT id, code_hash FROM auth_backup_codes WHERE user_id = ? AND is_used = false",
            [$userId]
        );

        foreach ($codes as $backupCode) {
            if (password_verify($code, $backupCode['code_hash'])) {
                // Mark code as used
                $db->update('auth_backup_codes', [
                    'is_used' => true,
                    'used_at' => date('Y-m-d H:i:s')
                ], ['id' => $backupCode['id']]);

                $this->logAuthEvent($userId, 'backup_code_used', 'Backup code used for authentication');
                return true;
            }
        }

        return false;
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(int $userId): bool
    {
        $db = Database::getInstance();

        // Mark all existing codes as used
        $db->execute(
            "UPDATE auth_backup_codes SET is_used = true, used_at = ? WHERE user_id = ? AND is_used = false",
            [date('Y-m-d H:i:s'), $userId]
        );

        // Generate new codes
        $this->generateBackupCodes($userId);

        $this->logAuthEvent($userId, 'backup_codes_regenerated', 'Backup codes regenerated');

        return true;
    }

    /**
     * Get remaining backup codes count
     */
    public function getRemainingBackupCodesCount(int $userId): int
    {
        $db = Database::getInstance();

        return $db->queryValue(
            "SELECT COUNT(*) FROM auth_backup_codes WHERE user_id = ? AND is_used = false",
            [$userId]
        );
    }

    /**
     * Base32 decode (for TOTP secrets)
     */
    private function base32Decode(string $base32): string
    {
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';

        $input = strtoupper($base32);
        $input = str_replace('=', '', $input); // Remove padding

        $bits = '';
        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $value = strpos($base32Chars, $char);

            if ($value === false) {
                throw new \Exception('Invalid base32 character: ' . $char);
            }

            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        // Convert bits to bytes
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $byte = substr($bits, $i, 8);
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }

    /**
     * Generate QR code data URL (simple implementation)
     */
    private function generateQRCodeFromURI(string $uri): string
    {
        // For a production system, you'd use a proper QR code library
        // This is a placeholder that returns a data URL
        // You can integrate with libraries like bacon/bacon-qr-code

        // Placeholder implementation - in production, use a real QR library
        return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==";
    }

    /**
     * Log authentication event
     */
    private function logAuthEvent(int $userId, string $event, string $description): void
    {
        $db = Database::getInstance();

        $db->insert('audit_log', [
            'user_id' => $userId,
            'action' => $event,
            'table_name' => 'user_auth_methods',
            'record_id' => $userId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
