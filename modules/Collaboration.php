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
            'online_users' => $this->getOnlineUsersData(),
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

    private function getOnlineUsersData() {
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

    public function createChannel() {
        $this->requirePermission('collaboration.messaging.create_channel');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'description' => 'string',
            'is_private' => 'boolean',
            'members' => 'array'
        ]);

        try {
            $channelId = $this->db->insert('channels', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'is_private' => $data['is_private'] ?? false,
                'is_active' => true,
                'created_by' => $this->user['id'],
                'last_activity_at' => date('Y-m-d H:i:s')
            ]);

            // Add creator as member
            $this->db->insert('channel_members', [
                'channel_id' => $channelId,
                'user_id' => $this->user['id'],
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s')
            ]);

            // Add other members if provided
            if (!empty($data['members'])) {
                foreach ($data['members'] as $memberId) {
                    $this->db->insert('channel_members', [
                        'channel_id' => $channelId,
                        'user_id' => $memberId,
                        'role' => 'member',
                        'joined_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            $this->jsonResponse([
                'success' => true,
                'channel_id' => $channelId,
                'message' => 'Channel created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function shareFile() {
        $this->requirePermission('collaboration.files.share');

        $data = $this->validateRequest([
            'file_id' => 'required|integer',
            'shared_with' => 'required|array',
            'permissions' => 'required|string',
            'message' => 'string'
        ]);

        try {
            $file = $this->db->querySingle("
                SELECT * FROM files
                WHERE id = ? AND company_id = ?
            ", [$data['file_id'], $this->user['company_id']]);

            if (!$file) {
                throw new Exception('File not found');
            }

            // Share with each user
            foreach ($data['shared_with'] as $userId) {
                $this->db->insert('file_permissions', [
                    'company_id' => $this->user['company_id'],
                    'file_id' => $data['file_id'],
                    'shared_by' => $this->user['id'],
                    'shared_with' => $userId,
                    'permissions' => $data['permissions'],
                    'shared_at' => date('Y-m-d H:i:s')
                ]);

                // Send notification
                $this->sendFileShareNotification($userId, $file, $data['message'] ?? '');
            }

            // Update file as shared
            $this->db->update('files', [
                'is_shared' => true
            ], 'id = ?', [$data['file_id']]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'File shared successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function sendFileShareNotification($userId, $file, $message) {
        // Implementation for sending file share notification
        // This would integrate with the notification system
    }

    public function addDocumentCollaborator() {
        $this->requirePermission('collaboration.documents.collaborate');

        $data = $this->validateRequest([
            'document_id' => 'required|integer',
            'user_id' => 'required|integer',
            'permission_level' => 'required|string'
        ]);

        try {
            $document = $this->db->querySingle("
                SELECT * FROM documents
                WHERE id = ? AND company_id = ?
            ", [$data['document_id'], $this->user['company_id']]);

            if (!$document) {
                throw new Exception('Document not found');
            }

            // Check if user is already a collaborator
            $existing = $this->db->querySingle("
                SELECT id FROM document_collaborators
                WHERE document_id = ? AND user_id = ?
            ", [$data['document_id'], $data['user_id']]);

            if ($existing) {
                // Update permission level
                $this->db->update('document_collaborators', [
                    'permission_level' => $data['permission_level'],
                    'last_accessed' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existing['id']]);
            } else {
                // Add new collaborator
                $this->db->insert('document_collaborators', [
                    'company_id' => $this->user['company_id'],
                    'document_id' => $data['document_id'],
                    'user_id' => $data['user_id'],
                    'permission_level' => $data['permission_level'],
                    'added_by' => $this->user['id'],
                    'added_at' => date('Y-m-d H:i:s'),
                    'last_accessed' => date('Y-m-d H:i:s')
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Collaborator added successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function scheduleMeeting() {
        $this->requirePermission('collaboration.video.schedule');

        $data = $this->validateRequest([
            'title' => 'required|string',
            'description' => 'string',
            'scheduled_at' => 'required|datetime',
            'duration_minutes' => 'required|integer',
            'room_id' => 'integer',
            'participants' => 'required|array',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'string'
        ]);

        try {
            $meetingId = $this->db->insert('video_meetings', [
                'company_id' => $this->user['company_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'host_id' => $this->user['id'],
                'room_id' => $data['room_id'] ?? null,
                'meeting_type' => 'scheduled',
                'status' => 'scheduled',
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $data['duration_minutes'],
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurrence_pattern' => $data['recurrence_pattern'] ?? null,
                'meeting_code' => $this->generateMeetingCode()
            ]);

            // Add participants
            foreach ($data['participants'] as $participantId) {
                $this->db->insert('video_meeting_participants', [
                    'meeting_id' => $meetingId,
                    'user_id' => $participantId,
                    'status' => 'invited'
                ]);
            }

            $this->jsonResponse([
                'success' => true,
                'meeting_id' => $meetingId,
                'meeting_code' => $this->generateMeetingCode(),
                'message' => 'Meeting scheduled successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createCalendarEvent() {
        $this->requirePermission('collaboration.calendar.create');

        $data = $this->validateRequest([
            'title' => 'required|string',
            'description' => 'string',
            'start_date' => 'required|datetime',
            'end_date' => 'required|datetime',
            'event_type' => 'required|string',
            'location' => 'string',
            'attendees' => 'array',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'string',
            'reminder_minutes' => 'integer'
        ]);

        try {
            $eventId = $this->db->insert('calendar_events', [
                'company_id' => $this->user['company_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'event_type' => $data['event_type'],
                'location' => $data['location'] ?? '',
                'is_recurring' => $data['is_recurring'] ?? false,
                'recurrence_pattern' => $data['recurrence_pattern'] ?? null,
                'reminder_minutes' => $data['reminder_minutes'] ?? 15,
                'created_by' => $this->user['id']
            ]);

            // Add attendees
            if (!empty($data['attendees'])) {
                foreach ($data['attendees'] as $attendeeId) {
                    $this->db->insert('calendar_event_attendees', [
                        'event_id' => $eventId,
                        'user_id' => $attendeeId,
                        'status' => 'pending'
                    ]);
                }
            }

            $this->jsonResponse([
                'success' => true,
                'event_id' => $eventId,
                'message' => 'Calendar event created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createKnowledgeArticle() {
        $this->requirePermission('collaboration.knowledge.create');

        $data = $this->validateRequest([
            'title' => 'required|string',
            'content' => 'required|string',
            'category_id' => 'required|integer',
            'tags' => 'array',
            'is_featured' => 'boolean',
            'attachments' => 'array'
        ]);

        try {
            $articleId = $this->db->insert('knowledge_articles', [
                'company_id' => $this->user['company_id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'category_id' => $data['category_id'],
                'tags' => json_encode($data['tags'] ?? []),
                'is_featured' => $data['is_featured'] ?? false,
                'attachments' => json_encode($data['attachments'] ?? []),
                'status' => 'draft',
                'created_by' => $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'article_id' => $articleId,
                'message' => 'Knowledge article created successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createWorkspace() {
        $this->requirePermission('collaboration.workspaces.create');

        $data = $this->validateRequest([
            'name' => 'required|string',
            'description' => 'string',
            'template_id' => 'integer',
            'members' => 'array',
            'settings' => 'array'
        ]);

        try {
            $workspaceId = $this->db->insert('workspaces', [
                'company_id' => $this->user['company_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'template_id' => $data['template_id'] ?? null,
                'settings' => json_encode($data['settings'] ?? []),
                'is_active' => true,
                'created_by' => $this->user['id']
            ]);

            // Add creator as admin member
            $this->db->insert('workspace_members', [
                'workspace_id' => $workspaceId,
                'user_id' => $this->user['id'],
                'role' => 'admin',
                'joined_at' => date('Y-m-d H:i:s')
            ]);

            // Add other members if provided
            if (!empty($data['members'])) {
                foreach ($data['members'] as $member) {
                    $this->db->insert('workspace_members', [
                        'workspace_id' => $workspaceId,
                        'user_id' => $member['user_id'],
                        'role' => $member['role'] ?? 'member',
                        'joined_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // Create default channels if using template
            if ($data['template_id']) {
                $this->createWorkspaceFromTemplate($workspaceId, $data['template_id']);
            }

            $this->jsonResponse([
                'success' => true,
                'workspace_id' => $workspaceId,
                'message' => 'Workspace created successfully'
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createWorkspaceFromTemplate($workspaceId, $templateId) {
        $template = $this->db->querySingle("
            SELECT * FROM workspace_templates
            WHERE id = ? AND company_id = ?
        ", [$templateId, $this->user['company_id']]);

        if ($template && $template['default_channels']) {
            $channels = json_decode($template['default_channels'], true);
            foreach ($channels as $channel) {
                $channelId = $this->db->insert('channels', [
                    'company_id' => $this->user['company_id'],
                    'workspace_id' => $workspaceId,
                    'name' => $channel['name'],
                    'description' => $channel['description'] ?? '',
                    'is_private' => $channel['is_private'] ?? false,
                    'is_active' => true,
                    'created_by' => $this->user['id']
                ]);

                // Add all workspace members to the channel
                $members = $this->db->query("
                    SELECT user_id FROM workspace_members
                    WHERE workspace_id = ?
                ", [$workspaceId]);

                foreach ($members as $member) {
                    $this->db->insert('channel_members', [
                        'channel_id' => $channelId,
                        'user_id' => $member['user_id'],
                        'role' => 'member',
                        'joined_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }

    public function joinMeeting() {
        $this->requirePermission('collaboration.video.join');

        $data = $this->validateRequest([
            'meeting_code' => 'required|string'
        ]);

        try {
            $meeting = $this->db->querySingle("
                SELECT * FROM video_meetings
                WHERE meeting_code = ? AND company_id = ?
            ", [$data['meeting_code'], $this->user['company_id']]);

            if (!$meeting) {
                throw new Exception('Meeting not found');
            }

            if ($meeting['status'] !== 'active') {
                throw new Exception('Meeting is not active');
            }

            // Check if user is a participant
            $participant = $this->db->querySingle("
                SELECT * FROM video_meeting_participants
                WHERE meeting_id = ? AND user_id = ?
            ", [$meeting['id'], $this->user['id']]);

            if (!$participant) {
                // Add user as participant
                $this->db->insert('video_meeting_participants', [
                    'meeting_id' => $meeting['id'],
                    'user_id' => $this->user['id'],
                    'joined_at' => date('Y-m-d H:i:s'),
                    'status' => 'joined'
                ]);
            } else {
                // Update participant status
                $this->db->update('video_meeting_participants', [
                    'joined_at' => date('Y-m-d H:i:s'),
                    'status' => 'joined'
                ], 'id = ?', [$participant['id']]);
            }

            $this->jsonResponse([
                'success' => true,
                'meeting' => $meeting,
                'message' => 'Joined meeting successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function leaveMeeting() {
        $this->requirePermission('collaboration.video.join');

        $data = $this->validateRequest([
            'meeting_id' => 'required|integer'
        ]);

        try {
            $participant = $this->db->querySingle("
                SELECT * FROM video_meeting_participants
                WHERE meeting_id = ? AND user_id = ?
            ", [$data['meeting_id'], $this->user['id']]);

            if ($participant) {
                $this->db->update('video_meeting_participants', [
                    'left_at' => date('Y-m-d H:i:s'),
                    'status' => 'left'
                ], 'id = ?', [$participant['id']]);
            }

            $this->jsonResponse([
                'success' => true,
                'message' => 'Left meeting successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addTaskComment() {
        $this->requirePermission('collaboration.tasks.comment');

        $data = $this->validateRequest([
            'task_id' => 'required|integer',
            'comment' => 'required|string',
            'attachments' => 'array'
        ]);

        try {
            $commentId = $this->db->insert('task_comments', [
                'company_id' => $this->user['company_id'],
                'task_id' => $data['task_id'],
                'commented_by' => $this->user['id'],
                'comment' => $data['comment'],
                'attachments' => json_encode($data['attachments'] ?? [])
            ]);

            $this->jsonResponse([
                'success' => true,
                'comment_id' => $commentId,
                'message' => 'Comment added successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateTaskStatus() {
        $this->requirePermission('collaboration.tasks.update');

        $data = $this->validateRequest([
            'task_id' => 'required|integer',
            'status' => 'required|string'
        ]);

        try {
            $this->db->update('tasks', [
                'status' => $data['status'],
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ? AND company_id = ?', [
                $data['task_id'],
                $this->user['company_id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Task status updated successfully'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchKnowledgeBase() {
        $this->requirePermission('collaboration.knowledge.view');

        $data = $this->validateRequest([
            'query' => 'required|string',
            'category_id' => 'integer',
            'limit' => 'integer'
        ]);

        try {
            $limit = min($data['limit'] ?? 20, 50); // Max 50 results

            $where = ["ka.company_id = ? AND ka.status = 'published'"];
            $params = [$this->user['company_id']];

            if ($data['category_id']) {
                $where[] = "ka.category_id = ?";
                $params[] = $data['category_id'];
            }

            $whereClause = implode(' AND ', $where);

            $results = $this->db->query("
                SELECT
                    ka.*,
                    kc.name as category_name,
                    MATCH(ka.title, ka.content, ka.tags) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
                FROM knowledge_articles ka
                LEFT JOIN knowledge_categories kc ON ka.category_id = kc.id
                WHERE $whereClause
                    AND MATCH(ka.title, ka.content, ka.tags) AGAINST(? IN NATURAL LANGUAGE MODE)
                ORDER BY relevance_score DESC
                LIMIT ?
            ", array_merge($params, [$data['query'], $data['query'], $limit]));

            $this->jsonResponse([
                'success' => true,
                'results' => $results,
                'query' => $data['query'],
                'total_results' => count($results)
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getOnlineUsers() {
        try {
            $users = $this->db->query("
                SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    us.status as online_status,
                    us.last_seen,
                    TIMESTAMPDIFF(MINUTE, us.last_seen, NOW()) as minutes_since_seen
                FROM users u
                LEFT JOIN user_status us ON u.id = us.user_id
                WHERE u.company_id = ? AND u.is_active = true
                    AND us.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY us.last_seen DESC
            ", [$this->user['company_id']]);

            $this->jsonResponse([
                'success' => true,
                'users' => $users,
                'total_online' => count($users)
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getChannelMessages() {
        $this->requirePermission('collaboration.messaging.view');

        $data = $this->validateRequest([
            'channel_id' => 'required|integer',
            'limit' => 'integer',
            'offset' => 'integer'
        ]);

        try {
            $limit = min($data['limit'] ?? 50, 100); // Max 100 messages
            $offset = $data['offset'] ?? 0;

            $messages = $this->db->query("
                SELECT
                    m.*,
                    u.first_name as sender_first,
                    u.last_name as sender_last,
                    COUNT(mr.id) as reply_count,
                    COUNT(ml.id) as like_count
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                LEFT JOIN message_replies mr ON m.id = mr.message_id
                LEFT JOIN message_likes ml ON m.id = ml.message_id
                WHERE m.channel_id = ? AND m.company_id = ?
                GROUP BY m.id, u.first_name, u.last_name
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ", [
                $data['channel_id'],
                $this->user['company_id'],
                $limit,
                $offset
            ]);

            $this->jsonResponse([
                'success' => true,
                'messages' => array_reverse($messages), // Return in chronological order
                'has_more' => count($messages) === $limit
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFileDownloadUrl() {
        $this->requirePermission('collaboration.files.view');

        $data = $this->validateRequest([
            'file_id' => 'required|integer'
        ]);

        try {
            $file = $this->db->querySingle("
                SELECT * FROM files
                WHERE id = ? AND company_id = ?
            ", [$data['file_id'], $this->user['company_id']]);

            if (!$file) {
                throw new Exception('File not found');
            }

            // Check permissions
            $hasPermission = $this->checkFilePermission($data['file_id'], $this->user['id']);
            if (!$hasPermission && $file['uploaded_by'] !== $this->user['id']) {
                throw new Exception('Access denied');
            }

            // Log file access
            $this->db->insert('file_access', [
                'file_id' => $data['file_id'],
                'user_id' => $this->user['id'],
                'accessed_at' => date('Y-m-d H:i:s')
            ]);

            $downloadUrl = '/api/collaboration/files/' . $file['id'] . '/download';

            $this->jsonResponse([
                'success' => true,
                'download_url' => $downloadUrl,
                'filename' => $file['name']
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function checkFilePermission($fileId, $userId) {
        $permission = $this->db->querySingle("
            SELECT permissions FROM file_permissions
            WHERE file_id = ? AND shared_with = ?
        ", [$fileId, $userId]);

        return $permission && in_array($permission['permissions'], ['read', 'write', 'admin']);
    }

    public function getDocumentContent() {
        $this->requirePermission('collaboration.documents.view');

        $data = $this->validateRequest([
            'document_id' => 'required|integer'
        ]);

        try {
            $document = $this->db->querySingle("
                SELECT * FROM documents
                WHERE id = ? AND company_id = ?
            ", [$data['document_id'], $this->user['company_id']]);

            if (!$document) {
                throw new Exception('Document not found');
            }

            // Check collaboration permissions
            $collaborator = $this->db->querySingle("
                SELECT permission_level FROM document_collaborators
                WHERE document_id = ? AND user_id = ?
            ", [$data['document_id'], $this->user['id']]);

            if (!$collaborator && $document['created_by'] !== $this->user['id']) {
                throw new Exception('Access denied');
            }

            // Update last accessed
            $this->db->update('document_collaborators', [
                'last_accessed' => date('Y-m-d H:i:s')
            ], 'document_id = ? AND user_id = ?', [
                $data['document_id'],
                $this->user['id']
            ]);

            $this->jsonResponse([
                'success' => true,
                'document' => $document,
                'permission_level' => $collaborator['permission_level'] ?? 'owner'
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateDocumentContent() {
        $this->requirePermission('collaboration.documents.edit');

        $data = $this->validateRequest([
            'document_id' => 'required|integer',
            'content' => 'required|string',
            'version_comment' => 'string'
        ]);

        try {
            $document = $this->db->querySingle("
                SELECT * FROM documents
                WHERE id = ? AND company_id = ?
            ", [$data['document_id'], $this->user['company_id']]);

            if (!$document) {
                throw new Exception('Document not found');
            }

            // Check write permissions
            $collaborator = $this->db->querySingle("
                SELECT permission_level FROM document_collaborators
                WHERE document_id = ? AND user_id = ?
            ", [$data['document_id'], $this->user['id']]);

            if (!$collaborator && $document['created_by'] !== $this->user['id']) {
                throw new Exception('Access denied');
            }

            if ($collaborator && !in_array($collaborator['permission_level'], ['write', 'admin', 'owner'])) {
                throw new Exception('Insufficient permissions');
            }

            // Create version backup
            $this->db->insert('document_versions', [
                'document_id' => $data['document_id'],
                'version_number' => $document['version'] + 1,
                'content' => $document['content'],
                'modified_by' => $this->user['id'],
                'comment' => $data['version_comment'] ?? 'Content updated'
            ]);

            // Update document
            $this->db->update('documents', [
                'content' => $data['content'],
                'version' => $document['version'] + 1,
                'last_modified' => date('Y-m-d H:i:s'),
                'last_modified_by' => $this->user['id']
            ], 'id = ?', [$data['document_id']]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Document updated successfully',
                'new_version' => $document['version'] + 1
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
?>
