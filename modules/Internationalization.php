<?php
/**
 * TPT Free ERP - Internationalization & Localization Module
 * Complete multi-language support, currency handling, and regional compliance
 */

class Internationalization extends BaseController {
    private $db;
    private $user;
    private $currentLanguage;
    private $currentLocale;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
        $this->currentLanguage = $this->getCurrentLanguage();
        $this->currentLocale = $this->getCurrentLocale();
    }

    /**
     * Main internationalization dashboard
     */
    public function index() {
        $this->requirePermission('i18n.view');

        $data = [
            'title' => 'Internationalization & Localization',
            'language_overview' => $this->getLanguageOverview(),
            'translation_status' => $this->getTranslationStatus(),
            'regional_settings' => $this->getRegionalSettings(),
            'currency_management' => $this->getCurrencyManagement(),
            'timezone_handling' => $this->getTimezoneHandling(),
            'compliance_regions' => $this->getComplianceRegions(),
            'localization_metrics' => $this->getLocalizationMetrics()
        ];

        $this->render('modules/i18n/dashboard', $data);
    }

    /**
     * Language management
     */
    public function languages() {
        $this->requirePermission('i18n.languages.view');

        $data = [
            'title' => 'Language Management',
            'supported_languages' => $this->getSupportedLanguages(),
            'language_packs' => $this->getLanguagePacks(),
            'translation_progress' => $this->getTranslationProgress(),
            'language_settings' => $this->getLanguageSettings(),
            'rtl_languages' => $this->getRTLLanguages(),
            'fallback_languages' => $this->getFallbackLanguages()
        ];

        $this->render('modules/i18n/languages', $data);
    }

    /**
     * Translation management
     */
    public function translations() {
        $this->requirePermission('i18n.translations.view');

        $filters = [
            'language' => $_GET['language'] ?? $this->currentLanguage,
            'module' => $_GET['module'] ?? null,
            'status' => $_GET['status'] ?? 'all',
            'search' => $_GET['search'] ?? null
        ];

        $translations = $this->getTranslations($filters);

        $data = [
            'title' => 'Translation Management',
            'translations' => $translations,
            'filters' => $filters,
            'translation_keys' => $this->getTranslationKeys(),
            'translation_modules' => $this->getTranslationModules(),
            'translation_stats' => $this->getTranslationStats($filters),
            'bulk_actions' => $this->getBulkActions()
        ];

        $this->render('modules/i18n/translations', $data);
    }

    /**
     * Currency management
     */
    public function currencies() {
        $this->requirePermission('i18n.currencies.view');

        $data = [
            'title' => 'Currency Management',
            'supported_currencies' => $this->getSupportedCurrencies(),
            'exchange_rates' => $this->getExchangeRates(),
            'currency_formatting' => $this->getCurrencyFormatting(),
            'currency_conversion' => $this->getCurrencyConversion(),
            'tax_rates' => $this->getTaxRates(),
            'currency_history' => $this->getCurrencyHistory()
        ];

        $this->render('modules/i18n/currencies', $data);
    }

    /**
     * Timezone and date handling
     */
    public function timezones() {
        $this->requirePermission('i18n.timezones.view');

        $data = [
            'title' => 'Timezone & Date Management',
            'supported_timezones' => $this->getSupportedTimezones(),
            'date_formats' => $this->getDateFormats(),
            'time_formats' => $this->getTimeFormats(),
            'calendar_systems' => $this->getCalendarSystems(),
            'business_hours' => $this->getBusinessHours(),
            'holiday_management' => $this->getHolidayManagement()
        ];

        $this->render('modules/i18n/timezones', $data);
    }

    /**
     * Regional compliance
     */
    public function compliance() {
        $this->requirePermission('i18n.compliance.view');

        $data = [
            'title' => 'Regional Compliance',
            'compliance_frameworks' => $this->getComplianceFrameworks(),
            'regional_requirements' => $this->getRegionalRequirements(),
            'data_localization' => $this->getDataLocalization(),
            'privacy_laws' => $this->getPrivacyLaws(),
            'tax_compliance' => $this->getTaxCompliance(),
            'industry_standards' => $this->getIndustryStandards()
        ];

        $this->render('modules/i18n/compliance', $data);
    }

    /**
     * Number and measurement formatting
     */
    public function formatting() {
        $this->requirePermission('i18n.formatting.view');

        $data = [
            'title' => 'Number & Measurement Formatting',
            'number_formats' => $this->getNumberFormats(),
            'measurement_systems' => $this->getMeasurementSystems(),
            'address_formats' => $this->getAddressFormats(),
            'phone_formats' => $this->getPhoneFormats(),
            'postal_codes' => $this->getPostalCodes(),
            'name_formats' => $this->getNameFormats()
        ];

        $this->render('modules/i18n/formatting', $data);
    }

    /**
     * Content localization
     */
    public function content() {
        $this->requirePermission('i18n.content.view');

        $data = [
            'title' => 'Content Localization',
            'localized_content' => $this->getLocalizedContent(),
            'content_variants' => $this->getContentVariants(),
            'multilingual_seo' => $this->getMultilingualSEO(),
            'content_workflow' => $this->getContentWorkflow(),
            'translation_memory' => $this->getTranslationMemory(),
            'quality_assurance' => $this->getQualityAssurance()
        ];

        $this->render('modules/i18n/content', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getCurrentLanguage() {
        // Get from user preferences, session, or browser
        return $_SESSION['language'] ?? $_COOKIE['language'] ?? 'en';
    }

    private function getCurrentLocale() {
        return $_SESSION['locale'] ?? $_COOKIE['locale'] ?? 'en_US';
    }

    private function getLanguageOverview() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT l.language_code) as total_languages,
                COUNT(CASE WHEN l.is_active = true THEN 1 END) as active_languages,
                COUNT(DISTINCT t.translation_key) as total_keys,
                COUNT(t.id) as total_translations,
                ROUND((COUNT(t.id) / (COUNT(DISTINCT l.language_code) * COUNT(DISTINCT t.translation_key))) * 100, 2) as completion_percentage,
                COUNT(DISTINCT u.id) as users_by_language
            FROM languages l
            LEFT JOIN translations t ON t.language_code = l.language_code
            LEFT JOIN users u ON u.language = l.language_code
            WHERE l.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTranslationStatus() {
        return $this->db->query("
            SELECT
                l.language_code,
                l.language_name,
                COUNT(t.id) as translated_keys,
                COUNT(DISTINCT tk.id) as total_keys,
                ROUND((COUNT(t.id) / COUNT(DISTINCT tk.id)) * 100, 2) as completion_percentage,
                MAX(t.last_updated) as last_update,
                COUNT(CASE WHEN t.needs_review = true THEN 1 END) as needs_review
            FROM languages l
            LEFT JOIN translations t ON t.language_code = l.language_code
            CROSS JOIN translation_keys tk
            WHERE l.company_id = ? AND l.is_active = true
            GROUP BY l.language_code, l.language_name
            ORDER BY completion_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getRegionalSettings() {
        return $this->db->query("
            SELECT
                rs.region_code,
                rs.region_name,
                rs.default_language,
                rs.default_currency,
                rs.default_timezone,
                rs.date_format,
                rs.time_format,
                rs.number_format,
                rs.is_active
            FROM regional_settings rs
            WHERE rs.company_id = ?
            ORDER BY rs.region_name ASC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyManagement() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT c.currency_code) as supported_currencies,
                COUNT(er.id) as exchange_rate_pairs,
                AVG(er.exchange_rate) as avg_exchange_rate,
                MAX(er.last_updated) as last_rate_update,
                COUNT(CASE WHEN c.is_crypto = true THEN 1 END) as crypto_currencies,
                COUNT(CASE WHEN c.is_active = true THEN 1 END) as active_currencies
            FROM currencies c
            LEFT JOIN exchange_rates er ON er.from_currency = c.currency_code OR er.to_currency = c.currency_code
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getTimezoneHandling() {
        return $this->db->query("
            SELECT
                tz.timezone_name,
                tz.utc_offset,
                tz.daylight_saving,
                COUNT(u.id) as users_count,
                tz.is_default
            FROM timezones tz
            LEFT JOIN users u ON u.timezone = tz.timezone_name
            WHERE tz.company_id = ?
            GROUP BY tz.timezone_name, tz.utc_offset, tz.daylight_saving, tz.is_default
            ORDER BY tz.timezone_name ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceRegions() {
        return [
            'gdpr' => [
                'name' => 'GDPR Region',
                'countries' => ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'],
                'requirements' => ['Data protection', 'Privacy by design', 'Data subject rights'],
                'compliance_level' => 'Strict'
            ],
            'ccpa' => [
                'name' => 'CCPA Region',
                'countries' => ['US'],
                'requirements' => ['Consumer rights', 'Data minimization', 'Security measures'],
                'compliance_level' => 'High'
            ],
            'pipeda' => [
                'name' => 'PIPEDA Region',
                'countries' => ['CA'],
                'requirements' => ['Personal information protection', 'Privacy policies', 'Breach notification'],
                'compliance_level' => 'High'
            ],
            'lgpd' => [
                'name' => 'LGPD Region',
                'countries' => ['BR'],
                'requirements' => ['Data protection', 'Privacy impact assessment', 'Data subject rights'],
                'compliance_level' => 'High'
            ]
        ];
    }

    private function getLocalizationMetrics() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT u.language) as languages_in_use,
                COUNT(DISTINCT u.timezone) as timezones_in_use,
                COUNT(DISTINCT u.currency) as currencies_in_use,
                AVG(u.localization_score) as avg_localization_score,
                COUNT(CASE WHEN u.needs_localization = true THEN 1 END) as needs_localization,
                MAX(u.last_localization_update) as last_update
            FROM users u
            WHERE u.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getSupportedLanguages() {
        return $this->db->query("
            SELECT
                l.*,
                COUNT(t.id) as translation_count,
                COUNT(DISTINCT u.id) as user_count,
                l.is_active,
                l.is_rtl,
                l.plural_rules
            FROM languages l
            LEFT JOIN translations t ON t.language_code = l.language_code
            LEFT JOIN users u ON u.language = l.language_code
            WHERE l.company_id = ?
            GROUP BY l.id
            ORDER BY l.language_name ASC
        ", [$this->user['company_id']]);
    }

    private function getLanguagePacks() {
        return $this->db->query("
            SELECT
                lp.*,
                l.language_name,
                lp.version,
                lp.file_size,
                lp.install_date,
                lp.last_update,
                lp.is_complete
            FROM language_packs lp
            JOIN languages l ON lp.language_code = l.language_code
            WHERE lp.company_id = ?
            ORDER BY lp.install_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTranslationProgress() {
        return $this->db->query("
            SELECT
                l.language_code,
                l.language_name,
                COUNT(DISTINCT tk.id) as total_keys,
                COUNT(t.id) as translated_keys,
                ROUND((COUNT(t.id) / COUNT(DISTINCT tk.id)) * 100, 2) as completion_percentage,
                COUNT(CASE WHEN t.needs_review = true THEN 1 END) as needs_review,
                MAX(t.last_updated) as last_update
            FROM languages l
            CROSS JOIN translation_keys tk
            LEFT JOIN translations t ON t.translation_key = tk.translation_key AND t.language_code = l.language_code
            WHERE l.company_id = ? AND l.is_active = true
            GROUP BY l.language_code, l.language_name
            ORDER BY completion_percentage DESC
        ", [$this->user['company_id']]);
    }

    private function getLanguageSettings() {
        return $this->db->querySingle("
            SELECT * FROM language_settings
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getRTLLanguages() {
        return ['ar', 'he', 'fa', 'ur', 'yi'];
    }

    private function getFallbackLanguages() {
        return $this->db->query("
            SELECT
                fl.language_code,
                fl.fallback_language,
                l1.language_name as primary_name,
                l2.language_name as fallback_name
            FROM fallback_languages fl
            JOIN languages l1 ON fl.language_code = l1.language_code
            JOIN languages l2 ON fl.fallback_language = l2.language_code
            WHERE fl.company_id = ?
            ORDER BY l1.language_name ASC
        ", [$this->user['company_id']]);
    }

    private function getTranslations($filters) {
        $where = ["t.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['language']) {
            $where[] = "t.language_code = ?";
            $params[] = $filters['language'];
        }

        if ($filters['module']) {
            $where[] = "tk.module_name = ?";
            $params[] = $filters['module'];
        }

        if ($filters['status'] === 'untranslated') {
            $where[] = "t.translation_text IS NULL";
        } elseif ($filters['status'] === 'needs_review') {
            $where[] = "t.needs_review = true";
        }

        if ($filters['search']) {
            $where[] = "(tk.translation_key LIKE ? OR t.translation_text LIKE ? OR tk.default_text LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                t.*,
                tk.translation_key,
                tk.module_name,
                tk.default_text,
                tk.context,
                tk.last_updated as key_last_updated,
                l.language_name
            FROM translation_keys tk
            LEFT JOIN translations t ON tk.translation_key = t.translation_key
                AND t.language_code = ?
            LEFT JOIN languages l ON t.language_code = l.language_code
            WHERE $whereClause
            ORDER BY tk.module_name ASC, tk.translation_key ASC
        ", array_merge([$filters['language']], $params));
    }

    private function getTranslationKeys() {
        return $this->db->query("
            SELECT
                tk.*,
                COUNT(t.id) as translation_count,
                COUNT(CASE WHEN t.translation_text IS NOT NULL THEN 1 END) as completed_translations,
                tk.last_updated
            FROM translation_keys tk
            LEFT JOIN translations t ON tk.translation_key = t.translation_key
            WHERE tk.company_id = ?
            GROUP BY tk.id
            ORDER BY tk.module_name ASC, tk.translation_key ASC
        ", [$this->user['company_id']]);
    }

    private function getTranslationModules() {
        return [
            'core' => 'Core System',
            'auth' => 'Authentication',
            'dashboard' => 'Dashboard',
            'inventory' => 'Inventory Management',
            'sales' => 'Sales & CRM',
            'hr' => 'Human Resources',
            'finance' => 'Finance & Accounting',
            'reporting' => 'Reporting',
            'project' => 'Project Management',
            'quality' => 'Quality Management',
            'asset' => 'Asset Management',
            'field' => 'Field Service',
            'lms' => 'Learning Management',
            'iot' => 'IoT Integration',
            'website' => 'Website & Social',
            'admin' => 'Administration'
        ];
    }

    private function getTranslationStats($filters) {
        $where = ["company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['language']) {
            $where[] = "language_code = ?";
            $params[] = $filters['language'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_translations,
                COUNT(CASE WHEN translation_text IS NOT NULL THEN 1 END) as completed_translations,
                COUNT(CASE WHEN needs_review = true THEN 1 END) as needs_review,
                COUNT(CASE WHEN last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recently_updated,
                AVG(translation_quality_score) as avg_quality_score
            FROM translations
            WHERE $whereClause
        ", $params);
    }

    private function getBulkActions() {
        return [
            'export' => 'Export Translations',
            'import' => 'Import Translations',
            'copy_from' => 'Copy from Language',
            'mark_review' => 'Mark for Review',
            'delete' => 'Delete Translations',
            'validate' => 'Validate Translations'
        ];
    }

    private function getSupportedCurrencies() {
        return $this->db->query("
            SELECT
                c.*,
                c.currency_code,
                c.currency_name,
                c.currency_symbol,
                c.decimal_places,
                c.is_active,
                c.is_crypto,
                COUNT(er.id) as exchange_rate_count,
                MAX(er.last_updated) as last_rate_update
            FROM currencies c
            LEFT JOIN exchange_rates er ON er.from_currency = c.currency_code OR er.to_currency = c.currency_code
            WHERE c.company_id = ?
            GROUP BY c.id
            ORDER BY c.currency_name ASC
        ", [$this->user['company_id']]);
    }

    private function getExchangeRates() {
        return $this->db->query("
            SELECT
                er.*,
                c1.currency_name as from_currency_name,
                c2.currency_name as to_currency_name,
                er.exchange_rate,
                er.last_updated,
                er.source,
                er.is_manual
            FROM exchange_rates er
            JOIN currencies c1 ON er.from_currency = c1.currency_code
            JOIN currencies c2 ON er.to_currency = c2.currency_code
            WHERE er.company_id = ?
            ORDER BY er.last_updated DESC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyFormatting() {
        return $this->db->query("
            SELECT
                cf.*,
                c.currency_name,
                cf.decimal_separator,
                cf.thousands_separator,
                cf.currency_position,
                cf.negative_format,
                cf.zero_format
            FROM currency_formatting cf
            JOIN currencies c ON cf.currency_code = c.currency_code
            WHERE cf.company_id = ?
            ORDER BY c.currency_name ASC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyConversion() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_conversions,
                COUNT(DISTINCT from_currency) as currencies_from,
                COUNT(DISTINCT to_currency) as currencies_to,
                AVG(conversion_fee_percentage) as avg_conversion_fee,
                MAX(last_conversion) as last_conversion_date,
                SUM(conversion_volume) as total_volume
            FROM currency_conversions
            WHERE company_id = ? AND conversion_date >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getTaxRates() {
        return $this->db->query("
            SELECT
                tr.*,
                tr.country_code,
                tr.region_code,
                tr.tax_type,
                tr.tax_rate,
                tr.is_compound,
                tr.effective_date,
                tr.expiry_date,
                tr.is_active
            FROM tax_rates tr
            WHERE tr.company_id = ?
            ORDER BY tr.country_code ASC, tr.region_code ASC
        ", [$this->user['company_id']]);
    }

    private function getCurrencyHistory() {
        return $this->db->query("
            SELECT
                ch.*,
                c1.currency_name as from_currency_name,
                c2.currency_name as to_currency_name,
                ch.old_rate,
                ch.new_rate,
                ch.change_percentage,
                ch.change_date,
                ch.change_reason
            FROM currency_history ch
            JOIN currencies c1 ON ch.from_currency = c1.currency_code
            JOIN currencies c2 ON ch.to_currency = c2.currency_code
            WHERE ch.company_id = ?
            ORDER BY ch.change_date DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getSupportedTimezones() {
        return $this->db->query("
            SELECT
                tz.*,
                tz.timezone_name,
                tz.utc_offset,
                tz.daylight_saving,
                tz.country_code,
                COUNT(u.id) as user_count,
                tz.is_default
            FROM timezones tz
            LEFT JOIN users u ON u.timezone = tz.timezone_name
            WHERE tz.company_id = ?
            GROUP BY tz.id
            ORDER BY tz.timezone_name ASC
        ", [$this->user['company_id']]);
    }

    private function getDateFormats() {
        return [
            'us' => [
                'name' => 'US Format',
                'format' => 'm/d/Y',
                'example' => '12/31/2023',
                'regions' => ['US', 'CA', 'MX']
            ],
            'eu' => [
                'name' => 'European Format',
                'format' => 'd/m/Y',
                'example' => '31/12/2023',
                'regions' => ['EU', 'UK', 'AU', 'NZ']
            ],
            'iso' => [
                'name' => 'ISO Format',
                'format' => 'Y-m-d',
                'example' => '2023-12-31',
                'regions' => ['Global']
            ],
            'jp' => [
                'name' => 'Japanese Format',
                'format' => 'Y年m月d日',
                'example' => '2023年12月31日',
                'regions' => ['JP']
            ]
        ];
    }

    private function getTimeFormats() {
        return [
            '12h' => [
                'name' => '12-Hour Format',
                'format' => 'h:i:s A',
                'example' => '03:45:30 PM',
                'regions' => ['US', 'UK', 'AU']
            ],
            '24h' => [
                'name' => '24-Hour Format',
                'format' => 'H:i:s',
                'example' => '15:45:30',
                'regions' => ['EU', 'JP', 'CN', 'KR']
            ]
        ];
    }

    private function getCalendarSystems() {
        return [
            'gregorian' => [
                'name' => 'Gregorian',
                'description' => 'Western calendar system',
                'regions' => ['Global']
            ],
            'japanese' => [
                'name' => 'Japanese',
                'description' => 'Japanese imperial calendar',
                'regions' => ['JP']
            ],
            'chinese' => [
                'name' => 'Chinese Lunar',
                'description' => 'Traditional Chinese calendar',
                'regions' => ['CN', 'TW', 'HK']
            ],
            'islamic' => [
                'name' => 'Islamic',
                'description' => 'Islamic lunar calendar',
                'regions' => ['Middle East', 'North Africa']
            ]
        ];
    }

    private function getBusinessHours() {
        return $this->db->query("
            SELECT
                bh.*,
                bh.region_code,
                bh.day_of_week,
                bh.open_time,
                bh.close_time,
                bh.is_holiday,
                bh.timezone_name
            FROM business_hours bh
            WHERE bh.company_id = ?
            ORDER BY bh.region_code ASC, bh.day_of_week ASC
        ", [$this->user['company_id']]);
    }

    private function getHolidayManagement() {
        return $this->db->query("
            SELECT
                hm.*,
                hm.holiday_name,
                hm.holiday_date,
                hm.country_code,
                hm.region_code,
                hm.is_observed,
                hm.description
            FROM holiday_management hm
            WHERE hm.company_id = ?
            ORDER BY hm.holiday_date ASC
        ", [$this->user['company_id']]);
    }

    private function getComplianceFrameworks() {
        return $this->db->query("
            SELECT
                cf.*,
                cf.framework_name,
                cf.region_code,
                cf.compliance_level,
                cf.last_assessment,
                cf.next_assessment,
                cf.responsible_person,
                cf.documentation_url
            FROM compliance_frameworks cf
            WHERE cf.company_id = ?
            ORDER BY cf.framework_name ASC
        ", [$this->user['company_id']]);
    }

    private function getRegionalRequirements() {
        return $this->db->query("
            SELECT
                rr.*,
                rr.region_code,
                rr.requirement_type,
                rr.description,
                rr.is_mandatory,
                rr.implementation_status,
                rr.deadline,
                rr.responsible_person
            FROM regional_requirements rr
            WHERE rr.company_id = ?
            ORDER BY rr.region_code ASC, rr.requirement_type ASC
        ", [$this->user['company_id']]);
    }

    private function getDataLocalization() {
        return $this->db->query("
            SELECT
                dl.*,
                dl.region_code,
                dl.data_type,
                dl.storage_location,
                dl.backup_location,
                dl.compliance_status,
                dl.last_audit,
                dl.next_audit
            FROM data_localization dl
            WHERE dl.company_id = ?
            ORDER BY dl.region_code ASC, dl.data_type ASC
        ", [$this->user['company_id']]);
    }

    private function getPrivacyLaws() {
        return $this->db->query("
            SELECT
                pl.*,
                pl.law_name,
                pl.region_code,
                pl.effective_date,
                pl.key_requirements,
                pl.compliance_status,
                pl.last_review,
                pl.next_review
            FROM privacy_laws pl
            WHERE pl.company_id = ?
            ORDER BY pl.region_code ASC, pl.effective_date DESC
        ", [$this->user['company_id']]);
    }

    private function getTaxCompliance() {
        return $this->db->query("
            SELECT
                tc.*,
                tc.country_code,
                tc.tax_type,
                tc.compliance_status,
                tc.last_filing,
                tc.next_filing,
                tc.responsible_person,
                tc.documentation_status
            FROM tax_compliance tc
            WHERE tc.company_id = ?
            ORDER BY tc.country_code ASC, tc.next_filing ASC
        ", [$this->user['company_id']]);
    }

    private function getIndustryStandards() {
        return $this->db->query("
            SELECT
                ist.*,
                ist.standard_name,
                ist.industry_code,
                ist.compliance_level,
                ist.certification_body,
                ist.last_audit,
                ist.next_audit,
                ist.certificate_expiry
            FROM industry_standards ist
            WHERE ist.company_id = ?
            ORDER BY ist.industry_code ASC, ist.standard_name ASC
        ", [$this->user['company_id']]);
    }

    private function getNumberFormats() {
        return $this->db->query("
            SELECT
                nf.*,
                nf.locale_code,
                nf.decimal_separator,
                nf.thousands_separator,
                nf.grouping_size,
                nf.negative_format,
                nf.currency_symbol,
                nf.currency_position
            FROM number_formats nf
            WHERE nf.company_id = ?
            ORDER BY nf.locale_code ASC
        ", [$this->user['company_id']]);
    }

    private function getMeasurementSystems() {
        return [
            'metric' => [
                'name' => 'Metric System',
                'units' => [
                    'length' => 'meters',
                    'weight' => 'kilograms',
                    'volume' => 'liters',
                    'temperature' => 'celsius'
                ],
                'regions' => ['EU', 'Asia', 'Australia', 'Most of world']
            ],
            'imperial' => [
                'name' => 'Imperial System',
                'units' => [
                    'length' => 'feet/inches',
                    'weight' => 'pounds',
                    'volume' => 'gallons',
                    'temperature' => 'fahrenheit'
                ],
                'regions' => ['US', 'UK (partial)', 'Canada (partial)']
            ],
            'customary' => [
                'name' => 'US Customary',
                'units' => [
                    'length' => 'feet/yards/miles',
                    'weight' => 'ounces/pounds/tons',
                    'volume' => 'cups/pints/quarts/gallons',
                    'temperature' => 'fahrenheit'
                ],
                'regions' => ['US']
            ]
        ];
    }

    private function getAddressFormats() {
        return $this->db->query("
            SELECT
                af.*,
                af.country_code,
                af.address_format,
                af.required_fields,
                af.postal_code_format,
                af.postal_code_validation
            FROM address_formats af
            WHERE af.company_id = ?
            ORDER BY af.country_code ASC
        ", [$this->user['company_id']]);
    }

    private function getPhoneFormats() {
        return $this->db->query("
            SELECT
                pf.*,
                pf.country_code,
                pf.phone_format,
                pf.area_code_required,
                pf.mobile_format,
                pf.international_prefix
            FROM phone_formats pf
            WHERE pf.company_id = ?
            ORDER BY pf.country_code ASC
        ", [$this->user['company_id']]);
    }

    private function getPostalCodes() {
        return $this->db->query("
            SELECT
                pc.*,
                pc.country_code,
                pc.postal_code_format,
                pc.validation_regex,
                pc.example_codes
            FROM postal_codes pc
            WHERE pc.company_id = ?
            ORDER BY pc.country_code ASC
        ", [$this->user['company_id']]);
    }

    private function getNameFormats() {
        return $this->db->query("
            SELECT
                nf.*,
                nf.locale_code,
                nf.name_order,
                nf.middle_name_handling,
                nf.title_handling,
                nf.suffix_handling
            FROM name_formats nf
            WHERE nf.company_id = ?
            ORDER BY nf.locale_code ASC
        ", [$this->user['company_id']]);
    }

    private function getLocalizedContent() {
        return $this->db->query("
            SELECT
                lc.*,
                lc.content_id,
                lc.language_code,
                lc.translated_title,
                lc.translated_content,
                lc.translation_status,
                lc.last_translator,
                lc.translation_quality,
                lc.seo_optimized
            FROM localized_content lc
            WHERE lc.company_id = ?
            ORDER BY lc.language_code ASC, lc.content_id ASC
        ", [$this->user['company_id']]);
    }

    private function getContentVariants() {
        return $this->db->query("
            SELECT
                cv.*,
                cv.original_content_id,
                cv.variant_type,
                cv.language_code,
                cv.cultural_adaptations,
                cv.target_audience,
                cv.performance_metrics
            FROM content_variants cv
            WHERE cv.company_id = ?
            ORDER BY cv.original_content_id ASC, cv.language_code ASC
        ", [$this->user['company_id']]);
    }

    private function getMultilingualSEO() {
        return $this->db->query("
            SELECT
                ms.*,
                ms.page_url,
                ms.language_code,
                ms.meta_title,
                ms.meta_description,
                ms.canonical_url,
                ms.hreflang_tags,
                ms.keyword_optimization_score
            FROM multilingual_seo ms
            WHERE ms.company_id = ?
            ORDER BY ms.page_url ASC, ms.language_code ASC
        ", [$this->user['company_id']]);
    }

    private function getContentWorkflow() {
        return $this->db->query("
            SELECT
                cw.*,
                cw.content_id,
                cw.workflow_stage,
                cw.assigned_translator,
                cw.deadline,
                cw.quality_score,
                cw.approval_status
            FROM content_workflow cw
            WHERE cw.company_id = ?
            ORDER BY cw.deadline ASC
        ", [$this->user['company_id']]);
    }

    private function getTranslationMemory() {
        return $this->db->query("
            SELECT
                tm.*,
                tm.source_text,
                tm.target_text,
                tm.source_language,
                tm.target_language,
                tm.usage_count,
                tm.last_used,
                tm.quality_score
            FROM translation_memory tm
            WHERE tm.company_id = ?
            ORDER BY tm.usage_count DESC
        ", [$this->user['company_id']]);
    }

    private function getQualityAssurance() {
        return $this->db->query("
            SELECT
                qa.*,
                qa.translation_id,
                qa.qa_type,
                qa.qa_score,
                qa.issues_found,
                qa.reviewer_comments,
                qa.qa_date
            FROM quality_assurance qa
            WHERE qa.company_id = ?
            ORDER BY qa.qa_date DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function getTranslation() {
        $key = $_GET['key'] ?? '';
        $language = $_GET['language'] ?? $this->currentLanguage;

        if (!$key) {
            $this->jsonResponse(['error' => 'Translation key required'], 400);
        }

        $translation = $this->db->querySingle("
            SELECT translation_text FROM translations
            WHERE translation_key = ? AND language_code = ? AND company_id = ?
        ", [$key, $language, $this->user['company_id']]);

        if ($translation) {
            $this->jsonResponse([
                'success' => true,
                'translation' => $translation['translation_text']
            ]);
        } else {
            // Try fallback language
            $fallback = $this->getFallbackLanguage($language);
            if ($fallback) {
                $fallbackTranslation = $this->db->querySingle("
                    SELECT translation_text FROM translations
                    WHERE translation_key = ? AND language_code = ? AND company_id = ?
                ", [$key, $fallback, $this->user['company_id']]);

                if ($fallbackTranslation) {
                    $this->jsonResponse([
                        'success' => true,
                        'translation' => $fallbackTranslation['translation_text'],
                        'fallback' => true
                    ]);
                }
            }

            $this->jsonResponse([
                'success' => false,
                'error' => 'Translation not found'
            ], 404);
        }
    }

    public function setUserLanguage() {
        $language = $_POST['language'] ?? '';
        $locale = $_POST['locale'] ?? '';

        if (!$language) {
            $this->jsonResponse(['error' => 'Language code required'], 400);
        }

        // Validate language exists
        $langExists = $this->db->querySingle("
            SELECT id FROM languages
            WHERE language_code = ? AND company_id = ? AND is_active = true
        ", [$language, $this->user['company_id']]);

        if (!$langExists) {
            $this->jsonResponse(['error' => 'Language not supported'], 400);
        }

        // Update user preferences
        $this->db->update('users', [
            'language' => $language,
            'locale' => $locale,
            'last_localization_update' => date('Y-m-d H:i:s')
        ], 'id = ?', [$this->user['id']]);

        // Update session
        $_SESSION['language'] = $language;
        $_SESSION['locale'] = $locale;

        $this->jsonResponse([
            'success' => true,
            'message' => 'Language updated successfully'
        ]);
    }

    public function convertCurrency() {
        $amount = (float)($_POST['amount'] ?? 0);
        $fromCurrency = $_POST['from_currency'] ?? '';
        $toCurrency = $_POST['to_currency'] ?? '';

        if (!$amount || !$fromCurrency || !$toCurrency) {
            $this->jsonResponse(['error' => 'Amount and currencies required'], 400);
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);

        if (!$rate) {
            $this->jsonResponse(['error' => 'Exchange rate not available'], 404);
        }

        $convertedAmount = $amount * $rate;

        // Log conversion
        $this->db->insert('currency_conversions', [
            'company_id' => $this->user['company_id'],
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'original_amount' => $amount,
            'converted_amount' => $convertedAmount,
            'exchange_rate' => $rate,
            'conversion_date' => date('Y-m-d H:i:s'),
            'user_id' => $this->user['id']
        ]);

        $this->jsonResponse([
            'success' => true,
            'original_amount' => $amount,
            'converted_amount' => $convertedAmount,
            'exchange_rate' => $rate,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency
        ]);
    }

    public function getLocalizedDate() {
        $timestamp = $_GET['timestamp'] ?? time();
        $timezone = $_GET['timezone'] ?? $this->user['timezone'] ?? 'UTC';
        $format = $_GET['format'] ?? 'medium';

        try {
            $date = new DateTime('@' . $timestamp);
            $date->setTimezone(new DateTimeZone($timezone));

            $formats = [
                'short' => 'n/j/Y',
                'medium' => 'M j, Y',
                'long' => 'F j, Y',
                'full' => 'l, F j, Y'
            ];

            $formattedDate = $date->format($formats[$format] ?? $formats['medium']);

            $this->jsonResponse([
                'success' => true,
                'timestamp' => $timestamp,
                'formatted_date' => $formattedDate,
                'timezone' => $timezone,
                'locale' => $this->currentLocale
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    private function getFallbackLanguage($language) {
        $fallback = $this->db->querySingle("
            SELECT fallback_language FROM fallback_languages
            WHERE language_code = ? AND company_id = ?
        ", [$language, $this->user['company_id']]);

        return $fallback ? $fallback['fallback_language'] : null;
    }

    private function getExchangeRate($fromCurrency, $toCurrency) {
        $rate = $this->db->querySingle("
            SELECT exchange_rate FROM exchange_rates
            WHERE from_currency = ? AND to_currency = ? AND company_id = ?
            ORDER BY last_updated DESC
            LIMIT 1
        ", [$fromCurrency, $toCurrency, $this->user['company_id']]);

        return $rate ? (float)$rate['exchange_rate'] : null;
    }

    public function __($key, $language = null) {
        $language = $language ?? $this->currentLanguage;

        $translation = $this->db->querySingle("
            SELECT translation_text FROM translations
            WHERE translation_key = ? AND language_code = ? AND company_id = ?
        ", [$key, $language, $this->user['company_id']]);

        if ($translation && $translation['translation_text']) {
            return $translation['translation_text'];
        }

        // Try fallback
        $fallback = $this->getFallbackLanguage($language);
        if ($fallback) {
            $fallbackTranslation = $this->db->querySingle("
                SELECT translation_text FROM translations
                WHERE translation_key = ? AND language_code = ? AND company_id = ?
            ", [$key, $fallback, $this->user['company_id']]);

            if ($fallbackTranslation && $fallbackTranslation['translation_text']) {
                return $fallbackTranslation['translation_text'];
            }
        }

        // Return key if no translation found
        return $key;
    }
}
?>
