<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\Core\Container;
use Noir\Core\Request;
use PDO;

final class AdminAiIntelligenceController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Container::get('db');
    }

    public function dashboard(Request $request): never
    {
        $tab = (string)$request->input('tab', 'demands'); // 'demands', 'conversations', 'knowledge', 'settings'
        $search = trim((string)$request->input('q', ''));
        $statusFilter = trim((string)$request->input('status', ''));
        $period = (string)$request->input('period', '7d'); // '1d', '7d', '30d', '1y', 'all'

        $dateClause = match ($period) {
            '1d', 'day', 'today' => "created_at >= CURDATE()",
            '7d', 'week' => "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            '30d', 'month' => "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            '1y', 'year' => "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)",
            default => "1=1",
        };

        $convDateClause = match ($period) {
            '1d', 'day', 'today' => "c.created_at >= CURDATE()",
            '7d', 'week' => "c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            '30d', 'month' => "c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            '1y', 'year' => "c.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)",
            default => "1=1",
        };

        // 1. High-Level Metrics
        $totalConversations = (int)$this->db->query("SELECT COUNT(*) FROM ai_stylist_conversations WHERE $dateClause")->fetchColumn();
        $totalMessages = (int)$this->db->query("SELECT COUNT(*) FROM ai_stylist_messages WHERE $dateClause")->fetchColumn();
        $totalDemands = (int)$this->db->query("SELECT COUNT(*) FROM ai_demand_signals WHERE $dateClause")->fetchColumn();
        $unmetDemands = (int)$this->db->query("SELECT COUNT(*) FROM ai_demand_signals WHERE unmet_demand_flag = 1 AND $dateClause")->fetchColumn();
        $unmetPct = $totalDemands > 0 ? round(($unmetDemands / $totalDemands) * 100, 1) : 0;
        $inProductionCount = (int)$this->db->query("SELECT COUNT(*) FROM ai_demand_signals WHERE status = 'in_production' AND $dateClause")->fetchColumn();

        // 2. Market Demand Intelligence Aggregations
        $categoryDemands = $this->db->query("
            SELECT COALESCE(category, 'General Capsule') as category_name, 
                   COUNT(*) as request_count, 
                   AVG(budget) as avg_budget,
                   SUM(unmet_demand_flag) as unmet_count
            FROM ai_demand_signals
            WHERE category IS NOT NULL AND category != '' AND $dateClause
            GROUP BY category
            ORDER BY request_count DESC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);

        $colorDemands = $this->db->query("
            SELECT COALESCE(color, 'Neutral') as color_name, 
                   COUNT(*) as request_count,
                   SUM(unmet_demand_flag) as unmet_count
            FROM ai_demand_signals
            WHERE color IS NOT NULL AND color != '' AND $dateClause
            GROUP BY color
            ORDER BY request_count DESC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);

        $styleDemands = $this->db->query("
            SELECT COALESCE(style, 'Minimalist') as style_name, 
                   COUNT(*) as request_count
            FROM ai_demand_signals
            WHERE style IS NOT NULL AND style != '' AND $dateClause
            GROUP BY style
            ORDER BY request_count DESC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);

        $occasionDemands = $this->db->query("
            SELECT COALESCE(occasion, 'Casual') as occasion_name, 
                   COUNT(*) as request_count
            FROM ai_demand_signals
            WHERE occasion IS NOT NULL AND occasion != '' AND $dateClause
            GROUP BY occasion
            ORDER BY request_count DESC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recent demand signals with search & filter
        $demandSql = "
            SELECT d.*, u.name as user_name, u.email as user_email
            FROM ai_demand_signals d
            LEFT JOIN users u ON u.id = d.user_id
            WHERE " . str_replace('created_at', 'd.created_at', $dateClause) . "
        ";
        $demandParams = [];
        if ($search !== '') {
            $demandSql .= " AND (d.raw_query LIKE ? OR d.category LIKE ? OR d.color LIKE ? OR d.style LIKE ?)";
            $demandParams[] = "%$search%";
            $demandParams[] = "%$search%";
            $demandParams[] = "%$search%";
            $demandParams[] = "%$search%";
        }
        if ($statusFilter !== '') {
            $demandSql .= " AND d.status = ?";
            $demandParams[] = $statusFilter;
        }
        $demandSql .= " ORDER BY d.created_at DESC LIMIT 50";
        $demandStmt = $this->db->prepare($demandSql);
        $demandStmt->execute($demandParams);
        $recentDemands = $demandStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Live Conversations Feed
        $convSql = "
            SELECT c.*, u.name as user_name, u.email as user_email,
                   (SELECT content FROM ai_stylist_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) as last_message,
                   (SELECT COUNT(*) FROM ai_stylist_messages m WHERE m.conversation_id = c.id) as message_count
            FROM ai_stylist_conversations c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE $convDateClause
            ORDER BY c.updated_at DESC
            LIMIT 30
        ";
        $conversations = $this->db->query($convSql)->fetchAll(PDO::FETCH_ASSOC);

        // 4. Knowledge Base Items
        $knowledgeItems = $this->db->query("SELECT * FROM ai_knowledge_base ORDER BY sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

        // 5. Config Weights
        $weightRow = $this->db->query("SELECT value FROM ai_stylist_config WHERE key_name = 'weights' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $weights = [
            'occasion' => 0.25,
            'preference' => 0.20,
            'color_harmony' => 0.15,
            'style_personality' => 0.15,
            'budget' => 0.15,
            'availability' => 0.10,
        ];
        if ($weightRow && !empty($weightRow['value'])) {
            $decoded = json_decode((string)$weightRow['value'], true);
            if (is_array($decoded)) $weights = array_merge($weights, $decoded);
        }

        // Current Admin User
        $user = $_SESSION['user'] ?? ['email' => 'admin@noir.test', 'name' => 'Administrator'];
        $base = defined('BASE_PATH') ? '' : '';
        $currentPeriod = $period;
        $currentTab = $tab;

        require BASE_PATH . '/app/Views/admin/ai-intelligence.php';
        exit;
    }

    public function conversationDetails(Request $request): never
    {
        $id = (int)$request->input('id');
        if (!$id) {
            json(['success' => false, 'message' => 'Conversation ID is required.'], 422);
        }

        $stmt = $this->db->prepare("
            SELECT c.*, u.name as user_name, u.email as user_email
            FROM ai_stylist_conversations c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            json(['success' => false, 'message' => 'Conversation not found.'], 404);
        }

        $msgStmt = $this->db->prepare("SELECT * FROM ai_stylist_messages WHERE conversation_id = ? ORDER BY id ASC");
        $msgStmt->execute([$id]);
        $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($messages as &$m) {
            if (!empty($m['structured_payload'])) {
                $m['structured_payload'] = json_decode((string)$m['structured_payload'], true);
            }
        }

        json([
            'success' => true,
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function updateDemandStatus(Request $request): never
    {
        $id = (int)$request->input('id');
        $status = (string)$request->input('status');
        $valid = ['detected', 'under_review', 'in_production', 'dropped'];

        if (!$id || !in_array($status, $valid, true)) {
            json(['success' => false, 'message' => 'Invalid status or ID.'], 422);
        }

        $stmt = $this->db->prepare("UPDATE ai_demand_signals SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        json(['success' => true, 'message' => 'Demand status updated to ' . ucwords(str_replace('_', ' ', $status))]);
    }

    public function saveKnowledge(Request $request): never
    {
        $id = (int)$request->input('id', 0);
        $category = trim((string)$request->input('category', 'general'));
        $title = trim((string)$request->input('title', ''));
        $keywords = trim((string)$request->input('keywords', ''));
        $content = trim((string)$request->input('content', ''));
        $actionType = trim((string)$request->input('action_type', '')) ?: null;
        $actionDestination = trim((string)$request->input('action_destination', '')) ?: null;
        $isActive = (int)$request->input('is_active', 1);

        if ($title === '' || $content === '') {
            json(['success' => false, 'message' => 'Title and content are required.'], 422);
        }

        if ($id > 0) {
            $stmt = $this->db->prepare("
                UPDATE ai_knowledge_base 
                SET category = ?, title = ?, keywords = ?, content = ?, action_type = ?, action_destination = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$category, $title, $keywords, $content, $actionType, $actionDestination, $isActive, $id]);
            json(['success' => true, 'message' => 'Knowledge base topic updated successfully.']);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO ai_knowledge_base (category, title, keywords, content, action_type, action_destination, is_active, sort_order, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
            ");
            $stmt->execute([$category, $title, $keywords, $content, $actionType, $actionDestination, $isActive]);
            json(['success' => true, 'message' => 'Knowledge base topic created successfully.']);
        }
    }

    public function deleteKnowledge(Request $request): never
    {
        $id = (int)$request->input('id');
        if (!$id) {
            json(['success' => false, 'message' => 'Topic ID is required.'], 422);
        }

        $stmt = $this->db->prepare("DELETE FROM ai_knowledge_base WHERE id = ?");
        $stmt->execute([$id]);

        json(['success' => true, 'message' => 'Topic removed from knowledge base.']);
    }

    public function saveWeights(Request $request): never
    {
        $weights = [
            'occasion' => (float)$request->input('weight_occasion', 0.25),
            'preference' => (float)$request->input('weight_preference', 0.20),
            'color_harmony' => (float)$request->input('weight_color', 0.15),
            'style_personality' => (float)$request->input('weight_style', 0.15),
            'budget' => (float)$request->input('weight_budget', 0.15),
            'availability' => (float)$request->input('weight_availability', 0.10),
        ];

        $stmt = $this->db->prepare("
            INSERT INTO ai_stylist_config (key_name, value, description, updated_at)
            VALUES ('weights', ?, 'Stylist Decision Multi-Factor Scoring Weights', NOW())
            ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()
        ");
        $stmt->execute([json_encode($weights)]);

        json(['success' => true, 'message' => 'Stylist scoring weights updated successfully.']);
    }

    public function exportDemands(Request $request): never
    {
        $rows = $this->db->query("
            SELECT d.id, d.created_at, COALESCE(u.email, d.session_id) as client,
                   d.category, d.style, d.color, d.budget, d.occasion,
                   d.had_catalog_match, d.unmet_demand_flag, d.status, d.raw_query
            FROM ai_demand_signals d
            LEFT JOIN users u ON u.id = d.user_id
            ORDER BY d.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="noir-ai-customer-demands-' . date('Y-m-d') . '.csv"');
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Date', 'Client / Session', 'Category', 'Style', 'Color', 'Budget (NGN)', 'Occasion', 'Catalog Match', 'Unmet Demand', 'Production Status', 'Raw Customer Prompt']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['created_at'],
                $r['client'],
                $r['category'] ?: 'Unspecified',
                $r['style'] ?: 'General',
                $r['color'] ?: 'Any',
                $r['budget'] ? number_format((float)$r['budget'], 2) : 'No limit',
                $r['occasion'] ?: 'Casual',
                $r['had_catalog_match'] ? 'YES' : 'NO',
                $r['unmet_demand_flag'] ? 'YES' : 'NO',
                ucwords(str_replace('_', ' ', (string)$r['status'])),
                $r['raw_query']
            ]);
        }

        fclose($out);
        exit;
    }
}
