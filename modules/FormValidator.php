<?php
/**
 * TPT Free ERP - Form Validator
 * Handles form validation logic
 */

class FormValidator {
    private $db;
    private $user;

    public function __construct() {
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Validate form data
     */
    public function validateFormData($formId, $data) {
        $errors = [];
        $isValid = true;

        // Get form fields
        $fields = $this->db->query("
            SELECT * FROM form_fields
            WHERE form_id = ? ORDER BY field_order ASC
        ", [$formId]);

        foreach ($fields as $field) {
            $fieldName = $field['field_name'];
            $fieldValue = $data[$fieldName] ?? null;

            // Check required fields
            if ($field['is_required'] && $this->isEmpty($fieldValue)) {
                $errors[$fieldName] = $this->getRequiredFieldMessage($field);
                $isValid = false;
                continue;
            }

            // Skip validation if field is empty and not required
            if ($this->isEmpty($fieldValue)) {
                continue;
            }

            // Apply validation rules
            $validationRules = json_decode($field['validation_rules'], true);
            if ($validationRules) {
                foreach ($validationRules as $rule => $params) {
                    if (!$this->validateRule($fieldValue, $rule, $params, $field)) {
                        $errors[$fieldName] = $this->getValidationErrorMessage($rule, $params, $field);
                        $isValid = false;
                        break;
                    }
                }
            }
        }

        return [
            'valid' => $isValid,
            'errors' => $errors
        ];
    }

    /**
     * Validate single field
     */
    public function validateField($fieldValue, $rules) {
        $errors = [];
        $isValid = true;

        foreach ($rules as $rule => $params) {
            if (!$this->validateRule($fieldValue, $rule, $params)) {
                $errors[] = $this->getValidationErrorMessage($rule, $params);
                $isValid = false;
            }
        }

        return [
            'valid' => $isValid,
            'errors' => $errors
        ];
    }

    /**
     * Validate single rule
     */
    public function validateRule($value, $rule, $params = [], $field = null) {
        switch ($rule) {
            case 'required':
                return !$this->isEmpty($value);

            case 'email':
                return $this->validateEmail($value);

            case 'url':
                return $this->validateUrl($value);

            case 'numeric':
                return $this->validateNumeric($value);

            case 'integer':
                return $this->validateInteger($value);

            case 'float':
                return $this->validateFloat($value);

            case 'alphanumeric':
                return $this->validateAlphanumeric($value);

            case 'alpha':
                return $this->validateAlpha($value);

            case 'min_length':
                return $this->validateMinLength($value, $params);

            case 'max_length':
                return $this->validateMaxLength($value, $params);

            case 'exact_length':
                return $this->validateExactLength($value, $params);

            case 'pattern':
                return $this->validatePattern($value, $params);

            case 'regex':
                return $this->validateRegex($value, $params);

            case 'date':
                return $this->validateDate($value, $params);

            case 'date_range':
                return $this->validateDateRange($value, $params);

            case 'time':
                return $this->validateTime($value, $params);

            case 'datetime':
                return $this->validateDateTime($value, $params);

            case 'future_date':
                return $this->validateFutureDate($value);

            case 'past_date':
                return $this->validatePastDate($value);

            case 'age_range':
                return $this->validateAgeRange($value, $params);

            case 'min_value':
                return $this->validateMinValue($value, $params);

            case 'max_value':
                return $this->validateMaxValue($value, $params);

            case 'range':
                return $this->validateRange($value, $params);

            case 'credit_card':
                return $this->validateCreditCard($value);

            case 'ssn':
                return $this->validateSSN($value);

            case 'tax_id':
                return $this->validateTaxId($value);

            case 'phone':
                return $this->validatePhone($value, $params);

            case 'postal_code':
                return $this->validatePostalCode($value, $params);

            case 'ip_address':
                return $this->validateIPAddress($value);

            case 'mac_address':
                return $this->validateMACAddress($value);

            case 'json':
                return $this->validateJSON($value);

            case 'xml':
                return $this->validateXML($value);

            case 'base64':
                return $this->validateBase64($value);

            case 'hex_color':
                return $this->validateHexColor($value);

            case 'rgb_color':
                return $this->validateRGBColor($value);

            case 'hsl_color':
                return $this->validateHSLColor($value);

            case 'currency':
                return $this->validateCurrency($value, $params);

            case 'percentage':
                return $this->validatePercentage($value, $params);

            case 'rating':
                return $this->validateRating($value, $params);

            case 'likert_scale':
                return $this->validateLikertScale($value, $params);

            case 'nps_score':
                return $this->validateNPSScore($value);

            case 'signature':
                return $this->validateSignature($value);

            case 'barcode':
                return $this->validateBarcode($value, $params);

            case 'qr_code':
                return $this->validateQRCode($value);

            case 'timezone':
                return $this->validateTimezone($value);

            case 'language_code':
                return $this->validateLanguageCode($value);

            case 'country_code':
                return $this->validateCountryCode($value);

            case 'coordinate':
                return $this->validateCoordinate($value, $params);

            case 'file_size':
                return $this->validateFileSize($value, $params);

            case 'file_type':
                return $this->validateFileType($value, $params);

            case 'image_dimensions':
                return $this->validateImageDimensions($value, $params);

            case 'unique':
                return $this->validateUnique($value, $params, $field);

            case 'exists':
                return $this->validateExists($value, $params);

            case 'custom':
                return $this->validateCustom($value, $params);

            default:
                return true;
        }
    }

    /**
     * Check if value is empty
     */
    private function isEmpty($value) {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

    /**
     * Validate email
     */
    private function validateEmail($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    private function validateUrl($value) {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate numeric
     */
    private function validateNumeric($value) {
        return is_numeric($value);
    }

    /**
     * Validate integer
     */
    private function validateInteger($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate float
     */
    private function validateFloat($value) {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Validate alphanumeric
     */
    private function validateAlphanumeric($value) {
        return ctype_alnum($value);
    }

    /**
     * Validate alpha
     */
    private function validateAlpha($value) {
        return ctype_alpha($value);
    }

    /**
     * Validate minimum length
     */
    private function validateMinLength($value, $params) {
        $length = $params['length'] ?? 0;
        return strlen($value) >= $length;
    }

    /**
     * Validate maximum length
     */
    private function validateMaxLength($value, $params) {
        $length = $params['length'] ?? PHP_INT_MAX;
        return strlen($value) <= $length;
    }

    /**
     * Validate exact length
     */
    private function validateExactLength($value, $params) {
        $length = $params['length'] ?? 0;
        return strlen($value) === $length;
    }

    /**
     * Validate pattern
     */
    private function validatePattern($value, $params) {
        $pattern = $params['pattern'] ?? '';
        return preg_match($pattern, $value);
    }

    /**
     * Validate regex
     */
    private function validateRegex($value, $params) {
        $pattern = $params['pattern'] ?? '';
        $flags = $params['flags'] ?? '';
        return preg_match('/' . $pattern . '/' . $flags, $value);
    }

    /**
     * Validate date
     */
    private function validateDate($value, $params) {
        $format = $params['format'] ?? 'Y-m-d';
        $date = DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }

    /**
     * Validate date range
     */
    private function validateDateRange($value, $params) {
        $date = strtotime($value);
        $minDate = strtotime($params['min_date'] ?? '1900-01-01');
        $maxDate = strtotime($params['max_date'] ?? '2100-12-31');
        return $date >= $minDate && $date <= $maxDate;
    }

    /**
     * Validate time
     */
    private function validateTime($value, $params) {
        $format = $params['format'] ?? 'H:i:s';
        $date = DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }

    /**
     * Validate datetime
     */
    private function validateDateTime($value, $params) {
        $format = $params['format'] ?? 'Y-m-d H:i:s';
        $date = DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }

    /**
     * Validate future date
     */
    private function validateFutureDate($value) {
        return strtotime($value) > time();
    }

    /**
     * Validate past date
     */
    private function validatePastDate($value) {
        return strtotime($value) < time();
    }

    /**
     * Validate age range
     */
    private function validateAgeRange($value, $params) {
        $birthDate = strtotime($value);
        $age = (time() - $birthDate) / (365.25 * 24 * 60 * 60);
        return $age >= ($params['min_age'] ?? 0) && $age <= ($params['max_age'] ?? 150);
    }

    /**
     * Validate minimum value
     */
    private function validateMinValue($value, $params) {
        return is_numeric($value) && $value >= ($params['value'] ?? 0);
    }

    /**
     * Validate maximum value
     */
    private function validateMaxValue($value, $params) {
        return is_numeric($value) && $value <= ($params['value'] ?? PHP_INT_MAX);
    }

    /**
     * Validate range
     */
    private function validateRange($value, $params) {
        return is_numeric($value) &&
               $value >= ($params['min'] ?? 0) &&
               $value <= ($params['max'] ?? PHP_INT_MAX);
    }

    /**
     * Validate credit card
     */
    private function validateCreditCard($value) {
        // Remove spaces and dashes
        $value = preg_replace('/\s+|-/', '', $value);

        // Check if it's all digits
        if (!preg_match('/^\d+$/', $value)) {
            return false;
        }

        // Check length
        $length = strlen($value);
        if ($length < 13 || $length > 19) {
            return false;
        }

        // Luhn algorithm
        $sum = 0;
        $shouldDouble = false;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int)$value[$i];

            if ($shouldDouble) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $shouldDouble = !$shouldDouble;
        }

        return $sum % 10 === 0;
    }

    /**
     * Validate SSN
     */
    private function validateSSN($value) {
        return preg_match('/^\d{3}-\d{2}-\d{4}$/', $value);
    }

    /**
     * Validate tax ID
     */
    private function validateTaxId($value) {
        return preg_match('/^\d{2}-\d{7}$/', $value); // EIN format
    }

    /**
     * Validate phone
     */
    private function validatePhone($value, $params) {
        $country = $params['country'] ?? 'US';
        $value = preg_replace('/\s+|-|\(|\)/', '', $value);

        switch (strtoupper($country)) {
            case 'US':
                return preg_match('/^\+?1?\d{10}$/', $value);
            case 'CA':
                return preg_match('/^\+?1?\d{10}$/', $value);
            case 'UK':
                return preg_match('/^\+?44\d{10}$/', $value);
            case 'AU':
                return preg_match('/^\+?61\d{9}$/', $value);
            case 'DE':
                return preg_match('/^\+?49\d{10,11}$/', $value);
            case 'FR':
                return preg_match('/^\+?33\d{9}$/', $value);
            default:
                return preg_match('/^\+?\d{7,15}$/', $value); // Generic international format
        }
    }

    /**
     * Validate postal code
     */
    private function validatePostalCode($value, $params) {
        $country = $params['country'] ?? 'US';
        $value = trim($value);

        switch (strtoupper($country)) {
            case 'US':
                return preg_match('/^\d{5}(-\d{4})?$/', $value);
            case 'CA':
                return preg_match('/^[A-Za-z]\d[A-Za-z] ?\d[A-Za-z]\d$/', $value);
            case 'UK':
                return preg_match('/^[A-Za-z]{1,2}\d[A-Za-z\d]? ?\d[A-Za-z]{2}$/', $value);
            case 'AU':
                return preg_match('/^\d{4}$/', $value);
            case 'DE':
                return preg_match('/^\d{5}$/', $value);
            case 'FR':
                return preg_match('/^\d{5}$/', $value);
            default:
                return true; // Allow any format for other countries
        }
    }

    /**
     * Validate IP address
     */
    private function validateIPAddress($value) {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate MAC address
     */
    private function validateMACAddress($value) {
        return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value);
    }

    /**
     * Validate JSON
     */
    private function validateJSON($value) {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate XML
     */
    private function validateXML($value) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($value);
        return $xml !== false;
    }

    /**
     * Validate base64
     */
    private function validateBase64($value) {
        return base64_decode($value, true) !== false;
    }

    /**
     * Validate hex color
     */
    private function validateHexColor($value) {
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value);
    }

    /**
     * Validate RGB color
     */
    private function validateRGBColor($value) {
        return preg_match('/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/', $value);
    }

    /**
     * Validate HSL color
     */
    private function validateHSLColor($value) {
        return preg_match('/^hsl\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*\)$/', $value);
    }

    /**
     * Validate currency
     */
    private function validateCurrency($value, $params) {
        $currency = $params['currency'] ?? 'USD';
        $locale = $params['locale'] ?? 'en_US';

        // This is a simplified check - in a real implementation,
        // you would use NumberFormatter or similar
        $value = trim($value);

        // Remove currency symbols and formatting
        $value = preg_replace('/[^\d.,-]/', '', $value);

        // Check if it's a valid number
        return is_numeric(str_replace([',', ' '], ['', ''], $value));
    }

    /**
     * Validate percentage
     */
    private function validatePercentage($value, $params) {
        $num = floatval($value);
        return $num >= ($params['min'] ?? 0) && $num <= ($params['max'] ?? 100);
    }

    /**
     * Validate rating
     */
    private function validateRating($value, $params) {
        $num = floatval($value);
        $min = $params['min'] ?? 1;
        $max = $params['max'] ?? 5;
        $allowHalf = $params['allow_half'] ?? false;

        if ($allowHalf) {
            return $num >= $min && $num <= $max && ($num * 2) == round($num * 2);
        }

        return $num >= $min && $num <= $max && $num == round($num);
    }

    /**
     * Validate Likert scale
     */
    private function validateLikertScale($value, $params) {
        $scalePoints = $params['scale_points'] ?? 5;
        $naAllowed = $params['na_allowed'] ?? false;
        $num = intval($value);

        if ($naAllowed && $value === 'NA') {
            return true;
        }

        return $num >= 1 && $num <= $scalePoints;
    }

    /**
     * Validate NPS score
     */
    private function validateNPSScore($value) {
        $num = intval($value);
        return $num >= 0 && $num <= 10;
    }

    /**
     * Validate signature
     */
    private function validateSignature($value) {
        return !empty($value) && strlen($value) > 100; // Basic signature check
    }

    /**
     * Validate barcode
     */
    private function validateBarcode($value, $params) {
        $type = $params['type'] ?? 'any';
        $value = trim($value);

        switch ($type) {
            case 'ean13':
                return preg_match('/^\d{13}$/', $value) && $this->validateEAN13Checksum($value);
            case 'upc':
                return preg_match('/^\d{12}$/', $value) && $this->validateUPCChecksum($value);
            case 'code39':
                return preg_match('/^[A-Z0-9\-\.\$\/\+%\*\s]+$/', $value);
            case 'code128':
                return strlen($value) > 0 && strlen($value) <= 128;
            default:
                return strlen($value) > 0;
        }
    }

    /**
     * Validate QR code
     */
    private function validateQRCode($value) {
        // QR codes can contain various data types
        // This is a very basic validation
        return !empty($value) && strlen($value) > 0;
    }

    /**
     * Validate timezone
     */
    private function validateTimezone($value) {
        return in_array($value, timezone_identifiers_list());
    }

    /**
     * Validate language code
     */
    private function validateLanguageCode($value) {
        return preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $value);
    }

    /**
     * Validate country code
     */
    private function validateCountryCode($value) {
        return preg_match('/^[A-Z]{2}$/', $value);
    }

    /**
     * Validate coordinate
     */
    private function validateCoordinate($value, $params) {
        $format = $params['format'] ?? 'decimal';

        if ($format === 'decimal') {
            return preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $value);
        } elseif ($format === 'dms') {
            return preg_match('/^\d{1,3}°\d{1,2}\'\d{1,2}"[NS],\d{1,3}°\d{1,2}\'\d{1,2}"[EW]$/', $value);
        }

        return false;
    }

    /**
     * Validate file size
     */
    private function validateFileSize($value, $params) {
        return is_numeric($value) && $value <= ($params['max_size'] ?? PHP_INT_MAX);
    }

    /**
     * Validate file type
     */
    private function validateFileType($value, $params) {
        $allowedTypes = $params['allowed_types'] ?? [];
        return in_array($value, $allowedTypes);
    }

    /**
     * Validate image dimensions
     */
    private function validateImageDimensions($value, $params) {
        // This would require image processing - simplified check
        return true; // Placeholder
    }

    /**
     * Validate unique
     */
    private function validateUnique($value, $params, $field = null) {
        $table = $params['table'] ?? '';
        $column = $params['column'] ?? '';
        $excludeCurrent = $params['exclude_current'] ?? false;

        if (empty($table) || empty($column)) {
            return true;
        }

        $query = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        $queryParams = [$value];

        if ($excludeCurrent && isset($_GET['id'])) {
            $query .= " AND id != ?";
            $queryParams[] = $_GET['id'];
        }

        $result = $this->db->querySingle($query, $queryParams);
        return ($result['count'] ?? 0) === 0;
    }

    /**
     * Validate exists
     */
    private function validateExists($value, $params) {
        $table = $params['table'] ?? '';
        $column = $params['column'] ?? '';

        if (empty($table) || empty($column)) {
            return true;
        }

        $result = $this->db->querySingle(
            "SELECT COUNT(*) as count FROM $table WHERE $column = ?",
            [$value]
        );

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Validate custom
     */
    private function validateCustom($value, $params) {
        $functionName = $params['function_name'] ?? '';

        if (empty($functionName)) {
            return true;
        }

        // This would call a custom validation function
        // For security, you should have a whitelist of allowed functions
        if (method_exists($this, $functionName)) {
            return $this->$functionName($value, $params);
        }

        return true; // Allow if function doesn't exist
    }

    /**
     * Get required field message
     */
    private function getRequiredFieldMessage($field) {
        return 'This field is required';
    }

    /**
     * Get validation error message
     */
    private function getValidationErrorMessage($rule, $params, $field = null) {
        $messages = [
            'required' => 'This field is required',
            'email' => 'Please enter a valid email address',
            'url' => 'Please enter a valid URL',
            'numeric' => 'Please enter a valid number',
            'integer' => 'Please enter a valid integer',
            'float' => 'Please enter a valid decimal number',
            'alphanumeric' => 'Only letters and numbers are allowed',
            'alpha' => 'Only letters are allowed',
            'min_length' => 'Must be at least ' . ($params['length'] ?? 0) . ' characters',
            'max_length' => 'Must be no more than ' . ($params['length'] ?? 0) . ' characters',
            'exact_length' => 'Must be exactly ' . ($params['length'] ?? 0) . ' characters',
            'pattern' => 'Invalid format',
            'regex' => 'Invalid format',
            'date' => 'Please enter a valid date',
            'date_range' => 'Date must be within the specified range',
            'time' => 'Please enter a valid time',
            'datetime' => 'Please enter a valid date and time',
            'future_date' => 'Date must be in the future',
            'past_date' => 'Date must be in the past',
            'age_range' => 'Age must be between ' . ($params['min_age'] ?? 0) . ' and ' . ($params['max_age'] ?? 150),
            'min_value' => 'Must be at least ' . ($params['value'] ?? 0),
            'max_value' => 'Must be no more than ' . ($params['value'] ?? 0),
            'range' => 'Must be between ' . ($params['min'] ?? 0) . ' and ' . ($params['max'] ?? 0),
            'credit_card' => 'Please enter a valid credit card number',
            'ssn' => 'Please enter a valid SSN (XXX-XX-XXXX)',
            'tax_id' => 'Please enter a valid tax ID',
            'phone' => 'Please enter a valid phone number',
            'postal_code' => 'Please enter a valid postal code',
            'ip_address' => 'Please enter a valid IP address',
            'mac_address' => 'Please enter a valid MAC address',
            'json' => 'Please enter valid JSON',
            'xml' => 'Please enter valid XML',
            'base64' => 'Please enter valid base64 data',
            'hex_color' => 'Please enter a valid hex color (e.g., #FF0000)',
            'rgb_color' => 'Please enter a valid RGB color (e.g., rgb(255, 0, 0))',
            'hsl_color' => 'Please enter a valid HSL color (e.g., hsl(0, 100%, 50%))',
            'currency' => 'Please enter a valid currency amount',
            'percentage' => 'Percentage must be between ' . ($params['min'] ?? 0) . '% and ' . ($params['max'] ?? 100) . '%',
            'rating' => 'Rating must be between ' . ($params['min'] ?? 1) . ' and ' . ($params['max'] ?? 5),
            'likert_scale' => 'Please select a valid option',
            'nps_score' => 'NPS score must be between 0 and 10',
            'signature' => 'Digital signature is required',
            'barcode' => 'Please enter a valid barcode',
            'qr_code' => 'Please enter valid QR code data',
            'timezone' => 'Please select a valid timezone',
            'language_code' => 'Please enter a valid language code',
            'country_code' => 'Please enter a valid country code',
            'coordinate' => 'Please enter valid coordinates',
            'file_size' => 'File size must be less than ' . ($params['max_size'] ?? 0) . ' bytes',
            'file_type' => 'File type not allowed',
            'image_dimensions' => 'Image dimensions do not meet requirements',
            'unique' => 'This value must be unique',
            'exists' => 'This value does not exist in our records',
            'custom' => 'Validation failed'
        ];

        return $messages[$rule] ?? 'Validation failed';
    }

    /**
     * Validate EAN13 checksum
     */
    private function validateEAN13Checksum($value) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$value[$i];
            $sum += $i % 2 === 0 ? $digit : $digit * 3;
        }
        $checksum = (10 - ($sum % 10)) % 10;
        return (int)$value[12] === $checksum;
    }

    /**
     * Validate UPC checksum
     */
    private function validateUPCChecksum($value) {
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $digit = (int)$value[$i];
            $sum += $i % 2 === 0 ? $digit * 3 : $digit;
        }
        $checksum = (10 - ($sum % 10)) % 10;
        return (int)$value[11] === $checksum;
    }

    /**
     * Get current user
     */
    private function getCurrentUser() {
        // This should be implemented to get the current user from session/auth
        return $_SESSION['user'] ?? null;
    }
}
