<?php
/**
 * TPT Free ERP - Collaboration Tools Module
 * Complete team messaging, file sharing, and document collaboration
 */

class Collaboration extends BaseController {
    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = new Database();
        $this->user = $this->getCurrentUser();
    }

    /**
     * Main collaboration dashboard
     */
    public function index() {
        $this->requirePermission('collaboration.view');

        $data = [
            'title' => 'Collaboration Tools',
            'recent_messages' => $this->getRecentMessages(),
            'active_channels' => $this->getActiveChannels(),
            'shared_files' => $this->getSharedFiles(),
            'team_activity' => $this->getTeamActivity(),
            'collaboration_stats' => $this->getCollaborationStats()
        ];

        $this->render('modules/collaboration/dashboard', $data);
    }

    /**
     * Team messaging
     */
    public function messaging() {
        $this->requirePermission('collaboration.messaging.view');

        $data = [
            'title' => 'Team Messaging',
            'channels' => $this->getChannels(),
            'direct_messages' => $this->getDirectMessages(),
            'message_history' => $this->getMessageHistory(),
            'online_users' => $this->getOnlineUsers(),
            'message_templates' => $this->getMessageTemplates()
        ];

        $this->render('modules/collaboration/messaging', $data);
    }

    /**
     * File sharing and management
     */
    public function files() {
        $this->requirePermission('collaboration.files.view');

        $filters = [
            'type' => $_GET['type'] ?? null,
            'shared_by' => $_GET['shared_by'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $files = $this->getFiles($filters);

        $data = [
            'title' => 'File Sharing',
            'files' => $files,
            'filters' => $filters,
            'file_categories' => $this->getFileCategories(),
            'shared_with_me' => $this->getSharedWithMe(),
            'recent_uploads' => $this->getRecentUploads(),
            'storage_usage' => $this->getStorageUsage()
        ];

        $this->render('modules/collaboration/files', $data);
    }

    /**
     * Document collaboration
     */
    public function documents() {
        $this->requirePermission('collaboration.documents.view');

        $data = [
            'title' => 'Document Collaboration',
            'documents' => $this->getDocuments(),
            'document_templates' => $this->getDocumentTemplates(),
            'version_history' => $this->getVersionHistory(),
            'collaborators' => $this->getCollaborators(),
            'document_comments' => $this->getDocumentComments()
        ];

        $this->render('modules/collaboration/documents', $data);
    }

    /**
     * Video conferencing
     */
    public function videoConferencing() {
        $this->requirePermission('collaboration.video.view');

        $data = [
            'title' => 'Video Conferencing',
            'active_meetings' => $this->getActiveMeetings(),
            'scheduled_meetings' => $this->getScheduledMeetings(),
            'meeting_rooms' => $this->getMeetingRooms(),
            'meeting_recordings' => $this->getMeetingRecordings(),
            'video_settings' => $this->getVideoSettings()
        ];

        $this->render('modules/collaboration/video_conferencing', $data);
    }

    /**
     * Task management within collaboration
     */
    public function tasks() {
        $this->requirePermission('collaboration.tasks.view');

        $data = [
            'title' => 'Collaborative Tasks',
            'team_tasks' => $this->getTeamTasks(),
            'task_boards' => $this->getTaskBoards(),
            'task_assignments' => $this->getTaskAssignments(),
            'task_comments' => $this->getTaskComments(),
            'task_templates' => $this->getTaskTemplates()
        ];

        $this->render('modules/collaboration/tasks', $data);
    }

    /**
     * Calendar and scheduling
     */
    public function calendar() {
        $this->requirePermission('collaboration.calendar.view');

        $data = [
            'title' => 'Team Calendar',
            'calendar_events' => $this->getCalendarEvents(),
            'meeting_schedules' => $this->getMeetingSchedules(),
            'team_availability' => $this->getTeamAvailability(),
            'event_templates' => $this->getEventTemplates(),
            'calendar_sharing' => $this->getCalendarSharing()
        ];

        $this->render('modules/collaboration/calendar', $data);
    }

    /**
     * Knowledge base and documentation
     */
    public function knowledgeBase() {
        $this->requirePermission('collaboration.knowledge.view');

        $data = [
            'title' => 'Knowledge Base',
            'articles' => $this->getKnowledgeArticles(),
            'categories' => $this->getKnowledgeCategories(),
            'search_results' => $this->getSearchResults(),
            'popular_articles' => $this->getPopularArticles(),
            'article_templates' => $this->getArticleTemplates()
        ];

        $this->render('modules/collaboration/knowledge_base', $data);
    }

    /**
     * Team workspaces
     */
    public function workspaces() {
        $this->requirePermission('collaboration.workspaces.view');

        $data = [
            'title' => 'Team Workspaces',
            'workspaces' => $this->getWorkspaces(),
            'workspace_members' => $this->getWorkspaceMembers(),
            'workspace_activity' => $this->getWorkspaceActivity(),
            'workspace_templates' => $this->getWorkspaceTemplates(),
            'workspace_permissions' => $this->getWorkspacePermissions()
        ];

        $this->render('modules/collaboration/workspaces', $data);
    }

    // ============================================================================
    // PRIVATE METHODS
    // ============================================================================

    private function getRecentMessages() {
        return $this->db->query("
            SELECT
                m.*,
                u.first_name as sender_first,
                u.last_name as sender_last,
                c.name as channel_name,
                TIMESTAMPDIFF(MINUTE, m.created_at, NOW()) as minutes_ago
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            LEFT JOIN channels c ON m.channel_id = c.id
            WHERE m.company_id = ? AND m.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY m.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getActiveChannels() {
        return $this->db->query("
            SELECT
                c.*,
                COUNT(cm.user_id) as member_count,
                COUNT(m.id) as message_count,
                MAX(m.created_at) as last_message_at
            FROM channels c
            LEFT JOIN channel_members cm ON c.id = cm.channel_id
            LEFT JOIN messages m ON c.id = m.channel_id
            WHERE c.company_id = ? AND c.is_active = true
            GROUP BY c.id
            ORDER BY last_message_at DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getSharedFiles() {
        return $this->db->query("
            SELECT
                f.*,
                u.first_name as shared_by_first,
                u.last_name as shared_by_last,
                COUNT(fa.id) as access_count,
                MAX(fa.accessed_at) as last_accessed
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            LEFT JOIN file_access fa ON f.id = fa.file_id
            WHERE f.company_id = ? AND f.is_shared = true
            GROUP BY f.id, u.first_name, u.last_name
            ORDER BY f.created_at DESC
            LIMIT 15
        ", [$this->user['company_id']]);
    }

    private function getTeamActivity() {
        return $this->db->query("
            SELECT
                ta.*,
                u.first_name as user_first,
                u.last_name as user_last,
                ta.activity_type,
                ta.description,
                TIMESTAMPDIFF(MINUTE, ta.created_at, NOW()) as minutes_ago
            FROM team_activity ta
            LEFT JOIN users u ON ta.user_id = u.id
            WHERE ta.company_id = ? AND ta.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY ta.created_at DESC
            LIMIT 30
        ", [$this->user['company_id']]);
    }

    private function getCollaborationStats() {
        return $this->db->querySingle("
            SELECT
                COUNT(DISTINCT c.id) as total_channels,
                COUNT(DISTINCT f.id) as total_files,
                COUNT(DISTINCT d.id) as total_documents,
                COUNT(m.id) as total_messages,
                COUNT(DISTINCT cm.user_id) as active_users,
                AVG(m.message_length) as avg_message_length
            FROM channels c
            LEFT JOIN files f ON f.company_id = c.company_id
            LEFT JOIN documents d ON d.company_id = c.company_id
            LEFT JOIN messages m ON m.company_id = c.company_id
            LEFT JOIN channel_members cm ON cm.company_id = c.company_id
            WHERE c.company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getChannels() {
        return $this->db->query("
            SELECT
                c.*,
                COUNT(cm.user_id) as member_count,
                COUNT(m.id) as message_count,
                MAX(m.created_at) as last_message_at,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM channels c
            LEFT JOIN channel_members cm ON c.id = cm.channel_id
            LEFT JOIN messages m ON c.id = m.channel_id
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.company_id = ?
            GROUP BY c.id, u.first_name, u.last_name
            ORDER BY c.name ASC
        ", [$this->user['company_id']]);
    }

    private function getDirectMessages() {
        return $this->db->query("
            SELECT
                dm.*,
                u1.first_name as sender_first,
                u1.last_name as sender_last,
                u2.first_name as recipient_first,
                u2.last_name as recipient_last,
                COUNT(dmr.id) as reply_count,
                MAX(dm.created_at) as last_message_at
            FROM direct_messages dm
            LEFT JOIN users u1 ON dm.sender_id = u1.id
            LEFT JOIN users u2 ON dm.recipient_id = u2.id
            LEFT JOIN direct_message_replies dmr ON dm.id = dmr.message_id
            WHERE dm.company_id = ? AND (dm.sender_id = ? OR dm.recipient_id = ?)
            GROUP BY dm.id, u1.first_name, u1.last_name, u2.first_name, u2.last_name
            ORDER BY last_message_at DESC
        ", [$this->user['company_id'], $this->user['id'], $this->user['id']]);
    }

    private function getMessageHistory() {
        return $this->db->query("
            SELECT
                m.*,
                u.first_name as sender_first,
                u.last_name as sender_last,
                c.name as channel_name,
                COUNT(mr.id) as reply_count,
                COUNT(ml.id) as like_count
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            LEFT JOIN channels c ON m.channel_id = c.id
            LEFT JOIN message_replies mr ON m.id = mr.message_id
            LEFT JOIN message_likes ml ON m.id = ml.message_id
            WHERE m.company_id = ?
            GROUP BY m.id, u.first_name, u.last_name, c.name
            ORDER BY m.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getOnlineUsers() {
        return $this->db->query("
            SELECT
                u.*,
                us.status as online_status,
                us.last_seen,
                TIMESTAMPDIFF(MINUTE, us.last_seen, NOW()) as minutes_since_seen
            FROM users u
            LEFT JOIN user_status us ON u.id = us.user_id
            WHERE u.company_id = ? AND u.is_active = true
                AND us.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY us.last_seen DESC
        ", [$this->user['company_id']]);
    }

    private function getMessageTemplates() {
        return $this->db->query("
            SELECT * FROM message_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getFiles($filters) {
        $where = ["f.company_id = ?"];
        $params = [$this->user['company_id']];

        if ($filters['type']) {
            $where[] = "f.file_type = ?";
            $params[] = $filters['type'];
        }

        if ($filters['shared_by']) {
            $where[] = "f.uploaded_by = ?";
            $params[] = $filters['shared_by'];
        }

        if ($filters['date_from']) {
            $where[] = "f.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if ($filters['date_to']) {
            $where[] = "f.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if ($filters['search']) {
            $where[] = "(f.name LIKE ? OR f.description LIKE ? OR f.tags LIKE ?)";
            $search_term = '%' . $filters['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT
                f.*,
                u.first_name as uploaded_by_first,
                u.last_name as uploaded_by_last,
                COUNT(fa.id) as access_count,
                COUNT(fc.id) as comment_count,
                MAX(fa.accessed_at) as last_accessed
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            LEFT JOIN file_access fa ON f.id = fa.file_id
            LEFT JOIN file_comments fc ON f.id = fc.file_id
            WHERE $whereClause
            GROUP BY f.id, u.first_name, u.last_name
            ORDER BY f.created_at DESC
        ", $params);
    }

    private function getFileCategories() {
        return [
            'documents' => 'Documents',
            'images' => 'Images',
            'videos' => 'Videos',
            'audio' => 'Audio Files',
            'archives' => 'Archives',
            'spreadsheets' => 'Spreadsheets',
            'presentations' => 'Presentations',
            'other' => 'Other'
        ];
    }

    private function getSharedWithMe() {
        return $this->db->query("
            SELECT
                f.*,
                u.first_name as shared_by_first,
                u.last_name as shared_by_last,
                fp.permissions,
                fp.shared_at
            FROM files f
            JOIN file_permissions fp ON f.id = fp.file_id
            LEFT JOIN users u ON fp.shared_by = u.id
            WHERE fp.shared_with = ? AND f.company_id = ?
            ORDER BY fp.shared_at DESC
        ", [$this->user['id'], $this->user['company_id']]);
    }

    private function getRecentUploads() {
        return $this->db->query("
            SELECT
                f.*,
                u.first_name as uploaded_by_first,
                u.last_name as uploaded_by_last
            FROM files f
            LEFT JOIN users u ON f.uploaded_by = u.id
            WHERE f.company_id = ? AND f.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY f.created_at DESC
            LIMIT 20
        ", [$this->user['company_id']]);
    }

    private function getStorageUsage() {
        return $this->db->querySingle("
            SELECT
                SUM(file_size_bytes) as total_used,
                COUNT(*) as total_files,
                AVG(file_size_bytes) as avg_file_size,
                MAX(file_size_bytes) as largest_file,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as files_last_30_days
            FROM files
            WHERE company_id = ?
        ", [$this->user['company_id']]);
    }

    private function getDocuments() {
        return $this->db->query("
            SELECT
                d.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(dv.id) as version_count,
                COUNT(dc.id) as collaborator_count,
                MAX(dv.created_at) as last_modified
            FROM documents d
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN document_versions dv ON d.id = dv.document_id
            LEFT JOIN document_collaborators dc ON d.id = dc.document_id
            WHERE d.company_id = ?
            GROUP BY d.id, u.first_name, u.last_name
            ORDER BY d.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getDocumentTemplates() {
        return $this->db->query("
            SELECT * FROM document_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getVersionHistory() {
        return $this->db->query("
            SELECT
                dv.*,
                u.first_name as modified_by_first,
                u.last_name as modified_by_last,
                d.title as document_title
            FROM document_versions dv
            JOIN documents d ON dv.document_id = d.id
            LEFT JOIN users u ON dv.modified_by = u.id
            WHERE dv.company_id = ?
            ORDER BY dv.created_at DESC
            LIMIT 50
        ", [$this->user['company_id']]);
    }

    private function getCollaborators() {
        return $this->db->query("
            SELECT
                dc.*,
                u.first_name as user_first,
                u.last_name as user_last,
                d.title as document_title,
                dc.permission_level,
                dc.last_accessed
            FROM document_collaborators dc
            JOIN documents d ON dc.document_id = d.id
            LEFT JOIN users u ON dc.user_id = u.id
            WHERE dc.company_id = ?
            ORDER BY dc.last_accessed DESC
        ", [$this->user['company_id']]);
    }

    private function getDocumentComments() {
        return $this->db->query("
            SELECT
                dc.*,
                u.first_name as commented_by_first,
                u.last_name as commented_by_last,
                d.title as document_title,
                TIMESTAMPDIFF(MINUTE, dc.created_at, NOW()) as minutes_ago
            FROM document_comments dc
            JOIN documents d ON dc.document_id = d.id
            LEFT JOIN users u ON dc.commented_by = u.id
            WHERE dc.company_id = ?
            ORDER BY dc.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getActiveMeetings() {
        return $this->db->query("
            SELECT
                vm.*,
                u.first_name as host_first,
                u.last_name as host_last,
                COUNT(vmp.id) as participant_count,
                TIMESTAMPDIFF(MINUTE, vm.started_at, NOW()) as duration_minutes
            FROM video_meetings vm
            LEFT JOIN users u ON vm.host_id = u.id
            LEFT JOIN video_meeting_participants vmp ON vm.id = vmp.meeting_id
            WHERE vm.company_id = ? AND vm.status = 'active'
            GROUP BY vm.id, u.first_name, u.last_name
            ORDER BY vm.started_at DESC
        ", [$this->user['company_id']]);
    }

    private function getScheduledMeetings() {
        return $this->db->query("
            SELECT
                vm.*,
                u.first_name as host_first,
                u.last_name as host_last,
                COUNT(vmp.id) as invited_count,
                TIMESTAMPDIFF(MINUTE, NOW(), vm.scheduled_at) as minutes_until_start
            FROM video_meetings vm
            LEFT JOIN users u ON vm.host_id = u.id
            LEFT JOIN video_meeting_participants vmp ON vm.id = vmp.meeting_id
            WHERE vm.company_id = ? AND vm.status = 'scheduled'
                AND vm.scheduled_at >= NOW()
            GROUP BY vm.id, u.first_name, u.last_name
            ORDER BY vm.scheduled_at ASC
        ", [$this->user['company_id']]);
    }

    private function getMeetingRooms() {
        return $this->db->query("
            SELECT
                mr.*,
                COUNT(vm.id) as meeting_count,
                AVG(vm.duration_minutes) as avg_meeting_duration,
                MAX(vm.created_at) as last_used
            FROM meeting_rooms mr
            LEFT JOIN video_meetings vm ON mr.id = vm.room_id
            WHERE mr.company_id = ?
            GROUP BY mr.id
            ORDER BY mr.name ASC
        ", [$this->user['company_id']]);
    }

    private function getMeetingRecordings() {
        return $this->db->query("
            SELECT
                vmr.*,
                vm.title as meeting_title,
                u.first_name as recorded_by_first,
                u.last_name as recorded_by_last,
                vmr.duration_seconds,
                vmr.file_size_bytes
            FROM video_meeting_recordings vmr
            JOIN video_meetings vm ON vmr.meeting_id = vm.id
            LEFT JOIN users u ON vmr.recorded_by = u.id
            WHERE vmr.company_id = ?
            ORDER BY vmr.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getVideoSettings() {
        return $this->db->querySingle("
            SELECT * FROM video_settings
            WHERE company_id = ? AND user_id = ?
        ", [$this->user['company_id'], $this->user['id']]);
    }

    private function getTeamTasks() {
        return $this->db->query("
            SELECT
                t.*,
                u1.first_name as assigned_to_first,
                u1.last_name as assigned_to_last,
                u2.first_name as created_by_first,
                u2.last_name as created_by_last,
                tb.name as board_name,
                COUNT(tc.id) as comment_count,
                COUNT(ta.id) as attachment_count
            FROM tasks t
            LEFT JOIN users u1 ON t.assigned_to = u1.id
            LEFT JOIN users u2 ON t.created_by = u2.id
            LEFT JOIN task_boards tb ON t.board_id = tb.id
            LEFT JOIN task_comments tc ON t.id = tc.task_id
            LEFT JOIN task_attachments ta ON t.id = ta.task_id
            WHERE t.company_id = ?
            GROUP BY t.id, u1.first_name, u1.last_name, u2.first_name, u2.last_name, tb.name
            ORDER BY t.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTaskBoards() {
        return $this->db->query("
            SELECT
                tb.*,
                COUNT(t.id) as task_count,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                u.first_name as created_by_first,
                u.last_name as created_by_last
            FROM task_boards tb
            LEFT JOIN tasks t ON tb.id = t.board_id
            LEFT JOIN users u ON tb.created_by = u.id
            WHERE tb.company_id = ?
            GROUP BY tb.id, u.first_name, u.last_name
            ORDER BY tb.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTaskAssignments() {
        return $this->db->query("
            SELECT
                ta.*,
                t.title as task_title,
                u.first_name as assigned_to_first,
                u.last_name as assigned_to_last,
                ta.assigned_at,
                ta.due_date
            FROM task_assignments ta
            JOIN tasks t ON ta.task_id = t.id
            LEFT JOIN users u ON ta.assigned_to = u.id
            WHERE ta.company_id = ?
            ORDER BY ta.assigned_at DESC
        ", [$this->user['company_id']]);
    }

    private function getTaskComments() {
        return $this->db->query("
            SELECT
                tc.*,
                u.first_name as commented_by_first,
                u.last_name as commented_by_last,
                t.title as task_title,
                TIMESTAMPDIFF(MINUTE, tc.created_at, NOW()) as minutes_ago
            FROM task_comments tc
            JOIN tasks t ON tc.task_id = t.id
            LEFT JOIN users u ON tc.commented_by = u.id
            WHERE tc.company_id = ?
            ORDER BY tc.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getTaskTemplates() {
        return $this->db->query("
            SELECT * FROM task_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getCalendarEvents() {
        return $this->db->query("
            SELECT
                ce.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(cea.user_id) as attendee_count,
                ce.event_type,
                ce.recurrence_pattern
            FROM calendar_events ce
            LEFT JOIN users u ON ce.created_by = u.id
            LEFT JOIN calendar_event_attendees cea ON ce.id = cea.event_id
            WHERE ce.company_id = ?
            GROUP BY ce.id, u.first_name, u.last_name
            ORDER BY ce.start_date ASC
        ", [$this->user['company_id']]);
    }

    private function getMeetingSchedules() {
        return $this->db->query("
            SELECT
                ms.*,
                u.first_name as scheduled_by_first,
                u.last_name as scheduled_by_last,
                mr.name as room_name,
                COUNT(msa.user_id) as attendee_count
            FROM meeting_schedules ms
            LEFT JOIN users u ON ms.scheduled_by = u.id
            LEFT JOIN meeting_rooms mr ON ms.room_id = mr.id
            LEFT JOIN meeting_schedule_attendees msa ON ms.id = msa.schedule_id
            WHERE ms.company_id = ?
            GROUP BY ms.id, u.first_name, u.last_name, mr.name
            ORDER BY ms.start_time ASC
        ", [$this->user['company_id']]);
    }

    private function getTeamAvailability() {
        return $this->db->query("
            SELECT
                u.first_name,
                u.last_name,
                ua.day_of_week,
                ua.start_time,
                ua.end_time,
                ua.is_available,
                ua.timezone
            FROM users u
            LEFT JOIN user_availability ua ON u.id = ua.user_id
            WHERE u.company_id = ?
            ORDER BY ua.day_of_week, ua.start_time
        ", [$this->user['company_id']]);
    }

    private function getEventTemplates() {
        return $this->db->query("
            SELECT * FROM event_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getCalendarSharing() {
        return $this->db->query("
            SELECT
                cs.*,
                u1.first_name as shared_by_first,
                u1.last_name as shared_by_last,
                u2.first_name as shared_with_first,
                u2.last_name as shared_with_last,
                cs.permission_level,
                cs.shared_at
            FROM calendar_sharing cs
            LEFT JOIN users u1 ON cs.shared_by = u1.id
            LEFT JOIN users u2 ON cs.shared_with = u2.id
            WHERE cs.company_id = ?
            ORDER BY cs.shared_at DESC
        ", [$this->user['company_id']]);
    }

    private function getKnowledgeArticles() {
        return $this->db->query("
            SELECT
                ka.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                kc.name as category_name,
                COUNT(kav.id) as view_count,
                COUNT(kah.id) as helpful_count,
                AVG(kar.rating) as avg_rating
            FROM knowledge_articles ka
            LEFT JOIN users u ON ka.created_by = u.id
            LEFT JOIN knowledge_categories kc ON ka.category_id = kc.id
            LEFT JOIN knowledge_article_views kav ON ka.id = kav.article_id
            LEFT JOIN knowledge_article_helpful kah ON ka.id = kah.article_id
            LEFT JOIN knowledge_article_ratings kar ON ka.id = kar.article_id
            WHERE ka.company_id = ?
            GROUP BY ka.id, u.first_name, u.last_name, kc.name
            ORDER BY ka.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getKnowledgeCategories() {
        return $this->db->query("
            SELECT
                kc.*,
                COUNT(ka.id) as article_count,
                MAX(ka.created_at) as last_updated
            FROM knowledge_categories kc
            LEFT JOIN knowledge_articles ka ON kc.id = ka.category_id
            WHERE kc.company_id = ?
            GROUP BY kc.id
            ORDER BY kc.name ASC
        ", [$this->user['company_id']]);
    }

    private function getSearchResults() {
        $searchTerm = $_GET['q'] ?? '';
        if (empty($searchTerm)) {
            return [];
        }

        return $this->db->query("
            SELECT
                ka.*,
                kc.name as category_name,
                MATCH(ka.title, ka.content, ka.tags) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
            FROM knowledge_articles ka
            LEFT JOIN knowledge_categories kc ON ka.category_id = kc.id
            WHERE ka.company_id = ? AND ka.status = 'published'
                AND MATCH(ka.title, ka.content, ka.tags) AGAINST(? IN NATURAL LANGUAGE MODE)
            ORDER BY relevance_score DESC
            LIMIT 20
        ", [$searchTerm, $this->user['company_id'], $searchTerm]);
    }

    private function getPopularArticles() {
        return $this->db->query("
            SELECT
                ka.*,
                kc.name as category_name,
                COUNT(kav.id) as view_count,
                COUNT(kah.id) as helpful_count,
                AVG(kar.rating) as avg_rating
            FROM knowledge_articles ka
            LEFT JOIN knowledge_categories kc ON ka.category_id = kc.id
            LEFT JOIN knowledge_article_views kav ON ka.id = kav.article_id
            LEFT JOIN knowledge_article_helpful kah ON ka.id = kah.article_id
            LEFT JOIN knowledge_article_ratings kar ON ka.id = kar.article_id
            WHERE ka.company_id = ?
            GROUP BY ka.id, kc.name
            ORDER BY view_count DESC, helpful_count DESC
            LIMIT 10
        ", [$this->user['company_id']]);
    }

    private function getArticleTemplates() {
        return $this->db->query("
            SELECT * FROM article_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getWorkspaces() {
        return $this->db->query("
            SELECT
                w.*,
                u.first_name as created_by_first,
                u.last_name as created_by_last,
                COUNT(wm.user_id) as member_count,
                COUNT(c.id) as channel_count,
                COUNT(f.id) as file_count,
                MAX(wa.created_at) as last_activity
            FROM workspaces w
            LEFT JOIN users u ON w.created_by = u.id
            LEFT JOIN workspace_members wm ON w.id = wm.workspace_id
            LEFT JOIN channels c ON w.id = c.workspace_id
            LEFT JOIN files f ON w.id = f.workspace_id
            LEFT JOIN workspace_activity wa ON w.id = wa.workspace_id
            WHERE w.company_id = ?
            GROUP BY w.id, u.first_name, u.last_name
            ORDER BY w.created_at DESC
        ", [$this->user['company_id']]);
    }

    private function getWorkspaceMembers() {
        return $this->db->query("
            SELECT
                wm.*,
                u.first_name as user_first,
                u.last_name as user_last,
                w.name as workspace_name,
                wm.role as member_role,
                wm.joined_at
            FROM workspace_members wm
            JOIN workspaces w ON wm.workspace_id = w.id
            LEFT JOIN users u ON wm.user_id = u.id
            WHERE wm.company_id = ?
            ORDER BY wm.joined_at DESC
        ", [$this->user['company_id']]);
    }

    private function getWorkspaceActivity() {
        return $this->db->query("
            SELECT
                wa.*,
                u.first_name as user_first,
                u.last_name as user_last,
                w.name as workspace_name,
                wa.activity_type,
                wa.description,
                TIMESTAMPDIFF(MINUTE, wa.created_at, NOW()) as minutes_ago
            FROM workspace_activity wa
            JOIN workspaces w ON wa.workspace_id = w.id
            LEFT JOIN users u ON wa.user_id = u.id
            WHERE wa.company_id = ?
            ORDER BY wa.created_at DESC
            LIMIT 100
        ", [$this->user['company_id']]);
    }

    private function getWorkspaceTemplates() {
        return $this->db->query("
            SELECT * FROM workspace_templates
            WHERE company_id = ? AND is_active = true
            ORDER BY category, name
        ", [$this->user['company_id']]);
    }

    private function getWorkspacePermissions() {
        return $this->db->query("
            SELECT
                wp.*,
                w.name as workspace_name,
                u.first_name as user_first,
                u.last_name as user_last,
                wp.permission_level,
                wp.granted_at
            FROM workspace_permissions wp
            JOIN workspaces w ON wp.workspace_id = w.id
            LEFT JOIN users u ON wp.user_id = u.id
            WHERE wp.company_id = ?
            ORDER BY wp.granted_at DESC
        ", [$this->user['company_id']]);
    }

    // ============================================================================
    // API ENDPOINTS
    // ============================================================================

    public function sendMessage() {
        $this->requirePermission('collaboration.messaging.send');

        $data = $this->validateRequest([
            'channel_id' => 'integer',
            'content' => 'required|string',
            'message_type' => 'string',
            'attachments' => 'array'
        ]);

        try {
            $messageId = $this->db->insert('messages', [
                'company_id' => $this->user['company_id'],
                'channel_id' => $data['channel_id'],
                'sender_id' => $this->user['id'],
                'content' => $data['content'],
                'message_type' => $data['message_type'] ?? 'text',
                'attachments' => json_encode($data['attachments'] ?? []),
                'message_length' => strlen($data['content'])
            ]);

            // Update channel last activity
            $this->db->update('channels', [
                'last_activity_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$data['channel_id']]);

            $this->jsonResponse([
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Message sent successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadFile() {
        $this->requirePermission('collaboration.files.upload');

        try {
            $file = $_FILES['file'];
            $description = $_POST['description'] ?? '';
            $isShared = isset($_POST['is_shared']) ? (bool)$_POST['is_shared'] : false;

            // Validate file
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload failed');
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filepath = '/uploads/files/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to save file');
            }

            $fileId = $this->db->insert('files', [
                'company_id' => $this->user['company_id'],
                'name' => $file['name'],
                'filename' => $filename,
                'filepath' => $filepath,
                'file_type' => $this->getFileType($extension),
                'file_size_bytes' => $file['size'],
                'mime_type' => $file['type'],
                'description' => $description,
                'is_shared' => $isShared,
                'uploaded_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'file_id' => $fileId,
                'filename' => $filename,
                'message' => 'File uploaded successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getFileType($extension) {
        $types = [
            'pdf' => 'documents',
            'doc' => 'documents',
            'docx' => 'documents',
            'txt' => 'documents',
            'jpg' => 'images',
            'jpeg' => 'images',
            'png' => 'images',
            'gif' => 'images',
            'mp4' => 'videos',
            'avi' => 'videos',
            'mp3' => 'audio',
            'wav' => 'audio',
            'zip' => 'archives',
            'rar' => 'archives',
            'xls' => 'spreadsheets',
            'xlsx' => 'spreadsheets',
            'ppt' => 'presentations',
            'pptx' => 'presentations'
        ];

        return $types[$extension] ?? 'other';
    }

    public function createDocument() {
        $this->requirePermission('collaboration.documents.create');

        $data = $this->validateRequest([
            'title' => 'required|string',
            'content' => 'string',
            'template_id' => 'integer',
            'is_collaborative' => 'boolean'
        ]);

        try {
            $documentId = $this->db->insert('documents', [
                'company_id' => $this->user['company_id'],
                'title' => $data['title'],
                'content' => $data['content'] ?? '',
                'template_id' => $data['template_id'] ?? null,
                'is_collaborative' => $data['is_collaborative'] ?? false,
                'status' => 'draft',
                'created_by' => $this->user['id']
            ]);

            // Add creator as collaborator
            $this->db->insert('document_collaborators', [
                'company_id' => $this->user['company_id'],
                'document_id' => $documentId,
                'user_id' => $this->user['id'],
                'permission_level' => 'owner',
                'added_at' => date('Y-m-d H:i:s')
            ]);

            $this->jsonResponse([
                'success' => true,
                'document_id' => $documentId,
                'message' => 'Document created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function startMeeting() {
        $this->requirePermission('collaboration.video.start');

        $data = $this->validateRequest([
            'title' => 'required|string',
            'room_id' => 'integer',
            'is_recording' => 'boolean',
            'participants' => 'array'
        ]);

        try {
            $meetingId = $this->db->insert('video_meetings', [
                'company_id' => $this->user['company_id'],
                'title' => $data['title'],
                'room_id' => $data['room_id'] ?? null,
                'host_id' => $this->user['id'],
                'meeting_type' => 'instant',
                'status' => 'active',
                'is_recording' => $data['is_recording'] ?? false,
                'started_at' => date('Y-m-d H:i:s'),
                'meeting_code' => $this->generateMeetingCode()
            ]);

            // Add participants
            if (!empty($data['participants'])) {
                foreach ($data['participants'] as $participantId) {
                    $this->db->insert('video_meeting_participants', [
                        'meeting_id' => $meetingId,
                        'user_id' => $participantId,
                        'joined_at' => date('Y-m-d H:i:s'),
                        'status' => 'invited'
                    ]);
                }
            }

            $this->jsonResponse([
                'success' => true,
                'meeting_id' => $meetingId,
                'meeting_code' => $this->generateMeetingCode(),
                'message' => 'Meeting started successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateMeetingCode() {
        return strtoupper(substr(md5(uniqid()), 0, 8));
    }

    public function createTask() {
        $this->requirePermission('collaboration.tasks.create');

        $data = $this->validateRequest([
            'title' => 'required|string',
            'description' => 'string',
            'board_id' => 'required|integer',
            'assigned_to' => 'integer',
            'due_date' => 'date',
            'priority' => 'string',
            'labels' => 'array'
        ]);

        try {
            $taskId = $this->db->insert('tasks', [
                'company_id' => $this->user['company_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'board_id' => $data['board_id'],
                'assigned_to' => $data['assigned_to'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'todo',
                'labels' => json_encode($data['labels'] ?? []),
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'task_id' => $taskId,
                'message' => 'Task created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
