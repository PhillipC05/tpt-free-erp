<?php
/**
 * TPT Free ERP - Form Builder
 * Handles form building and element management
 */

class FormBuilder {
    private $db;
    private $user;

    public function __construct() {
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Get form elements
     */
    public function getFormElements() {
        return [
            // Basic Input Types
            'text' => [
                'name' => 'Text Input',
                'icon' => 'text-input',
                'description' => 'Single line text input',
                'properties' => ['placeholder', 'maxlength', 'pattern']
            ],
            'textarea' => [
                'name' => 'Text Area',
                'icon' => 'textarea',
                'description' => 'Multi-line text input',
                'properties' => ['placeholder', 'rows', 'maxlength']
            ],
            'password' => [
                'name' => 'Password Input',
                'icon' => 'lock',
                'description' => 'Secure password input',
                'properties' => ['placeholder', 'minlength', 'show_toggle']
            ],
            'email' => [
                'name' => 'Email Input',
                'icon' => 'email',
                'description' => 'Email address input',
                'properties' => ['placeholder', 'confirmation']
            ],
            'url' => [
                'name' => 'URL Input',
                'icon' => 'link',
                'description' => 'Website URL input',
                'properties' => ['placeholder', 'protocols']
            ],
            'search' => [
                'name' => 'Search Input',
                'icon' => 'search',
                'description' => 'Search input with suggestions',
                'properties' => ['placeholder', 'autocomplete', 'suggestions']
            ],
            'tel' => [
                'name' => 'Telephone Input',
                'icon' => 'phone',
                'description' => 'Telephone number input',
                'properties' => ['format', 'country_code', 'validation']
            ],

            // Selection Elements
            'select' => [
                'name' => 'Select Dropdown',
                'icon' => 'select',
                'description' => 'Dropdown selection',
                'properties' => ['options', 'multiple', 'placeholder', 'searchable']
            ],
            'radio' => [
                'name' => 'Radio Buttons',
                'icon' => 'radio',
                'description' => 'Single choice selection',
                'properties' => ['options', 'orientation', 'other_option']
            ],
            'checkbox' => [
                'name' => 'Checkboxes',
                'icon' => 'checkbox',
                'description' => 'Multiple choice selection',
                'properties' => ['options', 'orientation', 'min_select', 'max_select']
            ],
            'multiselect' => [
                'name' => 'Multi-Select with Chips',
                'icon' => 'tags',
                'description' => 'Multi-select with removable chips',
                'properties' => ['options', 'max_selections', 'allow_custom']
            ],
            'autocomplete' => [
                'name' => 'Autocomplete',
                'icon' => 'autocomplete',
                'description' => 'Auto-complete from data source',
                'properties' => ['data_source', 'min_chars', 'max_results']
            ],

            // Numeric Inputs
            'number' => [
                'name' => 'Number Input',
                'icon' => 'number',
                'description' => 'Numeric input with validation',
                'properties' => ['min', 'max', 'step', 'placeholder', 'unit']
            ],
            'range' => [
                'name' => 'Range Slider',
                'icon' => 'range',
                'description' => 'Range slider with dual handles',
                'properties' => ['min', 'max', 'step', 'orientation', 'show_value']
            ],
            'currency' => [
                'name' => 'Currency Input',
                'icon' => 'dollar-sign',
                'description' => 'Currency amount input',
                'properties' => ['currency', 'locale', 'min', 'max', 'decimals']
            ],
            'percentage' => [
                'name' => 'Percentage Input',
                'icon' => 'percent',
                'description' => 'Percentage value input',
                'properties' => ['min', 'max', 'decimals', 'show_symbol']
            ],
            'calculator' => [
                'name' => 'Calculator Input',
                'icon' => 'calculator',
                'description' => 'Input with built-in calculator',
                'properties' => ['allow_formulas', 'precision', 'operators']
            ],

            // Date & Time
            'date' => [
                'name' => 'Date Picker',
                'icon' => 'calendar',
                'description' => 'Date selection',
                'properties' => ['format', 'min_date', 'max_date', 'disabled_dates']
            ],
            'time' => [
                'name' => 'Time Picker',
                'icon' => 'clock',
                'description' => 'Time selection',
                'properties' => ['format', 'interval', 'timezone']
            ],
            'datetime' => [
                'name' => 'DateTime Picker',
                'icon' => 'calendar-clock',
                'description' => 'Combined date and time selection',
                'properties' => ['format', 'timezone', 'min_datetime', 'max_datetime']
            ],
            'month' => [
                'name' => 'Month Picker',
                'icon' => 'calendar-month',
                'description' => 'Month and year selection',
                'properties' => ['format', 'min_month', 'max_month']
            ],
            'week' => [
                'name' => 'Week Picker',
                'icon' => 'calendar-week',
                'description' => 'Week selection',
                'properties' => ['format', 'min_week', 'max_week']
            ],

            // File Uploads
            'file' => [
                'name' => 'File Upload',
                'icon' => 'upload',
                'description' => 'File attachment',
                'properties' => ['accepted_types', 'max_size', 'multiple', 'max_files']
            ],
            'image' => [
                'name' => 'Image Upload',
                'icon' => 'image',
                'description' => 'Image upload with preview',
                'properties' => ['accepted_types', 'max_size', 'multiple', 'crop', 'resize']
            ],
            'video' => [
                'name' => 'Video Upload',
                'icon' => 'video',
                'description' => 'Video file upload',
                'properties' => ['accepted_types', 'max_size', 'max_duration', 'thumbnail']
            ],
            'audio' => [
                'name' => 'Audio Upload',
                'icon' => 'audio',
                'description' => 'Audio file upload',
                'properties' => ['accepted_types', 'max_size', 'max_duration']
            ],

            // Advanced Inputs
            'address' => [
                'name' => 'Address Field',
                'icon' => 'map',
                'description' => 'Complete address input',
                'properties' => ['components', 'validation', 'autocomplete']
            ],
            'location' => [
                'name' => 'Location Picker',
                'icon' => 'map-pin',
                'description' => 'Interactive map location picker',
                'properties' => ['map_type', 'default_location', 'zoom_level']
            ],
            'color' => [
                'name' => 'Color Picker',
                'icon' => 'palette',
                'description' => 'Color selection input',
                'properties' => ['format', 'palette', 'opacity']
            ],
            'rating' => [
                'name' => 'Rating Scale',
                'icon' => 'star',
                'description' => 'Star rating input',
                'properties' => ['max_rating', 'shape', 'color', 'allow_half']
            ],
            'slider' => [
                'name' => 'Slider',
                'icon' => 'slider',
                'description' => 'Single value slider input',
                'properties' => ['min', 'max', 'step', 'orientation', 'show_value']
            ],
            'likert' => [
                'name' => 'Likert Scale',
                'icon' => 'bar-chart',
                'description' => 'Agreement/disagreement scale',
                'properties' => ['scale_points', 'labels', 'na_option']
            ],
            'nps' => [
                'name' => 'Net Promoter Score',
                'icon' => 'trending-up',
                'description' => 'NPS rating scale (0-10)',
                'properties' => ['show_labels', 'custom_labels']
            ],

            // Rich Content
            'richtext' => [
                'name' => 'Rich Text Editor',
                'icon' => 'edit-3',
                'description' => 'Rich text editor with formatting',
                'properties' => ['toolbar', 'height', 'plugins', 'max_length']
            ],
            'code' => [
                'name' => 'Code Editor',
                'icon' => 'code',
                'description' => 'Code editor with syntax highlighting',
                'properties' => ['language', 'theme', 'line_numbers', 'readonly']
            ],
            'markdown' => [
                'name' => 'Markdown Editor',
                'icon' => 'file-text',
                'description' => 'Markdown text editor',
                'properties' => ['preview', 'toolbar', 'height']
            ],

            // Special Purpose
            'signature' => [
                'name' => 'Signature Pad',
                'icon' => 'signature',
                'description' => 'Digital signature capture',
                'properties' => ['width', 'height', 'format', 'pen_color']
            ],
            'barcode' => [
                'name' => 'Barcode Scanner',
                'icon' => 'barcode',
                'description' => 'Barcode/QR code scanner',
                'properties' => ['formats', 'camera', 'continuous']
            ],
            'qr' => [
                'name' => 'QR Code Generator',
                'icon' => 'qr-code',
                'description' => 'Generate QR codes from input',
                'properties' => ['size', 'error_correction', 'format']
            ],
            'masked' => [
                'name' => 'Masked Input',
                'icon' => 'mask',
                'description' => 'Input with format mask',
                'properties' => ['mask', 'placeholder', 'guide']
            ],
            'tags' => [
                'name' => 'Tags Input',
                'icon' => 'tag',
                'description' => 'Tag input with suggestions',
                'properties' => ['suggestions', 'max_tags', 'allow_spaces']
            ],

            // Business Specific
            'country' => [
                'name' => 'Country Selector',
                'icon' => 'globe',
                'description' => 'Country dropdown with flags',
                'properties' => ['show_flags', 'priority_countries', 'exclude_countries']
            ],
            'timezone' => [
                'name' => 'Timezone Selector',
                'icon' => 'clock-globe',
                'description' => 'Timezone selection',
                'properties' => ['format', 'current_time', 'group_by_region']
            ],
            'language' => [
                'name' => 'Language Selector',
                'icon' => 'languages',
                'description' => 'Language selection dropdown',
                'properties' => ['show_flags', 'rtl_support', 'fallback']
            ],
            'creditcard' => [
                'name' => 'Credit Card Input',
                'icon' => 'credit-card',
                'description' => 'Credit card number input with validation',
                'properties' => ['accepted_cards', 'show_icons', 'save_card']
            ],
            'ssn' => [
                'name' => 'SSN Input',
                'icon' => 'id-card',
                'description' => 'Social Security Number input',
                'properties' => ['format', 'mask', 'validation']
            ],
            'taxid' => [
                'name' => 'Tax ID Input',
                'icon' => 'file-text',
                'description' => 'Tax identification number input',
                'properties' => ['country', 'format', 'validation']
            ],

            // Matrix and Grid
            'matrix' => [
                'name' => 'Matrix/Rating Grid',
                'icon' => 'grid',
                'description' => 'Multi-row rating matrix',
                'properties' => ['rows', 'columns', 'rating_type', 'required_rows']
            ],
            'table' => [
                'name' => 'Dynamic Table',
                'icon' => 'table',
                'description' => 'Dynamic table input',
                'properties' => ['columns', 'min_rows', 'max_rows', 'sortable']
            ],

            // Hidden and System
            'hidden' => [
                'name' => 'Hidden Input',
                'icon' => 'eye-off',
                'description' => 'Hidden form field',
                'properties' => ['value', 'dynamic_value']
            ],
            'formula' => [
                'name' => 'Formula Field',
                'icon' => 'function',
                'description' => 'Calculated field based on other fields',
                'properties' => ['formula', 'precision', 'update_on_change']
            ],
            'conditional' => [
                'name' => 'Conditional Field',
                'icon' => 'git-branch',
                'description' => 'Field shown based on conditions',
                'properties' => ['conditions', 'show_when', 'animation']
            ]
        ];
    }

    /**
     * Get validation rules
     */
    public function getValidationRules() {
        return [
            'required' => [
                'name' => 'Required',
                'description' => 'Field must be filled',
                'rule' => 'required'
            ],
            'email' => [
                'name' => 'Email Format',
                'description' => 'Must be valid email address',
                'rule' => 'email'
            ],
            'phone' => [
                'name' => 'Phone Format',
                'description' => 'Must be valid phone number',
                'rule' => 'phone'
            ],
            'numeric' => [
                'name' => 'Numeric Only',
                'description' => 'Must contain only numbers',
                'rule' => 'numeric'
            ],
            'alphanumeric' => [
                'name' => 'Alphanumeric',
                'description' => 'Letters and numbers only',
                'rule' => 'alphanumeric'
            ],
            'min_length' => [
                'name' => 'Minimum Length',
                'description' => 'Must be at least X characters',
                'rule' => 'min_length',
                'parameters' => ['length']
            ],
            'max_length' => [
                'name' => 'Maximum Length',
                'description' => 'Must be no more than X characters',
                'rule' => 'max_length',
                'parameters' => ['length']
            ],
            'pattern' => [
                'name' => 'Pattern Match',
                'description' => 'Must match specific pattern',
                'rule' => 'pattern',
                'parameters' => ['pattern']
            ],
            'date_range' => [
                'name' => 'Date Range',
                'description' => 'Must be within date range',
                'rule' => 'date_range',
                'parameters' => ['min_date', 'max_date']
            ],
            'file_size' => [
                'name' => 'File Size',
                'description' => 'File must be within size limits',
                'rule' => 'file_size',
                'parameters' => ['min_size', 'max_size']
            ],
            'file_type' => [
                'name' => 'File Type',
                'description' => 'File must be specific type',
                'rule' => 'file_type',
                'parameters' => ['allowed_types']
            ],
            'url' => [
                'name' => 'URL Format',
                'description' => 'Must be valid URL',
                'rule' => 'url'
            ],
            'min_value' => [
                'name' => 'Minimum Value',
                'description' => 'Must be at least X',
                'rule' => 'min_value',
                'parameters' => ['value']
            ],
            'max_value' => [
                'name' => 'Maximum Value',
                'description' => 'Must be no more than X',
                'rule' => 'max_value',
                'parameters' => ['value']
            ],
            'range' => [
                'name' => 'Value Range',
                'description' => 'Must be between X and Y',
                'rule' => 'range',
                'parameters' => ['min', 'max']
            ],
            'credit_card' => [
                'name' => 'Credit Card Format',
                'description' => 'Must be valid credit card number',
                'rule' => 'credit_card'
            ],
            'ssn' => [
                'name' => 'SSN Format',
                'description' => 'Must be valid SSN format',
                'rule' => 'ssn'
            ],
            'tax_id' => [
                'name' => 'Tax ID Format',
                'description' => 'Must be valid tax ID format',
                'rule' => 'tax_id'
            ],
            'postal_code' => [
                'name' => 'Postal Code',
                'description' => 'Must be valid postal code',
                'rule' => 'postal_code',
                'parameters' => ['country']
            ],
            'phone_country' => [
                'name' => 'Phone with Country',
                'description' => 'Must be valid phone number for country',
                'rule' => 'phone_country',
                'parameters' => ['country']
            ],
            'file_extension' => [
                'name' => 'File Extension',
                'description' => 'File must have specific extension',
                'rule' => 'file_extension',
                'parameters' => ['extensions']
            ],
            'image_dimensions' => [
                'name' => 'Image Dimensions',
                'description' => 'Image must meet dimension requirements',
                'rule' => 'image_dimensions',
                'parameters' => ['min_width', 'min_height', 'max_width', 'max_height']
            ],
            'password_strength' => [
                'name' => 'Password Strength',
                'description' => 'Password must meet strength requirements',
                'rule' => 'password_strength',
                'parameters' => ['min_length', 'require_uppercase', 'require_lowercase', 'require_numbers', 'require_symbols']
            ],
            'regex' => [
                'name' => 'Regular Expression',
                'description' => 'Must match regular expression',
                'rule' => 'regex',
                'parameters' => ['pattern', 'flags']
            ],
            'custom_function' => [
                'name' => 'Custom Validation',
                'description' => 'Custom validation function',
                'rule' => 'custom_function',
                'parameters' => ['function_name', 'parameters']
            ],
            'conditional_required' => [
                'name' => 'Conditionally Required',
                'description' => 'Required based on other field values',
                'rule' => 'conditional_required',
                'parameters' => ['condition_field', 'condition_value']
            ],
            'unique' => [
                'name' => 'Unique Value',
                'description' => 'Value must be unique in database',
                'rule' => 'unique',
                'parameters' => ['table', 'column', 'exclude_current']
            ],
            'exists' => [
                'name' => 'Value Exists',
                'description' => 'Value must exist in database',
                'rule' => 'exists',
                'parameters' => ['table', 'column']
            ],
            'future_date' => [
                'name' => 'Future Date',
                'description' => 'Date must be in the future',
                'rule' => 'future_date'
            ],
            'past_date' => [
                'name' => 'Past Date',
                'description' => 'Date must be in the past',
                'rule' => 'past_date'
            ],
            'age_range' => [
                'name' => 'Age Range',
                'description' => 'Age must be within range',
                'rule' => 'age_range',
                'parameters' => ['min_age', 'max_age']
            ],
            'ip_address' => [
                'name' => 'IP Address',
                'description' => 'Must be valid IP address',
                'rule' => 'ip_address'
            ],
            'mac_address' => [
                'name' => 'MAC Address',
                'description' => 'Must be valid MAC address',
                'rule' => 'mac_address'
            ],
            'json' => [
                'name' => 'JSON Format',
                'description' => 'Must be valid JSON',
                'rule' => 'json'
            ],
            'xml' => [
                'name' => 'XML Format',
                'description' => 'Must be valid XML',
                'rule' => 'xml'
            ],
            'base64' => [
                'name' => 'Base64 Format',
                'description' => 'Must be valid base64',
                'rule' => 'base64'
            ],
            'hex_color' => [
                'name' => 'Hex Color',
                'description' => 'Must be valid hex color',
                'rule' => 'hex_color'
            ],
            'rgb_color' => [
                'name' => 'RGB Color',
                'description' => 'Must be valid RGB color',
                'rule' => 'rgb_color'
            ],
            'hsl_color' => [
                'name' => 'HSL Color',
                'description' => 'Must be valid HSL color',
                'rule' => 'hsl_color'
            ],
            'currency_format' => [
                'name' => 'Currency Format',
                'description' => 'Must be valid currency format',
                'rule' => 'currency_format',
                'parameters' => ['currency', 'locale']
            ],
            'percentage_range' => [
                'name' => 'Percentage Range',
                'description' => 'Must be valid percentage within range',
                'rule' => 'percentage_range',
                'parameters' => ['min', 'max']
            ],
            'rating_range' => [
                'name' => 'Rating Range',
                'description' => 'Rating must be within valid range',
                'rule' => 'rating_range',
                'parameters' => ['min', 'max', 'allow_half']
            ],
            'likert_scale' => [
                'name' => 'Likert Scale',
                'description' => 'Must be valid Likert scale value',
                'rule' => 'likert_scale',
                'parameters' => ['scale_points', 'na_allowed']
            ],
            'nps_score' => [
                'name' => 'NPS Score',
                'description' => 'Must be valid NPS score (0-10)',
                'rule' => 'nps_score'
            ],
            'signature_required' => [
                'name' => 'Signature Required',
                'description' => 'Digital signature is required',
                'rule' => 'signature_required'
            ],
            'barcode_format' => [
                'name' => 'Barcode Format',
                'description' => 'Must be valid barcode format',
                'rule' => 'barcode_format',
                'parameters' => ['type']
            ],
            'qr_code' => [
                'name' => 'QR Code',
                'description' => 'Must be valid QR code data',
                'rule' => 'qr_code'
            ],
            'timezone' => [
                'name' => 'Timezone',
                'description' => 'Must be valid timezone',
                'rule' => 'timezone'
            ],
            'language_code' => [
                'name' => 'Language Code',
                'description' => 'Must be valid language code',
                'rule' => 'language_code'
            ],
            'country_code' => [
                'name' => 'Country Code',
                'description' => 'Must be valid country code',
                'rule' => 'country_code'
            ],
            'coordinate' => [
                'name' => 'Coordinate',
                'description' => 'Must be valid latitude/longitude',
                'rule' => 'coordinate',
                'parameters' => ['format']
            ],
            'matrix_complete' => [
                'name' => 'Matrix Complete',
                'description' => 'All matrix fields must be completed',
                'rule' => 'matrix_complete'
            ],
            'table_min_rows' => [
                'name' => 'Minimum Table Rows',
                'description' => 'Table must have minimum number of rows',
                'rule' => 'table_min_rows',
                'parameters' => ['min_rows']
            ],
            'table_max_rows' => [
                'name' => 'Maximum Table Rows',
                'description' => 'Table must not exceed maximum rows',
                'rule' => 'table_max_rows',
                'parameters' => ['max_rows']
            ],
            'formula_result' => [
                'name' => 'Formula Result',
                'description' => 'Formula field must have valid result',
                'rule' => 'formula_result'
            ],
            'conditional_logic' => [
                'name' => 'Conditional Logic',
                'description' => 'Field must meet conditional requirements',
                'rule' => 'conditional_logic',
                'parameters' => ['conditions']
            ]
        ];
    }

    /**
     * Get form themes
     */
    public function getFormThemes() {
        return [
            'default' => [
                'name' => 'Default',
                'description' => 'Clean, professional theme',
                'preview' => '/themes/default/preview.png',
                'colors' => ['primary' => '#007bff', 'secondary' => '#6c757d']
            ],
            'modern' => [
                'name' => 'Modern',
                'description' => 'Contemporary design with gradients',
                'preview' => '/themes/modern/preview.png',
                'colors' => ['primary' => '#667eea', 'secondary' => '#764ba2']
            ],
            'corporate' => [
                'name' => 'Corporate',
                'description' => 'Professional corporate styling',
                'preview' => '/themes/corporate/preview.png',
                'colors' => ['primary' => '#2c3e50', 'secondary' => '#34495e']
            ],
            'minimal' => [
                'name' => 'Minimal',
                'description' => 'Clean, minimal design',
                'preview' => '/themes/minimal/preview.png',
                'colors' => ['primary' => '#000000', 'secondary' => '#666666']
            ],
            'colorful' => [
                'name' => 'Colorful',
                'description' => 'Bright, engaging colors',
                'preview' => '/themes/colorful/preview.png',
                'colors' => ['primary' => '#ff6b6b', 'secondary' => '#4ecdc4']
            ]
        ];
    }

    /**
     * Save form field
     */
    public function saveFormField($data) {
        // Check if field exists
        $existing = $this->db->querySingle("
            SELECT id FROM form_fields
            WHERE form_id = ? AND field_name = ?
        ", [$data['form_id'], $data['field_name']]);

        if ($existing) {
            // Update existing field
            $this->db->update('form_fields', [
                'field_label' => $data['field_label'],
                'field_type' => $data['field_type'],
                'field_order' => $data['field_order'] ?? 0,
                'is_required' => $data['is_required'] ?? false,
                'field_options' => json_encode($data['field_options'] ?? []),
                'validation_rules' => json_encode($data['validation_rules'] ?? [])
            ], 'id = ?', [$existing['id']]);

            return $existing['id'];
        } else {
            // Create new field
            return $this->db->insert('form_fields', [
                'form_id' => $data['form_id'],
                'field_name' => $data['field_name'],
                'field_label' => $data['field_label'],
                'field_type' => $data['field_type'],
                'field_order' => $data['field_order'] ?? 0,
                'is_required' => $data['is_required'] ?? false,
                'field_options' => json_encode($data['field_options'] ?? []),
                'validation_rules' => json_encode($data['validation_rules'] ?? [])
            ]);
        }
    }

    /**
     * Delete form field
     */
    public function deleteFormField($fieldId) {
        return $this->db->delete('form_fields', 'id = ?', [$fieldId]);
    }

    /**
     * Update field order
     */
    public function updateFieldOrder($formId, $fieldOrders) {
        foreach ($fieldOrders as $fieldId => $order) {
            $this->db->update('form_fields', [
                'field_order' => $order
            ], 'id = ? AND form_id = ?', [$fieldId, $formId]);
        }

        return true;
    }

    /**
     * Get form field by ID
     */
    public function getFormField($fieldId) {
        return $this->db->querySingle("
            SELECT * FROM form_fields WHERE id = ?
        ", [$fieldId]);
    }

    /**
     * Get form fields
     */
    public function getFormFields($formId) {
        return $this->db->query("
            SELECT * FROM form_fields
            WHERE form_id = ? ORDER BY field_order ASC
        ", [$formId]);
    }

    /**
     * Validate field configuration
     */
    public function validateFieldConfig($fieldData) {
        $errors = [];

        if (empty($fieldData['field_name'])) {
            $errors[] = 'Field name is required';
        }

        if (empty($fieldData['field_label'])) {
            $errors[] = 'Field label is required';
        }

        if (empty($fieldData['field_type'])) {
            $errors[] = 'Field type is required';
        }

        // Check if field name is unique within the form
        if (!empty($fieldData['field_name']) && !empty($fieldData['form_id'])) {
            $existing = $this->db->querySingle("
                SELECT id FROM form_fields
                WHERE form_id = ? AND field_name = ? AND id != ?
            ", [
                $fieldData['form_id'],
                $fieldData['field_name'],
                $fieldData['id'] ?? 0
            ]);

            if ($existing) {
                $errors[] = 'Field name must be unique within the form';
            }
        }

        return $errors;
    }

    /**
     * Get current user
     */
    private function getCurrentUser() {
        // This should be implemented to get the current user from session/auth
        return $_SESSION['user'] ?? null;
    }
}
