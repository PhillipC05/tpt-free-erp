<?php

namespace TPT\ERP\Core;

/**
 * Background Job Processing System
 *
 * Handles asynchronous job processing with queue management,
 * retry logic, and monitoring capabilities.
 */
class JobQueue
{
    private Database $db;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = [
            'max_retries' => (int) (getenv('JOB_MAX_RETRIES') ?: 3),
            'retry_delay' => (int) (getenv('JOB_RETRY_DELAY') ?: 60), // seconds
            'timeout' => (int) (getenv('JOB_TIMEOUT') ?: 300), // 5 minutes
            'max_concurrent' => (int) (getenv('JOB_MAX_CONCURRENT') ?: 5),
        ];
    }

    /**
     * Add job to queue
     */
    public function dispatch(string $jobClass, array $data = [], string $queue = 'default', int $delay = 0): int
    {
        $jobId = $this->db->insert('job_queue', [
            'job_class' => $jobClass,
            'data' => json_encode($data),
            'queue' => $queue,
            'status' => $delay > 0 ? 'delayed' : 'pending',
            'priority' => $data['priority'] ?? 1,
            'attempts' => 0,
            'max_attempts' => $this->config['max_retries'],
            'available_at' => date('Y-m-d H:i:s', time() + $delay),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $jobId;
    }

    /**
     * Add job to queue with delay
     */
    public function dispatchDelayed(string $jobClass, array $data = [], int $delay, string $queue = 'default'): int
    {
        return $this->dispatch($jobClass, $data, $queue, $delay);
    }

    /**
     * Process jobs in queue
     */
    public function processQueue(string $queue = 'default', int $limit = 10): int
    {
        // Get pending jobs
        $jobs = $this->db->query(
            "SELECT * FROM job_queue
             WHERE queue = ? AND status IN ('pending', 'delayed')
             AND available_at <= ? AND attempts < max_attempts
             ORDER BY priority DESC, created_at ASC
             LIMIT ?",
            [$queue, date('Y-m-d H:i:s'), $limit]
        );

        $processed = 0;

        foreach ($jobs as $job) {
            if ($this->processJob($job)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Process single job
     */
    private function processJob(array $job): bool
    {
        // Mark job as processing
        $this->db->update('job_queue', [
            'status' => 'processing',
            'started_at' => date('Y-m-d H:i:s')
        ], ['id' => $job['id']]);

        try {
            // Execute job
            $result = $this->executeJob($job);

            // Mark as completed
            $this->db->update('job_queue', [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'result' => json_encode($result)
            ], ['id' => $job['id']]);

            return true;

        } catch (\Exception $e) {
            // Handle job failure
            $attempts = $job['attempts'] + 1;

            if ($attempts >= $job['max_attempts']) {
                // Mark as failed
                $this->db->update('job_queue', [
                    'status' => 'failed',
                    'failed_at' => date('Y-m-d H:i:s'),
                    'error_message' => $e->getMessage(),
                    'attempts' => $attempts
                ], ['id' => $job['id']]);
            } else {
                // Retry job
                $this->db->update('job_queue', [
                    'status' => 'pending',
                    'attempts' => $attempts,
                    'available_at' => date('Y-m-d H:i:s', time() + $this->config['retry_delay']),
                    'last_error' => $e->getMessage()
                ], ['id' => $job['id']]);
            }

            return false;
        }
    }

    /**
     * Execute job
     */
    private function executeJob(array $job)
    {
        if (!class_exists($job['job_class'])) {
            throw new \Exception("Job class {$job['job_class']} not found");
        }

        $jobInstance = new $job['job_class']();
        $data = json_decode($job['data'], true) ?: [];

        if (!method_exists($jobInstance, 'handle')) {
            throw new \Exception("Job class {$job['job_class']} must have handle method");
        }

        // Set timeout
        set_time_limit($this->config['timeout']);

        return $jobInstance->handle($data);
    }

    /**
     * Get job status
     */
    public function getJobStatus(int $jobId): ?array
    {
        $job = $this->db->find('job_queue', $jobId);

        if (!$job) {
            return null;
        }

        return [
            'id' => $job['id'],
            'status' => $job['status'],
            'progress' => $job['progress'] ?? 0,
            'attempts' => $job['attempts'],
            'max_attempts' => $job['max_attempts'],
            'created_at' => $job['created_at'],
            'started_at' => $job['started_at'],
            'completed_at' => $job['completed_at'],
            'failed_at' => $job['failed_at'],
            'error_message' => $job['error_message']
        ];
    }

    /**
     * Cancel job
     */
    public function cancelJob(int $jobId): bool
    {
        return $this->db->update('job_queue', [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ], ['id' => $jobId]) > 0;
    }

    /**
     * Retry failed job
     */
    public function retryJob(int $jobId): bool
    {
        $job = $this->db->find('job_queue', $jobId);

        if (!$job || $job['status'] !== 'failed') {
            return false;
        }

        return $this->db->update('job_queue', [
            'status' => 'pending',
            'available_at' => date('Y-m-d H:i:s')
        ], ['id' => $jobId]) > 0;
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(string $queue = null): array
    {
        $conditions = $queue ? ['queue' => $queue] : [];

        $stats = [
            'total' => $this->db->count('job_queue', $conditions),
            'pending' => $this->db->count('job_queue', array_merge($conditions, ['status' => 'pending'])),
            'processing' => $this->db->count('job_queue', array_merge($conditions, ['status' => 'processing'])),
            'completed' => $this->db->count('job_queue', array_merge($conditions, ['status' => 'completed'])),
            'failed' => $this->db->count('job_queue', array_merge($conditions, ['status' => 'failed'])),
            'cancelled' => $this->db->count('job_queue', array_merge($conditions, ['status' => 'cancelled'])),
        ];

        // Get average processing time
        $completedJobs = $this->db->query(
            "SELECT AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_time
             FROM job_queue
             WHERE status = 'completed' AND started_at IS NOT NULL AND completed_at IS NOT NULL
             " . ($queue ? "AND queue = ?" : ""),
            $queue ? [$queue] : []
        );

        $stats['avg_processing_time'] = $completedJobs[0]['avg_time'] ?? 0;

        return $stats;
    }

    /**
     * Clean old completed jobs
     */
    public function cleanOldJobs(int $daysOld = 30): int
    {
        return $this->db->execute(
            "DELETE FROM job_queue
             WHERE status IN ('completed', 'cancelled')
             AND created_at < ?",
            [date('Y-m-d H:i:s', strtotime("-{$daysOld} days"))]
        );
    }

    /**
     * Get failed jobs for retry
     */
    public function getFailedJobs(string $queue = null, int $limit = 50): array
    {
        $conditions = ['status' => 'failed'];
        if ($queue) {
            $conditions['queue'] = $queue;
        }

        return $this->db->findBy(
            'job_queue',
            $conditions,
            ['created_at' => 'DESC'],
            $limit
        );
    }

    /**
     * Bulk retry failed jobs
     */
    public function retryFailedJobs(string $queue = null): int
    {
        $conditions = ['status' => 'failed'];
        if ($queue) {
            $conditions['queue'] = $queue;
        }

        return $this->db->execute(
            "UPDATE job_queue SET status = 'pending', available_at = ? WHERE status = 'failed'"
            . ($queue ? " AND queue = ?" : ""),
            array_merge([date('Y-m-d H:i:s')], $queue ? [$queue] : [])
        );
    }
}

/**
 * Base Job Class
 */
abstract class BaseJob
{
    protected array $data;

    /**
     * Execute the job
     */
    abstract public function handle(array $data): mixed;

    /**
     * Get job priority (1-10, higher = more priority)
     */
    public function getPriority(): int
    {
        return 1;
    }

    /**
     * Get maximum retry attempts
     */
    public function getMaxAttempts(): int
    {
        return 3;
    }

    /**
     * Get retry delay in seconds
     */
    public function getRetryDelay(): int
    {
        return 60;
    }

    /**
     * Called when job fails
     */
    public function failed(\Exception $exception): void
    {
        // Override in child classes for custom failure handling
        error_log("Job failed: " . $exception->getMessage());
    }
}

/**
 * Example: Email Job
 */
class SendEmailJob extends BaseJob
{
    public function handle(array $data): bool
    {
        $email = new Email();
        return $email->send(
            $data['to'],
            $data['subject'],
            $data['body'],
            $data['attachments'] ?? [],
            $data['options'] ?? []
        );
    }
}

/**
 * Example: Data Export Job
 */
class DataExportJob extends BaseJob
{
    public function handle(array $data): string
    {
        $table = $data['table'];
        $format = $data['format'] ?? 'csv';
        $filters = $data['filters'] ?? [];

        $db = Database::getInstance();
        $data = $db->findBy($table, $filters);

        return $this->exportData($data, $format);
    }

    private function exportData(array $data, string $format): string
    {
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($data);
            case 'json':
                return json_encode($data);
            case 'xml':
                return $this->exportToXml($data);
            default:
                throw new \Exception("Unsupported export format: {$format}");
        }
    }

    private function exportToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data[0]));

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function exportToXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<data/>');

        foreach ($data as $row) {
            $item = $xml->addChild('item');
            foreach ($row as $key => $value) {
                $item->addChild($key, htmlspecialchars($value));
            }
        }

        return $xml->asXML();
    }
}
