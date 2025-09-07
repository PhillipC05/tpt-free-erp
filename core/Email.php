<?php

namespace TPT\ERP\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Service
 *
 * Handles email sending with templates, queuing, and delivery tracking.
 */
class Email
{
    private PHPMailer $mailer;
    private array $config;
    private string $templatePath;

    public function __construct()
    {
        $this->config = [
            'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
            'port' => (int) (getenv('MAIL_PORT') ?: 587),
            'username' => getenv('MAIL_USERNAME') ?: '',
            'password' => getenv('MAIL_PASSWORD') ?: '',
            'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
            'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@tpt-erp.com',
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'TPT ERP',
        ];

        $this->templatePath = __DIR__ . '/../templates/emails';
        $this->initializeMailer();
    }

    /**
     * Initialize PHPMailer
     */
    private function initializeMailer(): void
    {
        $this->mailer = new PHPMailer(true);

        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->config['username'];
        $this->mailer->Password = $this->config['password'];
        $this->mailer->SMTPSecure = $this->config['encryption'];
        $this->mailer->Port = $this->config['port'];

        // Default sender
        $this->mailer->setFrom($this->config['from_address'], $this->config['from_name']);

        // Enable SMTP debugging if in debug mode
        if (getenv('APP_DEBUG')) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        }
    }

    /**
     * Send email
     */
    public function send(
        string|array $to,
        string $subject,
        string $body,
        array $attachments = [],
        array $options = []
    ): bool {
        try {
            $this->resetMailer();

            // Recipients
            $this->setRecipients($to);

            // Subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            // HTML content
            if (!empty($options['html'])) {
                $this->mailer->isHTML(true);
                $this->mailer->AltBody = strip_tags($body);
            }

            // Attachments
            foreach ($attachments as $attachment) {
                if (is_string($attachment)) {
                    $this->mailer->addAttachment($attachment);
                } elseif (is_array($attachment) && isset($attachment['path'])) {
                    $name = $attachment['name'] ?? basename($attachment['path']);
                    $this->mailer->addAttachment($attachment['path'], $name);
                }
            }

            // Additional options
            if (!empty($options['cc'])) {
                $this->setRecipients($options['cc'], 'cc');
            }

            if (!empty($options['bcc'])) {
                $this->setRecipients($options['bcc'], 'bcc');
            }

            if (!empty($options['reply_to'])) {
                $this->mailer->addReplyTo($options['reply_to']);
            }

            // Send email
            $result = $this->mailer->send();

            // Log email
            $this->logEmail($to, $subject, $result);

            return $result;

        } catch (Exception $e) {
            // Log error
            error_log('Email sending failed: ' . $e->getMessage());
            $this->logEmail($to, $subject, false, $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using template
     */
    public function sendTemplate(
        string|array $to,
        string $template,
        array $data = [],
        array $options = []
    ): bool {
        $templateContent = $this->loadTemplate($template, $data);

        if (!$templateContent) {
            return false;
        }

        $subject = $data['subject'] ?? 'TPT ERP Notification';
        $options['html'] = true;

        return $this->send($to, $subject, $templateContent, $options['attachments'] ?? [], $options);
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(string $email, array $userData): bool
    {
        return $this->sendTemplate($email, 'welcome', [
            'subject' => 'Welcome to TPT ERP',
            'name' => $userData['first_name'] . ' ' . $userData['last_name'],
            'email' => $userData['email'],
            'login_url' => getenv('APP_URL') . '/login'
        ]);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(string $email, string $resetToken): bool
    {
        return $this->sendTemplate($email, 'password_reset', [
            'subject' => 'Password Reset Request',
            'reset_url' => getenv('APP_URL') . '/reset-password?token=' . $resetToken,
            'token' => $resetToken
        ]);
    }

    /**
     * Send email verification
     */
    public function sendEmailVerification(string $email, string $verificationToken): bool
    {
        return $this->sendTemplate($email, 'email_verification', [
            'subject' => 'Verify Your Email Address',
            'verification_url' => getenv('APP_URL') . '/verify-email?token=' . $verificationToken,
            'token' => $verificationToken
        ]);
    }

    /**
     * Send notification email
     */
    public function sendNotification(
        string|array $to,
        string $title,
        string $message,
        array $data = []
    ): bool {
        return $this->sendTemplate($to, 'notification', array_merge($data, [
            'subject' => $title,
            'title' => $title,
            'message' => $message
        ]));
    }

    /**
     * Queue email for later sending
     */
    public function queue(
        string|array $to,
        string $subject,
        string $body,
        array $options = []
    ): bool {
        $emailData = [
            'to' => is_array($to) ? $to : [$to],
            'subject' => $subject,
            'body' => $body,
            'options' => $options,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'queued'
        ];

        // Store in database for queue processing
        $db = Database::getInstance();
        return $db->insert('email_queue', $emailData) > 0;
    }

    /**
     * Process email queue
     */
    public function processQueue(int $limit = 50): int
    {
        $db = Database::getInstance();

        // Get queued emails
        $queuedEmails = $db->query(
            "SELECT * FROM email_queue WHERE status = 'queued' ORDER BY created_at ASC LIMIT ?",
            [$limit]
        );

        $processed = 0;

        foreach ($queuedEmails as $email) {
            $result = $this->send(
                $email['to'],
                $email['subject'],
                $email['body'],
                $email['options']['attachments'] ?? [],
                $email['options']
            );

            // Update status
            $db->update('email_queue', [
                'status' => $result ? 'sent' : 'failed',
                'sent_at' => $result ? date('Y-m-d H:i:s') : null,
                'error_message' => $result ? null : 'Failed to send'
            ], ['id' => $email['id']]);

            $processed++;
        }

        return $processed;
    }

    /**
     * Load email template
     */
    private function loadTemplate(string $template, array $data = []): ?string
    {
        $templateFile = $this->templatePath . '/' . $template . '.html';

        if (!file_exists($templateFile)) {
            return null;
        }

        $content = file_get_contents($templateFile);

        // Replace variables
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Set recipients
     */
    private function setRecipients(string|array $recipients, string $type = 'to'): void
    {
        $recipients = is_array($recipients) ? $recipients : [$recipients];

        foreach ($recipients as $email => $name) {
            if (is_numeric($email)) {
                $email = $name;
                $name = '';
            }

            switch ($type) {
                case 'to':
                    $this->mailer->addAddress($email, $name);
                    break;
                case 'cc':
                    $this->mailer->addCC($email, $name);
                    break;
                case 'bcc':
                    $this->mailer->addBCC($email, $name);
                    break;
            }
        }
    }

    /**
     * Reset mailer for reuse
     */
    private function resetMailer(): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
        $this->mailer->clearAttachments();
        $this->mailer->clearCustomHeaders();
        $this->mailer->isHTML(false);
    }

    /**
     * Log email sending
     */
    private function logEmail(string|array $to, string $subject, bool $success, string $error = null): void
    {
        $db = Database::getInstance();

        $db->insert('email_logs', [
            'recipient' => is_array($to) ? implode(', ', $to) : $to,
            'subject' => $subject,
            'status' => $success ? 'sent' : 'failed',
            'error_message' => $error,
            'sent_at' => $success ? date('Y-m-d H:i:s') : null
        ]);
    }

    /**
     * Get email statistics
     */
    public function getStats(): array
    {
        $db = Database::getInstance();

        $stats = $db->queryOne("
            SELECT
                COUNT(*) as total_sent,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as total_failed,
                COUNT(CASE WHEN DATE(sent_at) = CURDATE() THEN 1 END) as sent_today
            FROM email_logs
            WHERE sent_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");

        return $stats ?: ['total_sent' => 0, 'total_failed' => 0, 'sent_today' => 0];
    }
}
