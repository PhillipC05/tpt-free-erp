<?php

namespace TPT\ERP\Core;

/**
 * Error Handler and Logger
 *
 * Comprehensive error handling with logging, monitoring, and reporting.
 */
class ErrorHandler
{
    private static array $levels = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];

    private static array $httpStatusCodes = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        408 => 'Request Timeout',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    private static ?Database $db = null;
    private static ?string $logPath = null;

    /**
     * Initialize error handling
     */
    public static function init(): void
    {
        self::$db = Database::getInstance();
        self::$logPath = __DIR__ . '/../logs';

        // Ensure log directory exists
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }

        // Set error reporting level
        $debug = getenv('APP_DEBUG') ?: false;
        error_reporting($debug ? E_ALL : E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);

        // Set error handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        // Set PHP ini settings
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::$logPath . '/php_errors.log');
    }

    /**
     * Handle PHP errors
     */
    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        $errorType = self::$levels[$level] ?? 'Unknown Error';

        $errorData = [
            'type' => 'php_error',
            'level' => $level,
            'level_name' => $errorType,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        self::logError($errorData);

        // Don't execute PHP's internal error handler
        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException(\Throwable $exception): void
    {
        $errorData = [
            'type' => 'exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        self::logError($errorData);

        // Send error response if in HTTP context
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => getenv('APP_DEBUG') ? $exception->getMessage() : 'Something went wrong'
            ]);
        }
    }

    /**
     * Handle shutdown (fatal errors)
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'fatal_error',
                'level' => $error['type'],
                'level_name' => self::$levels[$error['type']] ?? 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ];

            self::logError($errorData);
        }
    }

    /**
     * Log error to database and file
     */
    private static function logError(array $errorData): void
    {
        // Log to file
        self::logToFile($errorData);

        // Log to database if available
        if (self::$db) {
            try {
                self::$db->insert('error_logs', [
                    'type' => $errorData['type'],
                    'level' => $errorData['level'] ?? null,
                    'level_name' => $errorData['level_name'] ?? null,
                    'message' => $errorData['message'],
                    'file' => $errorData['file'] ?? null,
                    'line' => $errorData['line'] ?? null,
                    'trace' => json_encode($errorData['trace'] ?? []),
                    'request_uri' => $errorData['request_uri'],
                    'request_method' => $errorData['request_method'],
                    'user_agent' => $errorData['user_agent'],
                    'remote_ip' => $errorData['remote_ip'],
                    'created_at' => $errorData['timestamp']
                ]);
            } catch (\Exception $e) {
                // If database logging fails, log to file
                error_log('Failed to log error to database: ' . $e->getMessage());
            }
        }

        // Send notification for critical errors
        if (self::isCriticalError($errorData)) {
            self::sendErrorNotification($errorData);
        }
    }

    /**
     * Log error to file
     */
    private static function logToFile(array $errorData): void
    {
        $logFile = self::$logPath . '/errors_' . date('Y-m-d') . '.log';
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\n",
            $errorData['timestamp'],
            $errorData['type'],
            $errorData['message'],
            $errorData['file'] ?? 'unknown',
            $errorData['line'] ?? 0
        );

        if (isset($errorData['trace']) && is_array($errorData['trace'])) {
            $logMessage .= "Stack trace:\n";
            foreach ($errorData['trace'] as $i => $frame) {
                $logMessage .= sprintf(
                    "  #%d %s(%d): %s%s%s()\n",
                    $i,
                    $frame['file'] ?? 'unknown',
                    $frame['line'] ?? 0,
                    $frame['class'] ?? '',
                    $frame['type'] ?? '',
                    $frame['function'] ?? 'unknown'
                );
            }
        }

        $logMessage .= "Request: {$errorData['request_method']} {$errorData['request_uri']}\n";
        $logMessage .= "IP: {$errorData['remote_ip']}\n";
        $logMessage .= "User-Agent: {$errorData['user_agent']}\n";
        $logMessage .= "---\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Check if error is critical
     */
    private static function isCriticalError(array $errorData): bool
    {
        $criticalTypes = ['exception', 'fatal_error'];
        $criticalLevels = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

        return in_array($errorData['type'], $criticalTypes) ||
               (isset($errorData['level']) && in_array($errorData['level'], $criticalLevels));
    }

    /**
     * Send error notification
     */
    private static function sendErrorNotification(array $errorData): void
    {
        try {
            $notification = new Notification();
            $notification->sendSystemNotification(
                'Critical System Error',
                "A critical error occurred: {$errorData['message']} in {$errorData['file']}:{$errorData['line']}",
                [
                    'error_type' => $errorData['type'],
                    'file' => $errorData['file'],
                    'line' => $errorData['line'],
                    'request_uri' => $errorData['request_uri']
                ]
            );
        } catch (\Exception $e) {
            // Don't let notification failure cause more errors
            error_log('Failed to send error notification: ' . $e->getMessage());
        }
    }

    /**
     * Get error logs
     */
    public static function getErrorLogs(
        int $limit = 50,
        int $offset = 0,
        ?string $type = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        if (!self::$db) {
            return [];
        }

        $where = [];
        $params = [];

        if ($type) {
            $where[] = 'type = ?';
            $params[] = $type;
        }

        if ($dateFrom) {
            $where[] = 'created_at >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = 'created_at <= ?';
            $params[] = $dateTo;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM error_logs {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $logs = self::$db->query($sql, $params);

        // Decode trace JSON
        foreach ($logs as &$log) {
            $log['trace'] = json_decode($log['trace'], true) ?? [];
        }

        return $logs;
    }

    /**
     * Get error statistics
     */
    public static function getErrorStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        if (!self::$db) {
            return [];
        }

        $where = [];
        $params = [];

        if ($dateFrom) {
            $where[] = 'created_at >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = 'created_at <= ?';
            $params[] = $dateTo;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stats = self::$db->queryOne("
            SELECT
                COUNT(*) as total_errors,
                COUNT(CASE WHEN type = 'exception' THEN 1 END) as exceptions,
                COUNT(CASE WHEN type = 'fatal_error' THEN 1 END) as fatal_errors,
                COUNT(CASE WHEN type = 'php_error' THEN 1 END) as php_errors,
                COUNT(DISTINCT DATE(created_at)) as days_with_errors
            FROM error_logs {$whereClause}
        ", $params);

        return $stats ?: [
            'total_errors' => 0,
            'exceptions' => 0,
            'fatal_errors' => 0,
            'php_errors' => 0,
            'days_with_errors' => 0
        ];
    }

    /**
     * Clear old error logs
     */
    public static function clearOldLogs(int $daysOld = 90): int
    {
        if (!self::$db) {
            return 0;
        }

        return self::$db->execute(
            "DELETE FROM error_logs WHERE created_at < ?",
            [date('Y-m-d H:i:s', strtotime("-{$daysOld} days"))]
        );
    }

    /**
     * Create custom error response
     */
    public static function createErrorResponse(
        string $message,
        int $statusCode = 500,
        ?array $errors = null,
        ?array $data = null
    ): array {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
            'status_code' => $statusCode
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        if ($data) {
            $response['data'] = $data;
        }

        // Log API errors
        if ($statusCode >= 500) {
            self::logError([
                'type' => 'api_error',
                'message' => $message,
                'status_code' => $statusCode,
                'errors' => $errors,
                'data' => $data,
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        }

        return $response;
    }

    /**
     * Get HTTP status text
     */
    public static function getHttpStatusText(int $code): string
    {
        return self::$httpStatusCodes[$code] ?? 'Unknown Status';
    }
}
