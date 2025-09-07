<?php
/**
 * TPT Free ERP - AI Connectors Module
 * Integration with AI services (OpenAI, Anthropic, Gemini, OpenRouter)
 */

class AIConnectors extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main AI connectors dashboard
     */
    public function index() {
        $this->requirePermission('ai.view');

        $data = [
            'title' => 'AI Connectors Dashboard',
            'active_connectors' => $this->getActiveConnectors(),
            'usage_stats' => $this->getUsageStats(),
            'recent_interactions' => $this->getRecentInteractions(),
            'available_providers' => $this->getAvailableProviders(),
            'api_keys_status' => $this->getAPIKeysStatus()
        ];

        $this->render('modules/ai/dashboard', $data);
    }

    /**
     * OpenAI integration
     */
    public function openai() {
        $this->requirePermission('ai.openai.view');

        $data = [
            'title' => 'OpenAI Integration',
            'models' => $this->getOpenAIModels(),
            'usage' => $this->getOpenAIUsage(),
            'conversations' => $this->getOpenAIConversations(),
            'settings' => $this->getOpenAISettings()
        ];

        $this->render('modules/ai/openai', $data);
    }

    /**
     * Anthropic integration
     */
    public function anthropic() {
        $this->requirePermission('ai.anthropic.view');

        $data = [
            'title' => 'Anthropic Integration',
            'models' => $this->getAnthropicModels(),
            'usage' => $this->getAnthropicUsage(),
            'conversations' => $this->getAnthropicConversations(),
            'settings' => $this->getAnthropicSettings()
        ];

        $this->render('modules/ai/anthropic', $data);
    }

    /**
     * Google Gemini integration
     */
    public function gemini() {
        $this->requirePermission('ai.gemini.view');

        $data = [
            'title' => 'Google Gemini Integration',
            'models' => $this->getGeminiModels(),
            'usage' => $this->getGeminiUsage(),
            'conversations' => $this->getGeminiConversations(),
            'settings' => $this->getGeminiSettings()
        ];

        $this->render('modules/ai/gemini', $data);
    }

    /**
     * OpenRouter integration
     */
    public function openrouter() {
        $this->requirePermission('ai.openrouter.view');

        $data = [
            'title' => 'OpenRouter Integration',
            'models' => $this->getOpenRouterModels(),
            'usage' => $this->getOpenRouterUsage(),
            'conversations' => $this->getOpenRouterConversations(),
            'settings' => $this->getOpenRouterSettings()
        ];

        $this->render('modules/ai/openrouter', $data);
    }

    /**
     * API key management
     */
    public function apiKeys() {
        $this->requirePermission('ai.keys.view');

        $data = [
            'title' => 'API Key Management',
            'api_keys' => $this->getAPIKeys(),
            'providers' => $this->getAvailableProviders(),
            'usage_limits' => $this->getUsageLimits(),
            'security_audit' => $this->getSecurityAudit()
        ];

        $this->render('modules/ai/api_keys', $data);
    }

    /**
     * Usage tracking and analytics
     */
    public function usage() {
        $this->requirePermission('ai.usage.view');

        $data = [
            'title' => 'AI Usage Analytics',
            'usage_summary' => $this->getUsageSummary(),
            'cost_analysis' => $this->getCostAnalysis(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'user_activity' => $this->getUserActivity()
        ];

        $this->render('modules/ai/usage', $data);
    }

    /**
     * AI-powered features
     */
    public function features() {
        $this->requirePermission('ai.features.view');

        $data = [
            'title' => 'AI-Powered Features',
            'text_generation' => $this->getTextGenerationFeatures(),
            'data_analysis' => $this->getDataAnalysisFeatures(),
            'automation' => $this->getAutomationFeatures(),
            'insights' => $this->getAIInsights()
        ];

        $this->render('modules/ai/features', $data);
    }

    /**
     * AI chat interface
     */
    public function chat() {
        $this->requirePermission('ai.chat.view');

        $data = [
            'title' => 'AI Chat',
            'conversations' => $this->getUserConversations(),
            'available_models' => $this->getAvailableModels(),
            'chat_settings' => $this->getChatSettings()
        ];

        $this->render('modules/ai/chat', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getActiveConnectors() {
        return $this->db->query("
            SELECT
                ac.*,
                COUNT(ai.id) as total_interactions,
                MAX(ai.created_at) as last_used
            FROM ai_connectors ac
            LEFT JOIN ai_interactions ai ON ac.id = ai.connector_id
            WHERE ac.company_id = ? AND ac.is_active = true
            GROUP BY ac.id
            ORDER BY ac.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getUsageStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_interactions,
                SUM(tokens_used) as total_tokens,
                SUM(cost_usd) as total_cost,
                COUNT(DISTINCT user_id) as active_users,
                COUNT(DISTINCT connector_id) as active_connectors,
                AVG(response_time_ms) as avg_response_time
            FROM ai_interactions
            WHERE company_id = ? AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getRecentInteractions() {
        return $this->db->query("
            SELECT
                ai.*,
                ac.provider_name,
                ac.model_name,
                u.first_name,
                u.last_name
            FROM ai_interactions ai
            JOIN ai_connectors ac ON ai.connector_id = ac.id
            LEFT JOIN users u ON ai.user_id = u.id
            WHERE ai.company_id = ?
            ORDER BY ai.created_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getAvailableProviders() {
        return [
            'openai' => [
                'name' => 'OpenAI',
                'models' => ['gpt-4', 'gpt-3.5-turbo', 'dall-e-3'],
                'features' => ['text', 'image', 'embedding']
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'models' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'],
                'features' => ['text', 'analysis']
            ],
            'gemini' => [
                'name' => 'Google Gemini',
                'models' => ['gemini-pro', 'gemini-pro-vision'],
                'features' => ['text', 'vision', 'multimodal']
            ],
            'openrouter' => [
                'name' => 'OpenRouter',
                'models' => ['auto', 'anthropic/claude-3', 'openai/gpt-4'],
                'features' => ['routing', 'fallback']
            ]
        ];
    }

    private function getAPIKeysStatus() {
        return $this->db->query("
            SELECT
                provider,
                COUNT(*) as total_keys,
                COUNT(CASE WHEN is_active = true THEN 1 END) as active_keys,
                COUNT(CASE WHEN last_used_at >= ? THEN 1 END) as recently_used,
                MAX(last_used_at) as last_used
            FROM ai_api_keys
            WHERE company_id = ?
            GROUP BY provider
        ", [
            date('Y-m-d H:i:s', strtotime('-7 days')),
            $this->user['company_id']
        ]);
    }

    private function getOpenAIModels() {
        return [
            'gpt-4' => ['name' => 'GPT-4', 'context' => 8192, 'capabilities' => ['text', 'code', 'analysis']],
            'gpt-4-turbo' => ['name' => 'GPT-4 Turbo', 'context' => 128000, 'capabilities' => ['text', 'code', 'analysis']],
            'gpt-3.5-turbo' => ['name' => 'GPT-3.5 Turbo', 'context' => 16385, 'capabilities' => ['text', 'code']],
            'dall-e-3' => ['name' => 'DALL-E 3', 'context' => 0, 'capabilities' => ['image']]
        ];
    }

    private function getOpenAIUsage() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_requests,
                SUM(tokens_used) as total_tokens,
                SUM(cost_usd) as total_cost,
                AVG(response_time_ms) as avg_response_time
            FROM ai_interactions
            WHERE company_id = ? AND provider = 'openai'
                AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getOpenAIConversations() {
        return $this->db->query("
            SELECT
                ac.*,
                COUNT(ai.id) as message_count,
                MAX(ai.created_at) as last_message
            FROM ai_conversations ac
            LEFT JOIN ai_interactions ai ON ac.id = ai.conversation_id
            WHERE ac.company_id = ? AND ac.provider = 'openai'
            GROUP BY ac.id
            ORDER BY ac.updated_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getOpenAISettings() {
        return $this->db->querySingle("
            SELECT * FROM ai_connector_settings
            WHERE company_id = ? AND provider = 'openai'
        ", [$this->user['company_id']]);
    }

    private function getAnthropicModels() {
        return [
            'claude-3-opus' => ['name' => 'Claude 3 Opus', 'context' => 200000, 'capabilities' => ['text', 'analysis', 'code']],
            'claude-3-sonnet' => ['name' => 'Claude 3 Sonnet', 'context' => 200000, 'capabilities' => ['text', 'analysis', 'code']],
            'claude-3-haiku' => ['name' => 'Claude 3 Haiku', 'context' => 200000, 'capabilities' => ['text', 'analysis']]
        ];
    }

    private function getAnthropicUsage() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_requests,
                SUM(tokens_used) as total_tokens,
                SUM(cost_usd) as total_cost,
                AVG(response_time_ms) as avg_response_time
            FROM ai_interactions
            WHERE company_id = ? AND provider = 'anthropic'
                AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getAnthropicConversations() {
        return $this->db->query("
            SELECT
                ac.*,
                COUNT(ai.id) as message_count,
                MAX(ai.created_at) as last_message
            FROM ai_conversations ac
            LEFT JOIN ai_interactions ai ON ac.id = ai.conversation_id
            WHERE ac.company_id = ? AND ac.provider = 'anthropic'
            GROUP BY ac.id
            ORDER BY ac.updated_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getAnthropicSettings() {
        return $this->db->querySingle("
            SELECT * FROM ai_connector_settings
            WHERE company_id = ? AND provider = 'anthropic'
        ", [$this->user['company_id']]);
    }

    private function getGeminiModels() {
        return [
            'gemini-pro' => ['name' => 'Gemini Pro', 'context' => 32768, 'capabilities' => ['text', 'analysis', 'code']],
            'gemini-pro-vision' => ['name' => 'Gemini Pro Vision', 'context' => 16384, 'capabilities' => ['text', 'vision', 'multimodal']],
            'gemini-ultra' => ['name' => 'Gemini Ultra', 'context' => 32768, 'capabilities' => ['text', 'analysis', 'code', 'vision']]
        ];
    }

    private function getGeminiUsage() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_requests,
                SUM(tokens_used) as total_tokens,
                SUM(cost_usd) as total_cost,
                AVG(response_time_ms) as avg_response_time
            FROM ai_interactions
            WHERE company_id = ? AND provider = 'gemini'
                AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getGeminiConversations() {
        return $this->db->query("
            SELECT
                ac.*,
                COUNT(ai.id) as message_count,
                MAX(ai.created_at) as last_message
            FROM ai_conversations ac
            LEFT JOIN ai_interactions ai ON ac.id = ai.conversation_id
            WHERE ac.company_id = ? AND ac.provider = 'gemini'
            GROUP BY ac.id
            ORDER BY ac.updated_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getGeminiSettings() {
        return $this->db->querySingle("
            SELECT * FROM ai_connector_settings
            WHERE company_id = ? AND provider = 'gemini'
        ", [$this->user['company_id']]);
    }

    private function getOpenRouterModels() {
        return [
            'auto' => ['name' => 'Auto Router', 'context' => 0, 'capabilities' => ['routing', 'fallback']],
            'anthropic/claude-3-opus' => ['name' => 'Claude 3 Opus (via OpenRouter)', 'context' => 200000, 'capabilities' => ['text', 'analysis']],
            'openai/gpt-4' => ['name' => 'GPT-4 (via OpenRouter)', 'context' => 8192, 'capabilities' => ['text', 'code']],
            'meta/llama-2-70b' => ['name' => 'Llama 2 70B', 'context' => 4096, 'capabilities' => ['text', 'analysis']]
        ];
    }

    private function getOpenRouterUsage() {
        return $this->db->querySingle("
            SELECT
                COUNT(*) as total_requests,
                SUM(tokens_used) as total_tokens,
                SUM(cost_usd) as total_cost,
                AVG(response_time_ms) as avg_response_time
            FROM ai_interactions
            WHERE company_id = ? AND provider = 'openrouter'
                AND created_at >= ?
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getOpenRouterConversations() {
        return $this->db->query("
            SELECT
                ac.*,
                COUNT(ai.id) as message_count,
                MAX(ai.created_at) as last_message
            FROM ai_conversations ac
            LEFT JOIN ai_interactions ai ON ac.id = ai.conversation_id
            WHERE ac.company_id = ? AND ac.provider = 'openrouter'
            GROUP BY ac.id
            ORDER BY ac.updated_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getOpenRouterSettings() {
        return $this->db->querySingle("
            SELECT * FROM ai_connector_settings
            WHERE company_id = ? AND provider = 'openrouter'
        ", [$this->user['company_id']]);
    }

    private function getAPIKeys() {
        return $this->db->query("
            SELECT
                aak.*,
                COUNT(ai.id) as usage_count,
                MAX(ai.created_at) as last_used_at,
                SUM(ai.cost_usd) as total_cost
            FROM ai_api_keys aak
            LEFT JOIN ai_interactions ai ON aak.id = ai.api_key_id
            WHERE aak.company_id = ?
            GROUP BY aak.id
            ORDER BY aak.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getUsageLimits() {
        return $this->db->query("
            SELECT
                provider,
                SUM(monthly_limit) as total_limit,
                SUM(current_usage) as current_usage,
                MIN(reset_date) as next_reset
            FROM ai_usage_limits
            WHERE company_id = ?
            GROUP BY provider
        ", [$this->user['company_id']]);
    }

    private function getSecurityAudit() {
        return $this->db->query("
            SELECT
                asa.*,
                u.first_name,
                u.last_name
            FROM ai_security_audit asa
            LEFT JOIN users u ON asa.user_id = u.id
            WHERE asa.company_id = ?
            ORDER BY asa.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getUsageSummary() {
        return $this->db->query("
            SELECT
                DATE_TRUNC('month', created_at) as month,
                provider,
                COUNT(*) as total_requests,
                SUM(tokens_used) as total_tokens,
                SUM(cost_usd) as total_cost,
                AVG(response_time_ms) as avg_response_time
            FROM ai_interactions
            WHERE company_id = ? AND created_at >= ?
            GROUP BY DATE_TRUNC('month', created_at), provider
            ORDER BY month DESC, provider ASC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-12 months'))
        ]);
    }

    private function getCostAnalysis() {
        return $this->db->query("
            SELECT
                provider,
                model_name,
                COUNT(*) as request_count,
                SUM(cost_usd) as total_cost,
                AVG(cost_usd) as avg_cost_per_request,
                SUM(tokens_used) as total_tokens,
                AVG(tokens_used) as avg_tokens_per_request
            FROM ai_interactions
            WHERE company_id = ? AND created_at >= ?
            GROUP BY provider, model_name
            ORDER BY total_cost DESC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getPerformanceMetrics() {
        return $this->db->query("
            SELECT
                provider,
                model_name,
                COUNT(*) as total_requests,
                AVG(response_time_ms) as avg_response_time,
                MIN(response_time_ms) as min_response_time,
                MAX(response_time_ms) as max_response_time,
                COUNT(CASE WHEN response_time_ms > 10000 THEN 1 END) as slow_requests
            FROM ai_interactions
            WHERE company_id = ? AND created_at >= ?
            GROUP BY provider, model_name
            ORDER BY avg_response_time ASC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ]);
    }

    private function getUserActivity() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                COUNT(ai.id) as total_requests,
                SUM(ai.tokens_used) as total_tokens,
                SUM(ai.cost_usd) as total_cost,
                MAX(ai.created_at) as last_activity
            FROM users u
            LEFT JOIN ai_interactions ai ON u.id = ai.user_id
            WHERE u.company_id = ? AND ai.created_at >= ?
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY total_requests DESC
        ", [
            $this->user['company_id'],
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
    }

    private function getTextGenerationFeatures() {
        return [
            'content_creation' => [
                'name' => 'Content Creation',
                'description' => 'Generate marketing content, emails, and documentation',
                'models' => ['gpt-4', 'claude-3', 'gemini-pro']
            ],
            'code_generation' => [
                'name' => 'Code Generation',
                'description' => 'Generate code snippets and automation scripts',
                'models' => ['gpt-4', 'claude-3']
            ],
            'data_analysis' => [
                'name' => 'Data Analysis',
                'description' => 'Analyze business data and generate insights',
                'models' => ['gpt-4', 'claude-3', 'gemini-pro']
            ]
        ];
    }

    private function getDataAnalysisFeatures() {
        return [
            'trend_analysis' => [
                'name' => 'Trend Analysis',
                'description' => 'Identify patterns and trends in business data',
                'capabilities' => ['forecasting', 'anomaly_detection']
            ],
            'predictive_modeling' => [
                'name' => 'Predictive Modeling',
                'description' => 'Predict future business outcomes',
                'capabilities' => ['sales_forecasting', 'demand_prediction']
            ],
            'sentiment_analysis' => [
                'name' => 'Sentiment Analysis',
                'description' => 'Analyze customer feedback and reviews',
                'capabilities' => ['text_analysis', 'emotion_detection']
            ]
        ];
    }

    private function getAutomationFeatures() {
        return [
            'workflow_automation' => [
                'name' => 'Workflow Automation',
                'description' => 'Automate repetitive business processes',
                'capabilities' => ['task_creation', 'email_automation']
            ],
            'intelligent_routing' => [
                'name' => 'Intelligent Routing',
                'description' => 'Route tasks and requests to appropriate personnel',
                'capabilities' => ['priority_scoring', 'skill_matching']
            ],
            'quality_assurance' => [
                'name' => 'Quality Assurance',
                'description' => 'Automated quality checks and validation',
                'capabilities' => ['data_validation', 'error_detection']
            ]
        ];
    }

    private function getAIInsights() {
        return $this->db->query("
            SELECT
                ai.*,
                u.first_name,
                u.last_name
            FROM ai_insights ai
            LEFT JOIN users u ON ai.generated_by = u.id
            WHERE ai.company_id = ?
            ORDER BY ai.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getUserConversations() {
        return $this->db->query("
            SELECT
                ac.*,
                COUNT(ai.id) as message_count,
                MAX(ai.created_at) as last_message
            FROM ai_conversations ac
            LEFT JOIN ai_interactions ai ON ac.id = ai.conversation_id
            WHERE ac.company_id = ? AND ac.user_id = ?
            GROUP BY ac.id
            ORDER BY ac.updated_at DESC
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getAvailableModels() {
        $models = [];

        // Get active connectors and their models
        $connectors = $this->getActiveConnectors();

        foreach ($connectors as $connector) {
            $providerModels = $this->getProviderModels($connector['provider']);
            $models = array_merge($models, $providerModels);
        }

        return $models;
    }

    private function getProviderModels($provider) {
        switch ($provider) {
            case 'openai':
                return $this->getOpenAIModels();
            case 'anthropic':
                return $this->getAnthropicModels();
            case 'gemini':
                return $this->getGeminiModels();
            case 'openrouter':
                return $this->getOpenRouterModels();
            default:
                return [];
        }
    }

    private function getChatSettings() {
        return $this->db->querySingle("
            SELECT * FROM ai_chat_settings
            WHERE company_id = ? AND user_id = ?
        ", [$this->user['company_id'], $this->user['id']]);
    }

    // ============================================================================
    // API ENDPOINTS FOR AI INTERACTIONS
    // ============================================================================

    public function generateText() {
        $this->requirePermission('ai.generate');

        $data = $this->validateRequest([
            'prompt' => 'required|string',
            'model' => 'required|string',
            'provider' => 'required|string',
            'max_tokens' => 'integer|min:1|max:4000',
            'temperature' => 'numeric|min:0|max:2'
        ]);

        try {
            $result = $this->callAIProvider($data['provider'], 'text-generation', [
                'prompt' => $data['prompt'],
                'model' => $data['model'],
                'max_tokens' => $data['max_tokens'] ?? 1000,
                'temperature' => $data['temperature'] ?? 0.7
            ]);

            // Log the interaction
            $this->logInteraction([
                'provider' => $data['provider'],
                'model' => $data['model'],
                'type' => 'text_generation',
                'tokens_used' => $result['tokens_used'],
                'cost_usd' => $result['cost_usd'],
                'response_time_ms' => $result['response_time_ms']
            ]);

            $this->jsonResponse([
                'success' => true,
                'data' => $result['text'],
                'usage' => [
                    'tokens' => $result['tokens_used'],
                    'cost' => $result['cost_usd']
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function analyzeData() {
        $this->requirePermission('ai.analyze');

        $data = $this->validateRequest([
            'data' => 'required|array',
            'analysis_type' => 'required|string',
            'model' => 'required|string',
            'provider' => 'required|string'
        ]);

        try {
            $result = $this->callAIProvider($data['provider'], 'data-analysis', [
                'data' => $data['data'],
                'analysis_type' => $data['analysis_type'],
                'model' => $data['model']
            ]);

            // Log the interaction
            $this->logInteraction([
                'provider' => $data['provider'],
                'model' => $data['model'],
                'type' => 'data_analysis',
                'tokens_used' => $result['tokens_used'],
                'cost_usd' => $result['cost_usd'],
                'response_time_ms' => $result['response_time_ms']
            ]);

            $this->jsonResponse([
                'success' => true,
                'analysis' => $result['analysis'],
                'insights' => $result['insights'],
                'usage' => [
                    'tokens' => $result['tokens_used'],
                    'cost' => $result['cost_usd']
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function chatMessage() {
        $this->requirePermission('ai.chat');

        $data = $this->validateRequest([
            'message' => 'required|string',
            'conversation_id' => 'string',
            'model' => 'required|string',
            'provider' => 'required|string'
        ]);

        try {
            // Get or create conversation
            $conversationId = $data['conversation_id'] ?? $this->createConversation($data['provider'], $data['model']);

            $result = $this->callAIProvider($data['provider'], 'chat', [
                'message' => $data['message'],
                'conversation_id' => $conversationId,
                'model' => $data['model']
            ]);

            // Log the interaction
            $this->logInteraction([
                'provider' => $data['provider'],
                'model' => $data['model'],
                'type' => 'chat',
                'conversation_id' => $conversationId,
                'tokens_used' => $result['tokens_used'],
                'cost_usd' => $result['cost_usd'],
                'response_time_ms' => $result['response_time_ms']
            ]);

            $this->jsonResponse([
                'success' => true,
                'response' => $result['response'],
                'conversation_id' => $conversationId,
                'usage' => [
                    'tokens' => $result['tokens_used'],
                    'cost' => $result['cost_usd']
                ]
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

    private function callAIProvider($provider, $type, $params) {
        $startTime = microtime(true);

        // Get API key for the provider
        $apiKey = $this->getAPIKey($provider);
        if (!$apiKey) {
            throw new Exception("No API key configured for provider: $provider");
        }

        // Check usage limits
        $this->checkUsageLimits($provider);

        $result = null;

        switch ($provider) {
            case 'openai':
                $result = $this->callOpenAI($type, $params, $apiKey);
                break;
            case 'anthropic':
                $result = $this->callAnthropic($type, $params, $apiKey);
                break;
            case 'gemini':
                $result = $this->callGemini($type, $params, $apiKey);
                break;
            case 'openrouter':
                $result = $this->callOpenRouter($type, $params, $apiKey);
                break;
            default:
                throw new Exception("Unsupported AI provider: $provider");
        }

        $endTime = microtime(true);
        $result['response_time_ms'] = round(($endTime - $startTime) * 1000);

        return $result;
    }

    private function callOpenAI($type, $params, $apiKey) {
        $endpoint = $this->getOpenAIEndpoint($type);
        $payload = $this->buildOpenAIPayload($type, $params);

        $response = $this->makeAPIRequest('POST', $endpoint, $payload, [
            'Authorization' => "Bearer $apiKey",
            'Content-Type' => 'application/json'
        ]);

        return $this->parseOpenAIResponse($type, $response);
    }

    private function callAnthropic($type, $params, $apiKey) {
        $endpoint = $this->getAnthropicEndpoint($type);
        $payload = $this->buildAnthropicPayload($type, $params);

        $response = $this->makeAPIRequest('POST', $endpoint, $payload, [
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ]);

        return $this->parseAnthropicResponse($type, $response);
    }

    private function callGemini($type, $params, $apiKey) {
        $endpoint = $this->getGeminiEndpoint($type, $params['model']);
        $payload = $this->buildGeminiPayload($type, $params);

        $response = $this->makeAPIRequest('POST', $endpoint, $payload, [
            'Content-Type' => 'application/json'
        ], $apiKey);

        return $this->parseGeminiResponse($type, $response);
    }

    private function callOpenRouter($type, $params, $apiKey) {
        $endpoint = $this->getOpenRouterEndpoint($type);
        $payload = $this->buildOpenRouterPayload($type, $params);

        $response = $this->makeAPIRequest('POST', $endpoint, $payload, [
            'Authorization' => "Bearer $apiKey",
            'Content-Type' => 'application/json'
        ]);

        return $this->parseOpenRouterResponse($type, $response);
    }

    private function makeAPIRequest($method, $url, $data = null, $headers = [], $apiKey = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $requestHeaders = [];
        foreach ($headers as $key => $value) {
            $requestHeaders[] = "$key: $value";
        }

        if ($apiKey) {
            $requestHeaders[] = "Authorization: Bearer $apiKey";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("API request failed: $error");
        }

        if ($httpCode >= 400) {
            throw new Exception("API request failed with status $httpCode: $response");
        }

        return json_decode($response, true);
    }

    private function getAPIKey($provider) {
        $key = $this->db->querySingle("
            SELECT api_key FROM ai_api_keys
            WHERE company_id = ? AND provider = ? AND is_active = true
            ORDER BY last_used_at DESC
            LIMIT 1
        ", [$this->user['company_id'], $provider]);

        return $key ? $key['api_key'] : null;
    }

    private function checkUsageLimits($provider) {
        // Implementation for checking usage limits
        // This would check against configured limits and throw exception if exceeded
    }

    private function logInteraction($data) {
        $this->db->insert('ai_interactions', [
            'company_id' => $this->user['company_id'],
            'user_id' => $this->user['id'],
            'provider' => $data['provider'],
            'model' => $data['model'],
            'type' => $data['type'],
            'conversation_id' => $data['conversation_id'] ?? null,
            'tokens_used' => $data['tokens_used'],
            'cost_usd' => $data['cost_usd'],
            'response_time_ms' => $data['response_time_ms']
        ]);
    }

    private function createConversation($provider, $model) {
        return $this->db->insert('ai_conversations', [
            'company_id' => $this->user['company_id'],
            'user_id' => $this->user['id'],
            'provider' => $provider,
            'model' => $model,
            'title' => 'New Conversation'
        ]);
    }

    // Placeholder methods for API endpoints - would be fully implemented
    private function getOpenAIEndpoint($type) { return 'https://api.openai.com/v1/chat/completions'; }
    private function buildOpenAIPayload($type, $params) { return []; }
    private function parseOpenAIResponse($type, $response) { return []; }

    private function getAnthropicEndpoint($type) { return 'https://api.anthropic.com/v1/messages'; }
    private function buildAnthropicPayload($type, $params) { return []; }
    private function parseAnthropicResponse($type, $response) { return []; }

    private function getGeminiEndpoint($type, $model) { return "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent"; }
    private function buildGeminiPayload($type, $params) { return []; }
    private function parseGeminiResponse($type, $response) { return []; }

    private function getOpenRouterEndpoint($type) { return 'https://openrouter.ai/api/v1/chat/completions'; }
    private function buildOpenRouterPayload($type, $params) { return []; }
    private function parseOpenRouterResponse($type, $response) { return []; }
}
?>
