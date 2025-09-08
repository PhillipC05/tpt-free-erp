<?php
/**
 * TPT Free ERP - Form Submission Manager
 * Handles form submission processing and management
 */

class FormSubmissionManager {
    private $db;
    private $user;

    public function __construct() {
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Submit form
     */
    public function submitForm($data) {
        $this->db->beginTransaction();

        try {
            // Validate form exists and is published
            $form = $this->db->querySingle("
                SELECT * FROM forms WHERE id = ? AND company_id = ? AND status = 'published'
            ", [$data['form_id'], $this->user['company_id']]);

            if (!$form) {
                throw new Exception('Form not found or not published');
            }

            // Validate submission data against form fields
            $validationResult = $this->validateSubmissionData($data['form_id'], $data['submission_data']);

            if (!$validationResult['valid']) {
                throw new Exception('Validation failed: ' . implode(', ', $validationResult['errors']));
            }

            // Create submission record
            $submissionId = $this->db->insert('form_submissions', [
                'company_id' => $this->user['company_id'],
                'form_id' => $data['form_id'],
                'user_id' => $this->user['id'] ?? null,
                'submission_data' => json_encode($data['submission_data']),
                'device_info' => json_encode($data['device_info'] ?? []),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'status' => 'complete',
                'submitted_at' => date('Y-m-d H:i:s')
            ]);

            // Update form statistics
            $this->updateFormStatistics($data['form_id']);

            // Trigger integrations if configured
            $this->triggerFormIntegrations($data['form_id'], $data['submission_data'], $submissionId);

            $this->db->commit();
            return $submissionId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get form submissions
     */
    public function getFormSubmissions($formId = null, $filters = []) {
        $where = ["fs.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($formId) {
            $where[] = "fs.form_id = ?";
            $params[] = $formId;
        }

        // Apply filters
        if (!empty($filters['date_from'])) {
            $where[] = "fs.submitted_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "fs.submitted_at <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['status'])) {
            $where[] = "fs.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "fs.user_id = ?";
            $params[] = $filters['user_id'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                fs.*,
                fs.submission_data,
                fs.status,
                fs.submitted_at,
                fs.ip_address,
                fs.user_agent,
                f.form_title,
                u.first_name,
                u.last_name
            FROM form_submissions fs
            LEFT JOIN forms f ON fs.form_id = f.id
            LEFT JOIN users u ON fs.user_id = u.id
            WHERE $whereClause
            ORDER BY fs.submitted_at DESC
        ", $params);
    }

    /**
     * Get submission by ID
     */
    public function getSubmission($submissionId) {
        return $this->db->querySingle("
            SELECT
                fs.*,
                fs.submission_data,
                fs.status,
                fs.submitted_at,
                fs.ip_address,
                fs.user_agent,
                f.form_title,
                u.first_name,
                u.last_name
            FROM form_submissions fs
            LEFT JOIN forms f ON fs.form_id = f.id
            LEFT JOIN users u ON fs.user_id = u.id
            WHERE fs.id = ? AND fs.company_id = ?
        ", [$submissionId, $this->user['company_id']]);
    }

    /**
     * Update submission status
     */
    public function updateSubmissionStatus($submissionId, $status) {
        return $this->db->update('form_submissions', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ? AND company_id = ?', [$submissionId, $this->user['company_id']]);
    }

    /**
     * Delete submission
     */
    public function deleteSubmission($submissionId) {
        return $this->db->delete('form_submissions', 'id = ? AND company_id = ?', [
            $submissionId,
            $this->user['company_id']
        ]);
    }

    /**
     * Export submissions
     */
    public function exportSubmissions($formId, $format, $filters = []) {
        $submissions = $this->getFormSubmissions($formId, $filters);

        // Format data for export
        $exportData = $this->formatExportData($submissions, $format);

        // Create export record
        $exportId = $this->db->insert('form_exports', [
            'company_id' => $this->user['company_id'],
            'form_id' => $formId,
            'export_format' => $format,
            'record_count' => count($submissions),
            'filters' => json_encode($filters),
            'exported_by' => $this->user['id'],
            'exported_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'export_id' => $exportId,
            'data' => $exportData,
            'format' => $format,
            'record_count' => count($submissions)
        ];
    }

    /**
     * Bulk update submissions
     */
    public function bulkUpdateSubmissions($submissionIds, $action, $parameters = []) {
        $updated = 0;

        foreach ($submissionIds as $submissionId) {
            switch ($action) {
                case 'delete':
                    if ($this->deleteSubmission($submissionId)) {
                        $updated++;
                    }
                    break;

                case 'status':
                    if (!empty($parameters['status'])) {
                        if ($this->updateSubmissionStatus($submissionId, $parameters['status'])) {
                            $updated++;
                        }
                    }
                    break;

                case 'flag':
                    // Add flag logic here
                    $updated++;
                    break;

                case 'unflag':
                    // Remove flag logic here
                    $updated++;
                    break;
            }
        }

        return $updated;
    }

    /**
     * Get submission analytics
     */
    public function getSubmissionAnalytics($formId = null) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($formId) {
            $where[] = "form_id = ?";
            $params[] = $formId;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN status = 'complete' THEN 1 END) as complete_submissions,
                COUNT(CASE WHEN status = 'incomplete' THEN 1 END) as incomplete_submissions,
                AVG(completion_time_seconds) as avg_completion_time,
                MIN(submitted_at) as first_submission,
                MAX(submitted_at) as last_submission,
                COUNT(DISTINCT user_id) as unique_submitters
            FROM form_submissions
            WHERE $whereClause AND submitted_at >= ?
        ", array_merge($params, [date('Y-m-d H:i:s', strtotime('-30 days'))]));
    }

    /**
     * Get submission filters
     */
    public function getSubmissionFilters() {
        return [
            'date_range' => [
                'name' => 'Date Range',
                'type' => 'date',
                'options' => ['today', 'yesterday', 'last_7_days', 'last_30_days', 'custom']
            ],
            'status' => [
                'name' => 'Status',
                'type' => 'select',
                'options' => ['all', 'complete', 'incomplete', 'flagged']
            ],
            'user' => [
                'name' => 'User',
                'type' => 'select',
                'options' => $this->getUsersList()
            ],
            'device' => [
                'name' => 'Device Type',
                'type' => 'select',
                'options' => ['desktop', 'mobile', 'tablet']
            ],
            'completion_time' => [
                'name' => 'Completion Time',
                'type' => 'range',
                'options' => ['0-30s', '30s-1m', '1-5m', '5-10m', '10m+']
            ]
        ];
    }

    /**
     * Get export formats
     */
    public function getExportFormats() {
        return [
            'excel' => [
                'name' => 'Excel (.xlsx)',
                'description' => 'Microsoft Excel spreadsheet',
                'icon' => 'file-excel'
            ],
            'csv' => [
                'name' => 'CSV (.csv)',
                'description' => 'Comma-separated values',
                'icon' => 'file-csv'
            ],
            'json' => [
                'name' => 'JSON (.json)',
                'description' => 'JavaScript Object Notation',
                'icon' => 'file-json'
            ],
            'pdf' => [
                'name' => 'PDF Report (.pdf)',
                'description' => 'Portable Document Format report',
                'icon' => 'file-pdf'
            ],
            'xml' => [
                'name' => 'XML (.xml)',
                'description' => 'Extensible Markup Language',
                'icon' => 'file-xml'
            ]
        ];
    }

    /**
     * Get bulk operations
     */
    public function getBulkOperations() {
        return [
            'export' => 'Export Selected',
            'delete' => 'Delete Selected',
            'flag' => 'Flag for Review',
            'unflag' => 'Remove Flag',
            'notify' => 'Send Notification',
            'archive' => 'Archive Selected'
        ];
    }

    /**
     * Validate submission data
     */
    private function validateSubmissionData($formId, $submissionData) {
        $errors = [];
        $isValid = true;

        // Get form fields
        $fields = $this->db->query("
            SELECT * FROM form_fields
            WHERE form_id = ? ORDER BY field_order ASC
        ", [$formId]);

        foreach ($fields as $field) {
            $fieldName = $field['field_name'];
            $fieldValue = $submissionData[$fieldName] ?? null;

            // Check required fields
            if ($field['is_required'] && empty($fieldValue)) {
                $errors[$fieldName] = 'This field is required';
                $isValid = false;
                continue;
            }

            // Apply validation rules
            if (!empty($fieldValue)) {
                $validationRules = json_decode($field['validation_rules'], true);
                if ($validationRules) {
                    foreach ($validationRules as $rule => $params) {
                        if (!$this->validateFieldRule($fieldValue, $rule, $params)) {
                            $errors[$fieldName] = $this->getValidationErrorMessage($rule, $params);
                            $isValid = false;
                            break;
                        }
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
     * Validate field rule
     */
    private function validateFieldRule($value, $rule, $params) {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'numeric':
                return is_numeric($value);
            case 'alphanumeric':
                return ctype_alnum($value);
            case 'min_length':
                return strlen($value) >= ($params['length'] ?? 0);
            case 'max_length':
                return strlen($value) <= ($params['length'] ?? PHP_INT_MAX);
            case 'pattern':
                return preg_match($params['pattern'] ?? '', $value);
            case 'regex':
                $flags = $params['flags'] ?? '';
                return preg_match('/' . $params['pattern'] . '/' . $flags, $value);
            case 'date_range':
                $date = strtotime($value);
                $minDate = strtotime($params['min_date'] ?? '1900-01-01');
                $maxDate = strtotime($params['max_date'] ?? '2100-12-31');
                return $date >= $minDate && $date <= $maxDate;
            case 'future_date':
                return strtotime($value) > time();
            case 'past_date':
                return strtotime($value) < time();
            case 'age_range':
                $birthDate = strtotime($value);
                $age = (time() - $birthDate) / (365.25 * 24 * 60 * 60);
                return $age >= ($params['min_age'] ?? 0) && $age <= ($params['max_age'] ?? 150);
            case 'ip_address':
                return filter_var($value, FILTER_VALIDATE_IP) !== false;
            case 'mac_address':
                return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value);
            case 'json':
                json_decode($value);
                return json_last_error() === JSON_ERROR_NONE;
            case 'xml':
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($value);
                return $xml !== false;
            case 'base64':
                return base64_decode($value, true) !== false;
            case 'hex_color':
                return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value);
            case 'rgb_color':
                return preg_match('/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/', $value);
            case 'hsl_color':
                return preg_match('/^hsl\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*\)$/', $value);
            case 'currency_format':
                $currency = $params['currency'] ?? 'USD';
                $locale = $params['locale'] ?? 'en_US';
                return $this->validateCurrencyFormat($value, $currency, $locale);
            case 'percentage_range':
                $num = floatval($value);
                return $num >= ($params['min'] ?? 0) && $num <= ($params['max'] ?? 100);
            case 'rating_range':
                $num = floatval($value);
                $min = $params['min'] ?? 1;
                $max = $params['max'] ?? 5;
                $allowHalf = $params['allow_half'] ?? false;
                if ($allowHalf) {
                    return $num >= $min && $num <= $max && ($num * 2) == round($num * 2);
                }
                return $num >= $min && $num <= $max && $num == round($num);
            case 'likert_scale':
                $scalePoints = $params['scale_points'] ?? 5;
                $naAllowed = $params['na_allowed'] ?? false;
                $num = intval($value);
                if ($naAllowed && $value === 'NA') return true;
                return $num >= 1 && $num <= $scalePoints;
            case 'nps_score':
                $num = intval($value);
                return $num >= 0 && $num <= 10;
            case 'signature_required':
                return !empty($value) && strlen($value) > 100; // Basic signature check
            case 'barcode_format':
                $type = $params['type'] ?? 'any';
                return $this->validateBarcode($value, $type);
            case 'qr_code':
                return $this->validateQRCode($value);
            case 'timezone':
                return in_array($value, timezone_identifiers_list());
            case 'language_code':
                return preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $value);
            case 'country_code':
                return preg_match('/^[A-Z]{2}$/', $value);
            case 'coordinate':
                $format = $params['format'] ?? 'decimal';
                if ($format === 'decimal') {
                    return preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $value);
                } elseif ($format === 'dms') {
                    return preg_match('/^\d{1,3}°\d{1,2}\'\d{1,2}"[NS],\d{1,3}°\d{1,2}\'\d{1,2}"[EW]$/', $value);
                }
                return false;
            case 'matrix_complete':
                // This would require checking all matrix fields - simplified
                return !empty($value);
            case 'table_min_rows':
                $minRows = $params['min_rows'] ?? 1;
                return is_array($value) && count($value) >= $minRows;
            case 'table_max_rows':
                $maxRows = $params['max_rows'] ?? PHP_INT_MAX;
                return is_array($value) && count($value) <= $maxRows;
            case 'formula_result':
                // This would require evaluating the formula - simplified
                return is_numeric($value);
            case 'conditional_logic':
                $conditions = $params['conditions'] ?? [];
                return $this->validateConditionalLogic($value, $conditions);
            case 'unique':
                return $this->validateUnique($value, $params);
            case 'exists':
                return $this->validateExists($value, $params);
            case 'custom_function':
                $functionName = $params['function_name'];
                $functionParams = $params['parameters'] ?? [];
                return $this->callCustomValidationFunction($functionName, $value, $functionParams);
            case 'conditional_required':
                $conditionField = $params['condition_field'];
                $conditionValue = $params['condition_value'];
                // This would need access to other form data - simplified
                return true;
            default:
                return true;
        }
    }

    /**
     * Get validation error message
     */
    private function getValidationErrorMessage($rule, $params) {
        $messages = [
            'required' => 'This field is required',
            'email' => 'Please enter a valid email address',
            'url' => 'Please enter a valid URL',
            'numeric' => 'Please enter a valid number',
            'alphanumeric' => 'Only letters and numbers are allowed',
            'min_length' => 'Must be at least ' . ($params['length'] ?? 0) . ' characters',
            'max_length' => 'Must be no more than ' . ($params['length'] ?? 0) . ' characters',
            'pattern' => 'Invalid format',
            'regex' => 'Invalid format',
            'date_range' => 'Date must be within the specified range',
            'future_date' => 'Date must be in the future',
            'past_date' => 'Date must be in the past',
            'age_range' => 'Age must be between ' . ($params['min_age'] ?? 0) . ' and ' . ($params['max_age'] ?? 150),
            'ip_address' => 'Please enter a valid IP address',
            'mac_address' => 'Please enter a valid MAC address',
            'json' => 'Please enter valid JSON',
            'xml' => 'Please enter valid XML',
            'base64' => 'Please enter valid base64 data',
            'hex_color' => 'Please enter a valid hex color (e.g., #FF0000)',
            'rgb_color' => 'Please enter a valid RGB color (e.g., rgb(255, 0, 0))',
            'hsl_color' => 'Please enter a valid HSL color (e.g., hsl(0, 100%, 50%))',
            'currency_format' => 'Please enter a valid currency amount',
            'percentage_range' => 'Percentage must be between ' . ($params['min'] ?? 0) . '% and ' . ($params['max'] ?? 100) . '%',
            'rating_range' => 'Rating must be between ' . ($params['min'] ?? 1) . ' and ' . ($params['max'] ?? 5),
            'likert_scale' => 'Please select a valid option',
            'nps_score' => 'NPS score must be between 0 and 10',
            'signature_required' => 'Digital signature is required',
            'barcode_format' => 'Please enter a valid barcode',
            'qr_code' => 'Please enter valid QR code data',
            'timezone' => 'Please select a valid timezone',
            'language_code' => 'Please enter a valid language code',
            'country_code' => 'Please enter a valid country code',
            'coordinate' => 'Please enter valid coordinates',
            'matrix_complete' => 'Please complete all matrix fields',
            'table_min_rows' => 'Table must have at least ' . ($params['min_rows'] ?? 1) . ' rows',
            'table_max_rows' => 'Table must not exceed ' . ($params['max_rows'] ?? 0) . ' rows',
            'formula_result' => 'Formula calculation is invalid',
            'conditional_logic' => 'Field does not meet conditional requirements',
            'unique' => 'This value must be unique',
            'exists' => 'This value does not exist in our records',
            'custom_function' => 'Validation failed',
            'conditional_required' => 'This field is required based on other selections'
        ];

        return $messages[$rule] ?? 'Validation failed';
    }

    /**
     * Update form statistics
     */
    private function updateFormStatistics($formId) {
        // Update total submissions
        $this->db->query("
            UPDATE forms SET
                total_submissions = total_submissions + 1,
                last_submission = NOW()
            WHERE id = ?
        ", [$formId]);
    }

    /**
     * Trigger form integrations
     */
    private function triggerFormIntegrations($formId, $submissionData, $submissionId) {
        // Get form integrations
        $integrations = $this->db->query("
            SELECT * FROM form_integrations
            WHERE form_id = ? AND is_active = true
        ", [$formId]);

        foreach ($integrations as $integration) {
            try {
                switch ($integration['integration_type']) {
                    case 'email':
                        $this->sendIntegrationEmail($integration, $submissionData);
                        break;
                    case 'webhook':
                        $this->sendIntegrationWebhook($integration, $submissionData, $submissionId);
                        break;
                    case 'api':
                        $this->sendIntegrationAPI($integration, $submissionData, $submissionId);
                        break;
                }
            } catch (Exception $e) {
                // Log integration error but don't fail the submission
                error_log("Form integration failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Send integration email
     */
    private function sendIntegrationEmail($integration, $submissionData) {
        $settings = json_decode($integration['settings'], true);
        $recipients = $settings['recipients'] ?? [];
        $template = $settings['template'] ?? 'default';

        // Send email notification
        // Implementation would use the email system
    }

    /**
     * Send integration webhook
     */
    private function sendIntegrationWebhook($integration, $submissionData, $submissionId) {
        $settings = json_decode($integration['settings'], true);
        $url = $settings['url'];
        $method = $settings['method'] ?? 'POST';
        $headers = $settings['headers'] ?? [];

        $payload = [
            'form_id' => $integration['form_id'],
            'submission_id' => $submissionId,
            'data' => $submissionData,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Send webhook
        // Implementation would use curl or similar
    }

    /**
     * Send integration API
     */
    private function sendIntegrationAPI($integration, $submissionData, $submissionId) {
        $settings = json_decode($integration['settings'], true);
        $endpoint = $settings['endpoint'];
        $method = $settings['method'] ?? 'POST';
        $mapping = $settings['mapping'] ?? [];

        // Map submission data to API format
        $apiData = [];
        foreach ($mapping as $apiField => $formField) {
            $apiData[$apiField] = $submissionData[$formField] ?? null;
        }

        // Send API request
        // Implementation would use curl or similar
    }

    /**
     * Format export data
     */
    private function formatExportData($submissions, $format) {
        $data = [];

        foreach ($submissions as $submission) {
            $submissionData = json_decode($submission['submission_data'], true);
            $row = [
                'submission_id' => $submission['id'],
                'submitted_at' => $submission['submitted_at'],
                'user_id' => $submission['user_id'],
                'status' => $submission['status']
            ];

            // Add form field data
            $row = array_merge($row, $submissionData);
            $data[] = $row;
        }

        switch ($format) {
            case 'csv':
                return $this->arrayToCsv($data);
            case 'json':
                return json_encode($data);
            case 'xml':
                return $this->arrayToXml($data);
            default:
                return $data;
        }
    }

    /**
     * Convert array to CSV
     */
    private function arrayToCsv($data) {
        if (empty($data)) return '';

        $output = fopen('php://temp', 'r+');

        // Write headers
        fputcsv($output, array_keys($data[0]));

        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Convert array to XML
     */
    private function arrayToXml($data) {
        $xml = new SimpleXMLElement('<submissions/>');

        foreach ($data as $row) {
            $submission = $xml->addChild('submission');
            foreach ($row as $key => $value) {
                $submission->addChild($key, htmlspecialchars($value));
            }
        }

        return $xml->asXML();
    }

    /**
     * Get users list
     */
    private function getUsersList() {
        return $this->db->query("
            SELECT id, CONCAT(first_name, ' ', last_name) as name
            FROM users
            WHERE company_id = ?
            ORDER BY first_name, last_name
        ", [$this->user['company_id']]);
    }

    /**
     * Validate currency format
     */
    private function validateCurrencyFormat($value, $currency, $locale) {
        // This is a simplified check - in a real implementation,
        // you would use NumberFormatter or similar
        $value = trim($value);

        // Remove currency symbols and formatting
        $value = preg_replace('/[^\d.,-]/', '', $value);

        // Check if it's a valid number
        return is_numeric(str_replace([',', ' '], ['', ''], $value));
    }

    /**
     * Validate barcode
     */
    private function validateBarcode($value, $type) {
        // Simplified barcode validation
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
     * Validate QR code
     */
    private function validateQRCode($value) {
        // QR codes can contain various data types
        // This is a very basic validation
        return !empty($value) && strlen($value) > 0;
    }

    /**
     * Validate conditional logic
     */
    private function validateConditionalLogic($value, $conditions) {
        // This would require access to other form field values
        // Simplified implementation
        return true;
    }

    /**
     * Validate unique
     */
    private function validateUnique($value, $params) {
        $table = $params['table'] ?? '';
        $column = $params['column'] ?? '';
        $excludeCurrent = $params['exclude_current'] ?? false;

        if (empty($table) || empty($column)) {
            return true;
        }

        $query = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
        $params = [$value];

        if ($excludeCurrent && isset($_GET['id'])) {
            $query .= " AND id != ?";
            $params[] = $_GET['id'];
        }

        $result = $this->db->querySingle($query, $params);
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
     * Call custom validation function
     */
    private function callCustomValidationFunction($functionName, $value, $params) {
        // This would call a custom validation function
        // For security, you should have a whitelist of allowed functions
        if (method_exists($this, $functionName)) {
            return $this->$functionName($value, $params);
        }

        return true; // Allow if function doesn't exist
    }

    /**
     * Get current user
     */
    private function getCurrentUser() {
        // This should be implemented to get the current user from session/auth
        return $_SESSION['user'] ?? null;
    }
}
