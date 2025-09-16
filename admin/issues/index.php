<?php
// filepath: c:\xampp\htdocs\swma\admin\issues\index.php
require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../login/session_check.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'issues';

// Sidebar counts
$pendingIssuesCount = getSystemPendingIssuesCount($conn);
$activeUsersCount = getActiveUsersCount($conn);

// Header actions
$headerActionButtons = [
    ['icon' => 'fas fa-chart-line', 'label' => 'Analytics', 'href' => '../analytics/'],
    ['icon' => 'fas fa-file-export', 'label' => 'Reports', 'href' => '../reports/'],
];

// Fetch comprehensive issues data aligned with schema.sql (new location scheme)
$issuesStmt = $conn->prepare("
    SELECT
        i.id,
        i.title,
        i.description,
        i.type,
        i.severity,
        i.status,
        DATE(i.created_at) AS submitted_at,
        ic.name AS category,
        sec.name AS sector,
        ssec.name AS subsector,
        c.name AS main_community,
        sc.name AS smaller_community,
        sub.name AS suburb,
        cot.name AS cottage,
        ua.name AS agent,
        uo.name AS officer
    FROM issues i
    LEFT JOIN issue_categories ic ON i.category_id = ic.id
    LEFT JOIN issue_sectors sec ON i.sector_id = sec.id
    LEFT JOIN issue_subsectors ssec ON i.subsector_id = ssec.id
    LEFT JOIN communities c ON i.main_community_id = c.id
    LEFT JOIN smaller_communities sc ON i.smaller_community_id = sc.id
    LEFT JOIN suburbs sub ON i.suburb_id = sub.id
    LEFT JOIN cottages cot ON i.cottage_id = cot.id
    LEFT JOIN users ua ON i.agent_id = ua.id
    LEFT JOIN users uo ON i.officer_id = uo.id
    ORDER BY i.created_at DESC
");
$issuesStmt->execute();
$issues = $issuesStmt->fetchAll(PDO::FETCH_ASSOC);

function extractAndSortUniqueAdmin($array, $key)
{
    $vals = array_filter(array_map(function ($row) use ($key) {
        return $row[$key] ?? null;
    }, $array), fn($v) => $v !== null && $v !== '');
    $vals = array_unique($vals);
    $vals = array_values($vals);
    sort($vals);
    return $vals;
}

$categories = extractAndSortUniqueAdmin($issues, 'category');
$statuses = extractAndSortUniqueAdmin($issues, 'status');
$types = extractAndSortUniqueAdmin($issues, 'type');
$severities = extractAndSortUniqueAdmin($issues, 'severity');
$sectors = extractAndSortUniqueAdmin($issues, 'sector');
$subsectors = extractAndSortUniqueAdmin($issues, 'subsector');
$mainCommunities = extractAndSortUniqueAdmin($issues, 'main_community');
$smallerCommunities = extractAndSortUniqueAdmin($issues, 'smaller_community');
$suburbs = extractAndSortUniqueAdmin($issues, 'suburb');
$cottages = extractAndSortUniqueAdmin($issues, 'cottage');
$agents = extractAndSortUniqueAdmin($issues, 'agent');
$officers = extractAndSortUniqueAdmin($issues, 'officer');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Issues - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif']
                    },
                    colors: {
                        primary: '#dc2626',
                        secondary: '#64748b'
                    }
                }
            }
        }
    </script>
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .status-badge-reviewed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge-in_progress {
            background: #ede9fe;
            color: #6d28d9;
        }

        .status-badge-resolved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge-default {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen font-inter">
    <?php renderAdminSidebar($current_page, $pendingIssuesCount, $activeUsersCount); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAdminHeader('All Issues', 'System-wide issues overview and management', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center justify-between">
                    <span>Filter Issues</span>
                    <button id="toggleFiltersBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none rounded-full p-1 transition-transform"><i class="fas fa-chevron-up" id="toggleIcon"></i></button>
                </h2>
                <div id="filterInputsContainer" class="transition-all duration-300 ease-in-out overflow-hidden max-h-screen">
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-2">Search</label>
                            <input id="searchInput" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary" placeholder="Search by title or description..." />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <?php
                        // Helper to render a select
                        function renderSelect($id, $label, $options)
                        {
                            echo '<div>';
                            echo '<label for="' . $id . '" class="block text-xs font-medium text-gray-700 mb-2">' . $label . '</label>';
                            echo '<select id="' . $id . '" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary">';
                            echo '<option value="">All ' . htmlspecialchars($label) . '</option>';
                            foreach ($options as $opt) {
                                echo '<option value="' . htmlspecialchars(strtolower($opt)) . '">' . htmlspecialchars($opt) . '</option>';
                            }
                            echo '</select>';
                            echo '</div>';
                        }

                        renderSelect('categoryFilter', 'Category', $categories);
                        renderSelect('statusFilter', 'Status', $statuses);
                        renderSelect('typeFilter', 'Type', $types);
                        renderSelect('severityFilter', 'Severity', $severities);
                        renderSelect('sectorFilter', 'Sector', $sectors);
                        renderSelect('subsectorFilter', 'Subsector', $subsectors);
                        renderSelect('mainCommunityFilter', 'Main Community', $mainCommunities);
                        renderSelect('smallerCommunityFilter', 'Smaller Community', $smallerCommunities);
                        renderSelect('suburbFilter', 'Suburb', $suburbs);
                        renderSelect('cottageFilter', 'Cottage', $cottages);
                        renderSelect('agentFilter', 'Agent', $agents);
                        renderSelect('officerFilter', 'Officer', $officers);
                        ?>
                    </div>
                    <div class="flex justify-end">
                        <button id="resetFiltersBtn" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-undo mr-2"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title & Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="issuesTable">
                            <?php if (empty($issues)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No issues found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($issues as $issue):
                                    $statusKey = strtolower($issue['status'] ?? '');
                                    $statusClass = 'status-badge-default';
                                    if (in_array($statusKey, ['pending', 'reviewed', 'approved', 'rejected', 'resolved', 'in_progress'])) {
                                        $statusClass = 'status-badge-' . $statusKey;
                                    }
                                    $locationParts = array_filter([
                                        $issue['main_community'] ?? null,
                                        $issue['smaller_community'] ?? null,
                                        $issue['suburb'] ?? null,
                                        $issue['cottage'] ?? null,
                                    ]);
                                    $locationDisplay = !empty($locationParts) ? implode(' • ', $locationParts) : '—';
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors"
                                        data-title="<?= htmlspecialchars(strtolower($issue['title'] ?? '')) ?>"
                                        data-description="<?= htmlspecialchars(strtolower($issue['description'] ?? '')) ?>"
                                        data-category="<?= htmlspecialchars(strtolower($issue['category'] ?? '')) ?>"
                                        data-status="<?= htmlspecialchars(strtolower($issue['status'] ?? '')) ?>"
                                        data-type="<?= htmlspecialchars(strtolower($issue['type'] ?? '')) ?>"
                                        data-severity="<?= htmlspecialchars(strtolower($issue['severity'] ?? '')) ?>"
                                        data-sector="<?= htmlspecialchars(strtolower($issue['sector'] ?? '')) ?>"
                                        data-subsector="<?= htmlspecialchars(strtolower($issue['subsector'] ?? '')) ?>"
                                        data-main-community="<?= htmlspecialchars(strtolower($issue['main_community'] ?? '')) ?>"
                                        data-smaller-community="<?= htmlspecialchars(strtolower($issue['smaller_community'] ?? '')) ?>"
                                        data-suburb="<?= htmlspecialchars(strtolower($issue['suburb'] ?? '')) ?>"
                                        data-cottage="<?= htmlspecialchars(strtolower($issue['cottage'] ?? '')) ?>"
                                        data-agent="<?= htmlspecialchars(strtolower($issue['agent'] ?? '')) ?>"
                                        data-officer="<?= htmlspecialchars(strtolower($issue['officer'] ?? '')) ?>">
                                        <td class="px-6 py-4 text-sm text-gray-500"><?= (int)$issue['id'] ?></td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-semibold text-gray-900 truncate max-w-xs"><?= htmlspecialchars($issue['title'] ?? '') ?></div>
                                            <div class="text-xs text-gray-500 truncate max-w-xl"><?= htmlspecialchars($issue['description'] ?? '') ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($issue['category'] ?? '—') ?></td>
                                        <td class="px-6 py-4 text-sm"><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars(str_replace('_', ' ', $issue['status'] ?? '')) ?></span></td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($locationDisplay) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars(date('M d, Y', strtotime($issue['submitted_at']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="noIssuesMessage" class="hidden px-6 py-10 text-center text-sm text-gray-500">No issues found matching your filters.</div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filters = {
                search: document.getElementById('searchInput'),
                category: document.getElementById('categoryFilter'),
                status: document.getElementById('statusFilter'),
                type: document.getElementById('typeFilter'),
                severity: document.getElementById('severityFilter'),
                sector: document.getElementById('sectorFilter'),
                subsector: document.getElementById('subsectorFilter'),
                mainCommunity: document.getElementById('mainCommunityFilter'),
                smallerCommunity: document.getElementById('smallerCommunityFilter'),
                suburb: document.getElementById('suburbFilter'),
                cottage: document.getElementById('cottageFilter'),
                agent: document.getElementById('agentFilter'),
                officer: document.getElementById('officerFilter')
            };

            const tableBody = document.getElementById('issuesTable');
            const tableRows = Array.from(tableBody.querySelectorAll('tr'));
            const noIssuesMessage = document.getElementById('noIssuesMessage');

            const toggleFiltersBtn = document.getElementById('toggleFiltersBtn');
            const toggleIcon = document.getElementById('toggleIcon');
            const filterInputsContainer = document.getElementById('filterInputsContainer');
            let expanded = true;
            toggleFiltersBtn.addEventListener('click', () => {
                expanded = !expanded;
                if (expanded) {
                    filterInputsContainer.style.maxHeight = filterInputsContainer.scrollHeight + 'px';
                    toggleIcon.classList.remove('fa-chevron-down');
                    toggleIcon.classList.add('fa-chevron-up');
                } else {
                    filterInputsContainer.style.maxHeight = '0';
                    toggleIcon.classList.remove('fa-chevron-up');
                    toggleIcon.classList.add('fa-chevron-down');
                }
            });
            filterInputsContainer.style.maxHeight = filterInputsContainer.scrollHeight + 'px';

            function norm(v) {
                return (v || '').toLowerCase().trim();
            }

            function filterRows() {
                const values = {
                    search: norm(filters.search.value),
                    category: norm(filters.category.value),
                    status: norm(filters.status.value),
                    type: norm(filters.type.value),
                    severity: norm(filters.severity.value),
                    sector: norm(filters.sector.value),
                    subsector: norm(filters.subsector.value),
                    mainCommunity: norm(filters.mainCommunity.value),
                    smallerCommunity: norm(filters.smallerCommunity.value),
                    suburb: norm(filters.suburb.value),
                    cottage: norm(filters.cottage.value),
                    agent: norm(filters.agent.value),
                    officer: norm(filters.officer.value)
                };
                let visible = 0;
                tableRows.forEach(row => {
                    const matches = (
                        (!values.search || row.dataset.title.includes(values.search) || row.dataset.description.includes(values.search)) &&
                        (!values.category || row.dataset.category === values.category) &&
                        (!values.status || row.dataset.status === values.status) &&
                        (!values.type || row.dataset.type === values.type) &&
                        (!values.severity || row.dataset.severity === values.severity) &&
                        (!values.sector || row.dataset.sector === values.sector) &&
                        (!values.subsector || row.dataset.subsector === values.subsector) &&
                        (!values.mainCommunity || row.dataset.mainCommunity === undefined ? row.dataset['main-community'] === values.mainCommunity : row.dataset.mainCommunity === values.mainCommunity) &&
                        (!values.smallerCommunity || row.dataset.smallerCommunity === undefined ? row.dataset['smaller-community'] === values.smallerCommunity : row.dataset.smallerCommunity === values.smallerCommunity) &&
                        (!values.suburb || row.dataset.suburb === values.suburb) &&
                        (!values.cottage || row.dataset.cottage === values.cottage) &&
                        (!values.agent || row.dataset.agent === values.agent) &&
                        (!values.officer || row.dataset.officer === values.officer)
                    );
                    row.style.display = matches ? '' : 'none';
                    if (matches) visible++;
                });
                if (visible === 0) {
                    noIssuesMessage.classList.remove('hidden');
                    tableBody.classList.add('hidden');
                } else {
                    noIssuesMessage.classList.add('hidden');
                    tableBody.classList.remove('hidden');
                }
            }

            Object.values(filters).forEach(el => {
                if (el) {
                    el.addEventListener('input', filterRows);
                    el.addEventListener('change', filterRows);
                }
            });

            document.getElementById('resetFiltersBtn')?.addEventListener('click', () => {
                Object.values(filters).forEach(el => {
                    if (el) el.value = '';
                });
                filterRows();
            });
        });
    </script>
</body>

</html>