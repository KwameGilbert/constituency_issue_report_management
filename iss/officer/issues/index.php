<?php
// issues.php - Agent Issues Management Page
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
$officerId = $_SESSION['user_id'] ?? null;

$current_page = 'issues';

require_once __DIR__ . '/getComprehensiveIssuesForTable.php';

// Define action buttons for the header
$headerActionButtons = [
    [
        'icon' => 'fas fa-plus',
        'label' => 'New Issue',
        'href' => 'add_issue.php'
    ]
];

// Get current user data for display
$userName = $_SESSION['user_name'] ?? 'Officer';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Issues - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        success: '#10b981',
                        warning: '#f59e0b',
                        error: '#ef4444',
                        info: '#3b82f6', // Added info color for general purpose
                        slate: {
                            50: '#f8fafc',
                            100: '#f1f5f9', // Added for subtle backgrounds
                            200: '#e2e8f0', // Added for borders/dividers
                            900: '#0f172a',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom styles for status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            /* px-2.5 py-0.5 */
            border-radius: 9999px;
            /* rounded-full */
            font-size: 0.75rem;
            /* text-xs */
            font-weight: 500;
            /* font-medium */
            text-transform: capitalize;
        }

        /* Specific status colors */
        .status-badge-pending {
            background-color: #fef3c7;
            /* yellow-100 */
            color: #b45309;
            /* yellow-700 */
        }

        .status-badge-in_progress {
            background-color: #bfdbfe;
            /* blue-200 */
            color: #1e40af;
            /* blue-800 */
        }

        .status-badge-resolved {
            background-color: #d1fae5;
            /* green-100 */
            color: #065f46;
            /* green-700 */
        }

        .status-badge-rejected {
            background-color: #fee2e2;
            /* red-100 */
            color: #991b1b;
            /* red-700 */
        }

        .status-badge-closed {
            background-color: #dbeafe;
            /* blue-100 for closed */
            color: #1e40af;
            /* blue-700 for closed */
        }

        .status-badge-escalated {
            background-color: #fecaca;
            /* red-200 */
            color: #b91c1c;
            /* red-800 */
        }

        /* Default status color for any undefined status */
        .status-badge-default {
            background-color: #e0e7ff;
            /* indigo-100 */
            color: #4338ca;
            /* indigo-700 */
        }

        /* Added for better focus styles */
        input:focus,
        select:focus {
            border-color: #6366f1;
            /* primary color */
            outline: none;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen font-sans">
    <?php renderOfficerSidebar($current_page); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderOfficerHeader('Issues', 'Manage and track constituent issues', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">

            <!-- Filter Section -->
            <div id="filterSection" class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center justify-between">
                    <span>Filter Issues</span>
                    <button id="toggleFiltersBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary rounded-full p-1 transition-transform duration-200">
                        <i class="fas fa-chevron-up" id="toggleIcon"></i>
                    </button>
                </h2>
                <div id="filterInputsContainer" class="transition-all duration-300 ease-in-out overflow-hidden max-h-screen">
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <!-- Search Input (full width) -->
                        <div>
                            <label for="searchInput" class="block text-xs font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" id="searchInput" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary" placeholder="Search by title, description, or location...">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                        <?php
                        $filters = [
                            'Category' => $categories,
                            'Status' => $statuses,
                            'Type' => $types,
                            'Sector' => $sectors,
                            'Subsector' => $subsectors,
                            'Main Community' => $electoralAreas, // kept as source of main community names
                            'Community' => $communities,
                            'Severity' => $severities,
                            'Agent' => $agents,
                        ];
                        $idMap = [
                            'Category' => 'categoryFilter',
                            'Status' => 'statusFilter',
                            'Type' => 'typeFilter',
                            'Sector' => 'sectorFilter',
                            'Subsector' => 'subsectorFilter',
                            'Main Community' => 'mainCommunityFilter',
                            'Community' => 'communityFilter',
                            'Severity' => 'severityFilter',
                            'Agent' => 'agentFilter'
                        ];

                        foreach ($filters as $label => $options) {
                            echo '<div>';
                            echo '<label for="' . $idMap[$label] . '" class="block text-xs font-medium text-gray-700 mb-2">' . $label . '</label>';
                            echo '<select id="' . $idMap[$label] . '" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary">';
                            echo '<option value="">All ' . $label . 's</option>';
                            foreach ($options as $opt) {
                                echo '<option value="' . htmlspecialchars($opt) . '">' . htmlspecialchars($opt) . '</option>';
                            }
                            echo '</select>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button id="resetFiltersBtn" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors duration-200">
                            <i class="fas fa-undo mr-2"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Issues Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title & Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Submitted</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="issuesTable">
                            <?php if (empty($issues)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No issues found. Adjust your filters or add a new issue.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($issues as $issue):
                                    // Determine status badge class
                                    $statusClass = 'status-badge-default'; // Default for undefined
                                    switch (strtolower($issue['status'])) {
                                        case 'pending':
                                            $statusClass = 'status-badge-pending';
                                            break;
                                        case 'in_progress':
                                            $statusClass = 'status-badge-in_progress';
                                            break;
                                        case 'resolved':
                                            $statusClass = 'status-badge-resolved';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'status-badge-rejected';
                                            break;
                                        case 'closed':
                                            $statusClass = 'status-badge-closed';
                                            break;
                                        case 'escalated':
                                            $statusClass = 'status-badge-escalated';
                                            break;
                                        default:
                                            $statusClass = 'status-badge-default'; // Fallback
                                            break;
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                        data-title="<?= strtolower($issue['title'] ?? '') ?>"
                                        data-location="<?= strtolower($issue['location_description'] ?? $issue['location'] ?? '') ?>"
                                        data-category="<?= strtolower($issue['category'] ?? '') ?>"
                                        data-status="<?= strtolower($issue['status'] ?? '') ?>"
                                        data-type="<?= strtolower($issue['type'] ?? '') ?>"
                                        data-sector="<?= strtolower($issue['sector'] ?? '') ?>"
                                        data-subsector="<?= strtolower($issue['subsector'] ?? '') ?>"
                                        data-electoral-area="<?= strtolower($issue['electoral_area'] ?? '') ?>"
                                        data-main-community="<?= strtolower($issue['main_community'] ?? $issue['electoral_area'] ?? '') ?>"
                                        data-community="<?= strtolower($issue['community'] ?? '') ?>"
                                        data-smaller-community="<?= strtolower($issue['smaller_community'] ?? '') ?>"
                                        data-severity="<?= strtolower($issue['severity'] ?? '') ?>"
                                        data-agent="<?= strtolower($issue['agent'] ?? '') ?>">
                                        <td class="px-6 py-4 text-sm text-gray-500"><?= $issue['id'] ?></td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($issue['title']) ?></div>
                                            <div class="text-xs text-gray-500 truncate w-64"><?= htmlspecialchars($issue['description']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($issue['category']) ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= htmlspecialchars(ucwords(str_replace("_", " ", $issue['status']))) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars(date('M d, Y', strtotime($issue['submitted_at']))) ?></td>
                                        <td class="px-6 py-4 text-center text-sm font-medium">
                                            <a href="view_issue.php?id=<?= $issue['id'] ?>" class="text-primary hover:text-primary-dark mr-3">View</a>
                                            <a href="edit_issue.php?id=<?= $issue['id'] ?>" class="text-secondary hover:text-secondary-dark">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="noIssuesMessage" class="hidden px-6 py-10 text-center text-sm text-gray-500">
                    No issues found matching your filters.
                </div>
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
                sector: document.getElementById('sectorFilter'),
                subsector: document.getElementById('subsectorFilter'),
                mainCommunity: document.getElementById('mainCommunityFilter'),
                community: document.getElementById('communityFilter'),
                severity: document.getElementById('severityFilter'),
                agent: document.getElementById('agentFilter')
            };

            const tableBody = document.getElementById('issuesTable');
            const tableRows = Array.from(tableBody.querySelectorAll('tr')); // Convert NodeList to Array
            const noIssuesMessage = document.getElementById('noIssuesMessage');

            // Filter section toggle
            const toggleFiltersBtn = document.getElementById('toggleFiltersBtn');
            const toggleIcon = document.getElementById('toggleIcon');
            const filterInputsContainer = document.getElementById('filterInputsContainer');

            let isFiltersExpanded = true; // Initial state

            toggleFiltersBtn.addEventListener('click', () => {
                isFiltersExpanded = !isFiltersExpanded;
                if (isFiltersExpanded) {
                    filterInputsContainer.style.maxHeight = filterInputsContainer.scrollHeight + 'px'; // Expand to full height
                    toggleIcon.classList.remove('fa-chevron-down');
                    toggleIcon.classList.add('fa-chevron-up');
                } else {
                    filterInputsContainer.style.maxHeight = '0'; // Collapse
                    toggleIcon.classList.remove('fa-chevron-up');
                    toggleIcon.classList.add('fa-chevron-down');
                }
            });

            // Ensure filters are expanded initially and height is set correctly
            filterInputsContainer.style.maxHeight = filterInputsContainer.scrollHeight + 'px'; // Set initial max-height

            function normalize(text) {
                return (text || '').toLowerCase().trim();
            }

            function filterRows() {
                const values = Object.fromEntries(Object.entries(filters).map(([key, el]) => [key, normalize(el ? el.value : '')]));
                let visibleRowCount = 0;

                tableRows.forEach(row => {
                    const matches =
                        (!values.search ||
                            row.dataset.title.includes(values.search) ||
                            (row.dataset.location || '').includes(values.search) ||
                            (row.querySelector('.text-xs.text-gray-500') && normalize(row.querySelector('.text-xs.text-gray-500').textContent).includes(values.search))
                        ) &&
                        (!values.category || row.dataset.category === values.category) &&
                        (!values.status || row.dataset.status === values.status) &&
                        (!values.type || row.dataset.type === values.type) &&
                        (!values.sector || row.dataset.sector === values.sector) &&
                        (!values.subsector || row.dataset.subsector === values.subsector) &&
                        (!values.mainCommunity || (row.dataset.mainCommunity || '') === values.mainCommunity) &&
                        (!values.community || ((row.dataset.smallerCommunity || row.dataset.community || '') === values.community)) &&
                        (!values.severity || row.dataset.severity === values.severity) &&
                        (!values.agent || row.dataset.agent === values.agent);

                    row.style.display = matches ? '' : 'none';
                    if (matches) {
                        visibleRowCount++;
                    }
                });

                // Show/hide "No issues found" message
                if (visibleRowCount === 0) {
                    noIssuesMessage.classList.remove('hidden');
                    tableBody.classList.add('hidden'); // Hide the actual table body
                } else {
                    noIssuesMessage.classList.add('hidden');
                    tableBody.classList.remove('hidden'); // Show the actual table body
                }
            }

            // Attach event listeners to all filter inputs
            Object.values(filters).forEach(input => {
                if (input) { // Check if element exists before adding listener
                    input.addEventListener('input', filterRows);
                    input.addEventListener('change', filterRows);
                }
            });

            // Reset filters button
            document.getElementById('resetFiltersBtn')?.addEventListener('click', () => {
                Object.values(filters).forEach(input => {
                    if (input) {
                        input.value = '';
                    }
                });
                filterRows(); // Apply filters after resetting
            });

            // Initial filter application in case of pre-filled values (though not expected here)
            filterRows();
        });
    </script>
</body>

</html>