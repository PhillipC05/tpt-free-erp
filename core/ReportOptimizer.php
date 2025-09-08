<?php
/**
 * TPT Free ERP - AI-Powered Report Optimization System
 * Provides intelligent suggestions for report improvements and optimizations
 */

class ReportOptimizer {
    private $db;
    private $aiConnector;
    private $user;

    public function __construct() {
        $this->db = new Database();
        $this->aiConnector = new AIConnectors();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Analyze report and provide optimization suggestions
     */
    public function analyzeReport($reportId) {
        $report = $this->getReportDetails($reportId);
        if (!$report) {
            throw new Exception("Report not found");
        }

        $analysis = [
            'report_id' => $reportId,
            'current_metrics' => $this->getReportMetrics($reportId),
            'optimization_suggestions' => $this->generateOptimizationSuggestions($report),
            'chart_recommendations' => $this->recommendChartTypes($report),
            'layout_suggestions' => $this->suggestLayoutImprovements($report),
            'performance_optimizations' => $this->identifyPerformanceIssues($report),
            'accessibility_improvements' => $this->suggestAccessibilityEnhancements($report),
            'generated_at' => date('Y-m-d H:i:s')
        ];

        // Store analysis results
        $this->storeAnalysisResults($analysis);

        return $analysis;
    }

    /**
     * Generate AI-powered optimization suggestions
     */
    private function generateOptimizationSuggestions($report) {
        $suggestions = [];

        // Analyze data volume and suggest optimizations
        if ($report['data_volume'] > 10000) {
            $suggestions[] = [
                'type' => 'performance',
                'priority' => 'high',
                'title' => 'Large Dataset Optimization',
                'description' => 'Consider implementing data pagination or sampling for better performance',
                'implementation_effort' => 'medium',
                'expected_impact' => 'high'
            ];
        }

        // Analyze field usage and suggest cleanup
        $unusedFields = $this->identifyUnusedFields($report);
        if (!empty($unusedFields)) {
            $suggestions[] = [
                'type' => 'maintenance',
                'priority' => 'medium',
                'title' => 'Remove Unused Fields',
                'description' => 'Remove ' . count($unusedFields) . ' unused fields to improve report clarity',
                'implementation_effort' => 'low',
                'expected_impact' => 'medium'
            ];
        }

        // Suggest data aggregation improvements
        if ($this->hasDetailedDataWithoutAggregation($report)) {
            $suggestions[] = [
                'type' => 'analysis',
                'priority' => 'medium',
                'title' => 'Add Data Aggregation',
                'description' => 'Consider grouping data by time periods or categories for better insights',
                'implementation_effort' => 'medium',
                'expected_impact' => 'high'
            ];
        }

        // AI-powered suggestions using available AI connectors
        $aiSuggestions = $this->getAISuggestions($report);
        $suggestions = array_merge($suggestions, $aiSuggestions);

        return $suggestions;
    }

    /**
     * Recommend optimal chart types based on data structure
     */
    private function recommendChartTypes($report) {
        $recommendations = [];
        $dataFields = $this->getReportDataFields($report);

        foreach ($dataFields as $field) {
            $recommendation = $this->analyzeFieldForChartType($field);
            if ($recommendation) {
                $recommendations[] = $recommendation;
            }
        }

        return $recommendations;
    }

    /**
     * Analyze individual field and suggest appropriate chart type
     */
    private function analyzeFieldForChartType($field) {
        $fieldType = $field['data_type'];
        $uniqueValues = $field['unique_count'];
        $totalValues = $field['total_count'];

        switch ($fieldType) {
            case 'date':
            case 'datetime':
                return [
                    'field_name' => $field['field_name'],
                    'recommended_chart' => 'line_chart',
                    'reason' => 'Time-series data works best with line charts',
                    'alternative_charts' => ['area_chart', 'bar_chart'],
                    'confidence' => 0.9
                ];

            case 'numeric':
                if ($uniqueValues < 10) {
                    return [
                        'field_name' => $field['field_name'],
                        'recommended_chart' => 'bar_chart',
                        'reason' => 'Categorical numeric data displays well in bar charts',
                        'alternative_charts' => ['pie_chart', 'donut_chart'],
                        'confidence' => 0.8
                    ];
                } else {
                    return [
                        'field_name' => $field['field_name'],
                        'recommended_chart' => 'histogram',
                        'reason' => 'Continuous numeric data is best represented as histograms',
                        'alternative_charts' => ['box_plot', 'scatter_plot'],
                        'confidence' => 0.85
                    ];
                }

            case 'string':
                if ($uniqueValues <= 5) {
                    return [
                        'field_name' => $field['field_name'],
                        'recommended_chart' => 'pie_chart',
                        'reason' => 'Low-cardinality categorical data works well in pie charts',
                        'alternative_charts' => ['bar_chart', 'donut_chart'],
                        'confidence' => 0.8
                    ];
                } elseif ($uniqueValues <= 20) {
                    return [
                        'field_name' => $field['field_name'],
                        'recommended_chart' => 'bar_chart',
                        'reason' => 'Medium-cardinality data displays effectively in bar charts',
                        'alternative_charts' => ['column_chart', 'treemap'],
                        'confidence' => 0.75
                    ];
                }
                break;
        }

        return null;
    }

    /**
     * Suggest layout and design improvements
     */
    private function suggestLayoutImprovements($report) {
        $suggestions = [];

        // Check for mobile responsiveness
        if (!$this->isMobileResponsive($report)) {
            $suggestions[] = [
                'type' => 'usability',
                'title' => 'Improve Mobile Responsiveness',
                'description' => 'Add responsive design elements for better mobile viewing',
                'implementation_effort' => 'medium',
                'impact' => 'high'
            ];
        }

        // Suggest color scheme improvements
        if ($this->hasPoorColorContrast($report)) {
            $suggestions[] = [
                'type' => 'accessibility',
                'title' => 'Improve Color Contrast',
                'description' => 'Enhance color contrast for better readability',
                'implementation_effort' => 'low',
                'impact' => 'medium'
            ];
        }

        // Suggest information hierarchy
        if (!$this->hasClearHierarchy($report)) {
            $suggestions[] = [
                'type' => 'design',
                'title' => 'Establish Clear Information Hierarchy',
                'description' => 'Use typography and spacing to create better content organization',
                'implementation_effort' => 'medium',
                'impact' => 'high'
            ];
        }

        return $suggestions;
    }

    /**
     * Identify performance optimization opportunities
     */
    private function identifyPerformanceIssues($report) {
        $issues = [];

        // Check query execution time
        if ($report['avg_execution_time'] > 5) {
            $issues[] = [
                'type' => 'query_optimization',
                'title' => 'Slow Query Performance',
                'description' => 'Average execution time is ' . $report['avg_execution_time'] . ' seconds. Consider query optimization.',
                'suggestion' => 'Add database indexes or rewrite complex queries',
                'severity' => 'high'
            ];
        }

        // Check data refresh frequency
        if ($report['data_volume'] > 50000 && $report['refresh_frequency'] < 3600) {
            $issues[] = [
                'type' => 'caching',
                'title' => 'Frequent Data Refresh',
                'description' => 'Large dataset with frequent refreshes may impact performance',
                'suggestion' => 'Implement caching or increase refresh intervals',
                'severity' => 'medium'
            ];
        }

        // Check memory usage
        if ($this->hasHighMemoryUsage($report)) {
            $issues[] = [
                'type' => 'memory_optimization',
                'title' => 'High Memory Consumption',
                'description' => 'Report consumes excessive memory during generation',
                'suggestion' => 'Implement streaming or chunked data processing',
                'severity' => 'high'
            ];
        }

        return $issues;
    }

    /**
     * Suggest accessibility enhancements
     */
    private function suggestAccessibilityEnhancements($report) {
        $suggestions = [];

        // Check for alt text on images
        if (!$this->hasAltText($report)) {
            $suggestions[] = [
                'type' => 'accessibility',
                'title' => 'Add Alt Text to Images',
                'description' => 'Ensure all images have descriptive alt text for screen readers',
                'wcag_guideline' => '1.1.1 Non-text Content',
                'implementation_effort' => 'low',
                'impact' => 'high'
            ];
        }

        // Check color blindness compatibility
        if (!$this->isColorBlindFriendly($report)) {
            $suggestions[] = [
                'type' => 'accessibility',
                'title' => 'Improve Color Blind Accessibility',
                'description' => 'Use color-blind friendly color schemes and patterns',
                'wcag_guideline' => '1.4.1 Use of Color',
                'implementation_effort' => 'medium',
                'impact' => 'medium'
            ];
        }

        // Check keyboard navigation
        if (!$this->supportsKeyboardNavigation($report)) {
            $suggestions[] = [
                'type' => 'accessibility',
                'title' => 'Add Keyboard Navigation',
                'description' => 'Ensure all interactive elements are keyboard accessible',
                'wcag_guideline' => '2.1.1 Keyboard',
                'implementation_effort' => 'medium',
                'impact' => 'high'
            ];
        }

        return $suggestions;
    }

    /**
     * Get AI-powered suggestions using available AI connectors
     */
    private function getAISuggestions($report) {
        $suggestions = [];

        try {
            // Use AI to analyze report content and suggest improvements
            $aiPrompt = $this->buildAIPrompt($report);
            $aiResponse = $this->aiConnector->generateSuggestions($aiPrompt);

            if ($aiResponse && isset($aiResponse['suggestions'])) {
                foreach ($aiResponse['suggestions'] as $suggestion) {
                    $suggestions[] = [
                        'type' => 'ai_generated',
                        'priority' => $suggestion['priority'] ?? 'medium',
                        'title' => $suggestion['title'] ?? 'AI Suggestion',
                        'description' => $suggestion['description'] ?? '',
                        'implementation_effort' => $suggestion['effort'] ?? 'medium',
                        'expected_impact' => $suggestion['impact'] ?? 'medium',
                        'ai_confidence' => $suggestion['confidence'] ?? 0.7
                    ];
                }
            }
        } catch (Exception $e) {
            // Log AI error but don't fail the entire analysis
            error_log("AI suggestion generation failed: " . $e->getMessage());
        }

        return $suggestions;
    }

    /**
     * Build AI prompt for report analysis
     */
    private function buildAIPrompt($report) {
        return [
            'task' => 'analyze_report_for_improvements',
            'report_data' => [
                'title' => $report['report_name'],
                'type' => $report['report_type'],
                'data_volume' => $report['data_volume'],
                'field_count' => count($this->getReportDataFields($report)),
                'current_charts' => $report['chart_types'] ?? [],
                'usage_metrics' => $this->getReportMetrics($report['id'])
            ],
            'context' => 'business_intelligence_report_optimization'
        ];
    }

    /**
     * Helper methods for analysis
     */
    private function getReportDetails($reportId) {
        return $this->db->querySingle("
            SELECT r.*, COUNT(rf.id) as field_count, AVG(re.execution_time) as avg_execution_time
            FROM reports r
            LEFT JOIN report_fields rf ON r.id = rf.report_id
            LEFT JOIN report_executions re ON r.id = re.report_id
            WHERE r.id = ? AND r.company_id = ?
            GROUP BY r.id
        ", [$reportId, $this->user['company_id']]);
    }

    private function getReportMetrics($reportId) {
        return $this->db->querySingle("
            SELECT
                COUNT(re.id) as execution_count,
                AVG(re.execution_time) as avg_execution_time,
                MAX(re.execution_time) as max_execution_time,
                MIN(re.execution_time) as min_execution_time,
                SUM(rv.view_count) as total_views,
                AVG(rv.session_duration) as avg_session_duration
            FROM reports r
            LEFT JOIN report_executions re ON r.id = re.report_id
            LEFT JOIN report_views rv ON r.id = rv.report_id
            WHERE r.id = ?
        ", [$reportId]);
    }

    private function getReportDataFields($report) {
        return $this->db->query("
            SELECT
                rf.field_name,
                rf.data_type,
                COUNT(DISTINCT rd.value) as unique_count,
                COUNT(rd.id) as total_count
            FROM report_fields rf
            LEFT JOIN report_data rd ON rf.id = rd.field_id
            WHERE rf.report_id = ?
            GROUP BY rf.id, rf.field_name, rf.data_type
        ", [$report['id']]);
    }

    private function identifyUnusedFields($report) {
        return $this->db->query("
            SELECT rf.field_name
            FROM report_fields rf
            LEFT JOIN report_data rd ON rf.id = rd.field_id
            WHERE rf.report_id = ? AND rd.id IS NULL
        ", [$report['id']]);
    }

    private function hasDetailedDataWithoutAggregation($report) {
        $dataVolume = $this->db->querySingle("
            SELECT COUNT(*) as count FROM report_data WHERE report_id = ?
        ", [$report['id']]);

        return $dataVolume['count'] > 1000; // Arbitrary threshold
    }

    private function isMobileResponsive($report) {
        // Check if report has responsive design settings
        return $report['is_responsive'] ?? false;
    }

    private function hasPoorColorContrast($report) {
        // Check color contrast ratios
        return false; // Placeholder - would need actual color analysis
    }

    private function hasClearHierarchy($report) {
        // Check if report has proper heading structure
        return true; // Placeholder - would need content analysis
    }

    private function hasHighMemoryUsage($report) {
        return ($report['avg_execution_time'] ?? 0) > 10;
    }

    private function hasAltText($report) {
        // Check if images have alt text
        return true; // Placeholder - would need image analysis
    }

    private function isColorBlindFriendly($report) {
        // Check color scheme for color blindness compatibility
        return true; // Placeholder - would need color analysis
    }

    private function supportsKeyboardNavigation($report) {
        // Check for keyboard navigation support
        return true; // Placeholder - would need UI analysis
    }

    private function getCurrentUser() {
        // Get current user from session
        return $_SESSION['user'] ?? null;
    }

    private function storeAnalysisResults($analysis) {
        $this->db->query("
            INSERT INTO report_optimizations
            (report_id, company_id, analysis_data, created_by, created_at)
            VALUES (?, ?, ?, ?, ?)
        ", [
            $analysis['report_id'],
            $this->user['company_id'],
            json_encode($analysis),
            $this->user['id'],
            $analysis['generated_at']
        ]);
    }
}
