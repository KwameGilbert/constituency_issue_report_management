<?php
// filepath: c:\xampp\htdocs\swma\admin\reports\export.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../login/session_check.php';

header('X-Content-Type-Options: nosniff');

$database = new Database();
$conn = $database->getConnection();

function jsonResponse($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function computeDateRange(string $period, ?string $start, ?string $end): ?array
{
    $now = new DateTime('now');
    $startDt = null;
    $endDt = null;

    switch ($period) {
        case 'all':
            return null;
        case 'week':
            $startDt = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
            $endDt = (clone $now)->setTime(23, 59, 59);
            break;
        case 'month':
            $startDt = new DateTime(date('Y-m-01 00:00:00'));
            $endDt = (clone $now)->setTime(23, 59, 59);
            break;
        case 'quarter':
            $month = (int)$now->format('n');
            $quarterStartMonth = (int)(floor(($month - 1) / 3) * 3) + 1;
            $startDt = new DateTime($now->format('Y') . '-' . str_pad((string)$quarterStartMonth, 2, '0', STR_PAD_LEFT) . '-01 00:00:00');
            $endDt = (clone $now)->setTime(23, 59, 59);
            break;
        case 'half':
            $halfStartMonth = ((int)$now->format('n') <= 6) ? 1 : 7;
            $startDt = new DateTime($now->format('Y') . '-' . str_pad((string)$halfStartMonth, 2, '0', STR_PAD_LEFT) . '-01 00:00:00');
            $endDt = (clone $now)->setTime(23, 59, 59);
            break;
        case 'year':
            $startDt = new DateTime($now->format('Y') . '-01-01 00:00:00');
            $endDt = (clone $now)->setTime(23, 59, 59);
            break;
        case 'custom':
            if ($start) {
                $startDt = DateTime::createFromFormat('Y-m-d H:i:s', $start . ' 00:00:00') ?: new DateTime($start . ' 00:00:00');
            }
            if ($end) {
                $endDt = DateTime::createFromFormat('Y-m-d H:i:s', $end . ' 23:59:59') ?: new DateTime($end . ' 23:59:59');
            }
            if (!$startDt || !$endDt) return null;
            break;
        default:
            return null;
    }
    return [$startDt->format('Y-m-d H:i:s'), $endDt->format('Y-m-d H:i:s')];
}

function getReportMeta(string $type): array
{
    switch ($type) {
        case 'issues':
            return [
                'table' => 'issues i',
                'date_column' => 'i.created_at',
                'joins' => [
                    'LEFT JOIN users ua ON i.agent_id = ua.id',
                    'LEFT JOIN users uo ON i.officer_id = uo.id',
                    'LEFT JOIN issue_categories ic ON i.category_id = ic.id',
                    'LEFT JOIN issue_sectors s ON i.sector_id = s.id',
                    'LEFT JOIN issue_subsectors ss ON i.subsector_id = ss.id',
                    'LEFT JOIN communities c ON i.main_community_id = c.id',
                    'LEFT JOIN smaller_communities sc ON i.smaller_community_id = sc.id',
                    'LEFT JOIN suburbs sub ON i.suburb_id = sub.id',
                    'LEFT JOIN cottages cot ON i.cottage_id = cot.id'
                ],
                'columns' => [
                    'id' => ['expr' => 'i.id', 'label' => 'ID'],
                    'title' => ['expr' => 'i.title', 'label' => 'Title'],
                    'status' => ['expr' => 'i.status', 'label' => 'Status'],
                    'severity' => ['expr' => 'i.severity', 'label' => 'Severity'],
                    'type' => ['expr' => 'i.type', 'label' => 'Type'],
                    'category' => ['expr' => 'ic.name', 'label' => 'Category'],
                    'sector' => ['expr' => 's.name', 'label' => 'Sector'],
                    'subsector' => ['expr' => 'ss.name', 'label' => 'Subsector'],
                    'agent_name' => ['expr' => 'ua.name', 'label' => 'Agent'],
                    'officer_name' => ['expr' => 'uo.name', 'label' => 'Officer'],
                    'people_affected' => ['expr' => 'i.people_affected', 'label' => 'People Affected'],
                    'budget_estimate' => ['expr' => 'i.budget_estimate', 'label' => 'Budget Estimate'],
                    'created_at' => ['expr' => 'i.created_at', 'label' => 'Created At'],
                    'resolved_at' => ['expr' => 'i.resolved_at', 'label' => 'Resolved At'],
                    'main_community' => ['expr' => 'c.name', 'label' => 'Main Community'],
                    'smaller_community' => ['expr' => 'sc.name', 'label' => 'Smaller Community'],
                    'suburb' => ['expr' => 'sub.name', 'label' => 'Suburb'],
                    'cottage' => ['expr' => 'cot.name', 'label' => 'Cottage']
                ],
                'defaults' => ['id', 'title', 'status', 'severity', 'type', 'category', 'agent_name', 'created_at']
            ];
        case 'projects':
            return [
                'table' => 'projects p',
                'date_column' => 'p.created_at',
                'joins' => [
                    'LEFT JOIN issue_sectors s ON p.sector_id = s.id',
                    'LEFT JOIN issue_subsectors ss ON p.subsector_id = ss.id',
                    'LEFT JOIN users u ON p.created_by = u.id'
                ],
                'columns' => [
                    'id' => ['expr' => 'p.id', 'label' => 'ID'],
                    'title' => ['expr' => 'p.title', 'label' => 'Title'],
                    'status' => ['expr' => 'p.status', 'label' => 'Status'],
                    'type' => ['expr' => 'p.type', 'label' => 'Type'],
                    'budget' => ['expr' => 'p.budget', 'label' => 'Budget'],
                    'contractor_name' => ['expr' => 'p.contractor_name', 'label' => 'Contractor'],
                    'sector' => ['expr' => 's.name', 'label' => 'Sector'],
                    'subsector' => ['expr' => 'ss.name', 'label' => 'Subsector'],
                    'started_at' => ['expr' => 'p.started_at', 'label' => 'Started At'],
                    'completed_at' => ['expr' => 'p.completed_at', 'label' => 'Completed At'],
                    'created_at' => ['expr' => 'p.created_at', 'label' => 'Created At'],
                    'is_public' => ['expr' => 'p.is_public', 'label' => 'Public?']
                ],
                'defaults' => ['id', 'title', 'status', 'type', 'budget', 'sector', 'created_at']
            ];
        case 'employment':
            return [
                'table' => 'employment_opportunities e',
                'date_column' => 'e.created_at',
                'joins' => [
                    'LEFT JOIN users u ON e.posted_by = u.id',
                    'LEFT JOIN (SELECT opportunity_id, COUNT(*) AS applications_count FROM employment_applications GROUP BY opportunity_id) a ON a.opportunity_id = e.id'
                ],
                'columns' => [
                    'id' => ['expr' => 'e.id', 'label' => 'ID'],
                    'title' => ['expr' => 'e.title', 'label' => 'Title'],
                    'location' => ['expr' => 'e.location', 'label' => 'Location'],
                    'deadline' => ['expr' => 'e.deadline', 'label' => 'Deadline'],
                    'posted_by_name' => ['expr' => 'u.name', 'label' => 'Posted By'],
                    'created_at' => ['expr' => 'e.created_at', 'label' => 'Created At'],
                    'applications_count' => ['expr' => 'COALESCE(a.applications_count,0)', 'label' => 'Applications']
                ],
                'defaults' => ['id', 'title', 'location', 'deadline', 'posted_by_name', 'applications_count', 'created_at']
            ];
        case 'users':
            return [
                'table' => 'users u',
                'date_column' => 'u.created_at',
                'joins' => [],
                'columns' => [
                    'id' => ['expr' => 'u.id', 'label' => 'ID'],
                    'name' => ['expr' => 'u.name', 'label' => 'Name'],
                    'email' => ['expr' => 'u.email', 'label' => 'Email'],
                    'role' => ['expr' => 'u.role', 'label' => 'Role'],
                    'status' => ['expr' => 'u.status', 'label' => 'Status'],
                    'phone' => ['expr' => 'u.phone', 'label' => 'Phone'],
                    'department' => ['expr' => 'u.department', 'label' => 'Department'],
                    'electoral_area' => ['expr' => 'u.electoral_area', 'label' => 'Electoral Area'],
                    'last_login' => ['expr' => 'u.last_login', 'label' => 'Last Login'],
                    'created_at' => ['expr' => 'u.created_at', 'label' => 'Created At']
                ],
                'defaults' => ['id', 'name', 'email', 'role', 'status', 'created_at']
            ];
        case 'constituents':
            return [
                'table' => 'constituents c',
                'date_column' => 'c.created_at',
                'joins' => [],
                'columns' => [
                    'id' => ['expr' => 'c.id', 'label' => 'ID'],
                    'name' => ['expr' => 'c.name', 'label' => 'Name'],
                    'phone' => ['expr' => 'c.phone', 'label' => 'Phone'],
                    'location' => ['expr' => 'c.location', 'label' => 'Location'],
                    'created_at' => ['expr' => 'c.created_at', 'label' => 'Created At']
                ],
                'defaults' => ['id', 'name', 'phone', 'location', 'created_at']
            ];
        case 'ideas':
            return [
                'table' => 'idea_bank ib',
                'date_column' => 'ib.created_at',
                'joins' => ['LEFT JOIN constituents c ON ib.submitted_by = c.id'],
                'columns' => [
                    'id' => ['expr' => 'ib.id', 'label' => 'ID'],
                    'title' => ['expr' => 'ib.title', 'label' => 'Title'],
                    'reviewed' => ['expr' => 'ib.reviewed', 'label' => 'Reviewed'],
                    'created_at' => ['expr' => 'ib.created_at', 'label' => 'Created At']
                ],
                'defaults' => ['id', 'title', 'reviewed', 'created_at']
            ];
        case 'activity':
            return [
                'table' => 'activity_logs al',
                'date_column' => 'al.created_at',
                'joins' => ['LEFT JOIN users u ON al.user_id = u.id'],
                'columns' => [
                    'id' => ['expr' => 'al.id', 'label' => 'ID'],
                    'user_name' => ['expr' => 'u.name', 'label' => 'User'],
                    'action' => ['expr' => 'al.action', 'label' => 'Action'],
                    'created_at' => ['expr' => 'al.created_at', 'label' => 'Created At']
                ],
                'defaults' => ['id', 'user_name', 'action', 'created_at']
            ];
        default:
            return [];
    }
}

function buildQuery(string $type, array $selectedCols, array $filters, ?array $dateRange): array
{
    $meta = getReportMeta($type);
    if (!$meta) throw new RuntimeException('Invalid report type');

    $colsMap = $meta['columns'];
    if (empty($selectedCols)) $selectedCols = $meta['defaults'];
    $selectedCols = array_values(array_filter($selectedCols, fn($k) => isset($colsMap[$k])));
    if (empty($selectedCols)) $selectedCols = $meta['defaults'];

    $selectParts = [];
    foreach ($selectedCols as $key) {
        $selectParts[] = $colsMap[$key]['expr'] . ' AS ' . $key;
    }

    $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM ' . $meta['table'];
    if (!empty($meta['joins'])) {
        $sql .= ' ' . implode(' ', $meta['joins']);
    }

    $where = [];
    $params = [];

    switch ($type) {
        case 'issues':
            if (!empty($filters['status'])) {
                $where[] = 'i.status = :status';
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['severity'])) {
                $where[] = 'i.severity = :severity';
                $params[':severity'] = $filters['severity'];
            }
            if (!empty($filters['type'])) {
                $where[] = 'i.type = :itype';
                $params[':itype'] = $filters['type'];
            }
            break;
        case 'projects':
            if (!empty($filters['project_status'])) {
                $where[] = 'p.status = :pstatus';
                $params[':pstatus'] = $filters['project_status'];
            }
            if (!empty($filters['project_type'])) {
                $where[] = 'p.type = :ptype';
                $params[':ptype'] = $filters['project_type'];
            }
            break;
        case 'users':
            if (!empty($filters['user_role'])) {
                $where[] = 'u.role = :urole';
                $params[':urole'] = $filters['user_role'];
            }
            if (!empty($filters['user_status'])) {
                $where[] = 'u.status = :ustatus';
                $params[':ustatus'] = $filters['user_status'];
            }
            break;
        case 'ideas':
            if (isset($filters['idea_reviewed']) && $filters['idea_reviewed'] !== '') {
                $where[] = 'ib.reviewed = :reviewed';
                $params[':reviewed'] = ($filters['idea_reviewed'] === 'true') ? 1 : 0;
            }
            break;
        default:
            break;
    }

    if ($dateRange) {
        [$start, $end] = $dateRange;
        $where[] = $meta['date_column'] . ' BETWEEN :start AND :end';
        $params[':start'] = $start;
        $params[':end'] = $end;
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY ' . $meta['date_column'] . ' DESC';

    $labels = [];
    foreach ($selectedCols as $key) {
        $labels[$key] = $colsMap[$key]['label'];
    }

    return [$sql, $params, $labels, $selectedCols];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $action = $_POST['action'] ?? 'preview';
    $reportType = $_POST['type'] ?? '';
    $columns = $_POST['columns'] ?? [];
    if (!is_array($columns)) $columns = [];

    $period = $_POST['period'] ?? 'all';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $dateRange = computeDateRange($period, $start_date, $end_date);

    $filters = [
        'status' => $_POST['status'] ?? null,
        'severity' => $_POST['severity'] ?? null,
        'type' => $_POST['issue_type'] ?? null, // avoid collision with report type
        'project_status' => $_POST['project_status'] ?? null,
        'project_type' => $_POST['project_type'] ?? null,
        'user_role' => $_POST['user_role'] ?? null,
        'user_status' => $_POST['user_status'] ?? null,
        'idea_reviewed' => $_POST['idea_reviewed'] ?? null,
    ];

    [$sql, $params, $labels, $selectedKeys] = buildQuery($reportType, $columns, $filters, $dateRange);

    $limit = 50;
    $previewSql = $sql . ' LIMIT ' . $limit;

    if ($action === 'preview') {
        $stmt = $conn->prepare($previewSql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columnsOut = array_values($labels);
        $rowsOut = [];
        foreach ($rows as $r) {
            $rowOut = [];
            foreach ($selectedKeys as $key) {
                $label = $labels[$key];
                $rowOut[$label] = $r[$key] ?? null;
            }
            $rowsOut[] = $rowOut;
        }
        jsonResponse(['columns' => $columnsOut, 'rows' => $rowsOut]);
    }

    if ($action === 'csv') {
        $stmt = $conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        $filename = $reportType . '_report_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, array_values($labels));
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $line = [];
            foreach ($selectedKeys as $key) {
                $line[] = $r[$key] ?? '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    jsonResponse(['error' => 'Unsupported action'], 400);
} catch (Throwable $e) {
    if (($_POST['action'] ?? 'preview') === 'csv') {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo 'Export failed: ' . $e->getMessage();
        exit;
    }
    jsonResponse(['error' => $e->getMessage()], 500);
}
