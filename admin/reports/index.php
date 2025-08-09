<?php
// filepath: c:\xampp\htdocs\swma\admin\reports\index.php
require_once __DIR__ . '/../../config/db_connection.php';
$database = new Database();
$conn = $database->getConnection();

require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../login/session_check.php';

$current_page = 'reports';
$adminId = $_SESSION['user_id'] ?? null;

// Sidebar counts
$pendingIssuesCount = getSystemPendingIssuesCount($conn);
$activeUsersCount = getActiveUsersCount($conn);

$headerActionButtons = [
    [
        'icon' => 'fas fa-chart-line',
        'label' => 'Analytics',
        'href' => '../analytics/'
    ],
    [
        'icon' => 'fas fa-arrows-rotate',
        'label' => 'Reset Form',
        'href' => '#',
        'onclick' => 'resetReportForm()'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reports - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>

<body class="bg-slate-50 min-h-screen font-inter">
    <?php renderAdminSidebar($current_page, $pendingIssuesCount, $activeUsersCount); ?>

    <main class="lg:ml-64 min-h-screen transition-all">
        <?php renderAdminHeader('Reports', 'Create, filter, and export system reports', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <!-- Builder -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Report Builder</h2>
                    <p class="text-sm text-gray-600">Choose data source, fields, filters, and time range. Preview before exporting.</p>
                </div>
                <form id="reportForm" class="p-5 space-y-6">
                    <!-- Report type and columns -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                            <select id="reportType" name="type" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="issues">Issues</option>
                                <option value="projects">Projects</option>
                                <option value="employment">Employment Opportunities</option>
                                <option value="users">Users</option>
                                <option value="constituents">Constituents</option>
                                <option value="ideas">Idea Bank</option>
                                <option value="activity">Activity Logs</option>
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Columns</label>
                            <div id="columnsContainer" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2"></div>
                            <div class="mt-2 flex items-center gap-2 text-sm">
                                <button type="button" class="text-primary hover:underline" onclick="selectAllColumns(true)">Select all</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" class="text-primary hover:underline" onclick="selectAllColumns(false)">Clear all</button>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="bg-slate-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Filters</h4>
                            <div id="filtersContainer" class="space-y-3"></div>
                        </div>

                        <!-- Date Range -->
                        <div class="lg:col-span-2 bg-slate-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Date Range</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Period</label>
                                    <select id="period" name="period" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <option value="all">All time</option>
                                        <option value="week">This week</option>
                                        <option value="month">This month</option>
                                        <option value="quarter">This quarter</option>
                                        <option value="half">This half-year</option>
                                        <option value="year">This year</option>
                                        <option value="custom">Custom range</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2 items-end" id="customDates" style="display:none;">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Start date</label>
                                        <input type="date" id="start_date" name="start_date" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">End date</label>
                                        <input type="date" id="end_date" name="end_date" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary" />
                                    </div>
                                </div>
                                <div class="md:col-span-2">
                                    <p class="text-xs text-gray-500">Date filters apply to the record's created_at field by default.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-3 justify-end">
                        <button type="button" id="btnPreview" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-eye"></i>
                            Preview
                        </button>
                        <button type="button" id="btnExportCsv" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                            <i class="fas fa-file-csv"></i>
                            Export CSV
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Preview</h2>
                        <p class="text-sm text-gray-600">First 50 rows for your current selection.</p>
                    </div>
                    <div id="previewMeta" class="text-xs text-gray-500"></div>
                </div>
                <div class="p-5 overflow-auto">
                    <div id="previewEmpty" class="text-sm text-gray-500">No data yet. Click Preview to load results.</div>
                    <table id="previewTable" class="min-w-full divide-y divide-gray-200 hidden">
                        <thead class="bg-gray-50" id="previewThead"></thead>
                        <tbody class="bg-white divide-y divide-gray-100" id="previewTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Column definitions per report type
        const COLUMN_DEFS = {
            issues: {
                id: 'ID',
                title: 'Title',
                status: 'Status',
                severity: 'Severity',
                type: 'Type',
                category: 'Category',
                sector: 'Sector',
                subsector: 'Subsector',
                agent_name: 'Agent',
                officer_name: 'Officer',
                people_affected: 'People Affected',
                budget_estimate: 'Budget Estimate',
                created_at: 'Created At',
                resolved_at: 'Resolved At',
                main_community: 'Main Community',
                smaller_community: 'Smaller Community',
                suburb: 'Suburb',
                cottage: 'Cottage'
            },
            projects: {
                id: 'ID',
                title: 'Title',
                status: 'Status',
                type: 'Type',
                budget: 'Budget',
                contractor_name: 'Contractor',
                sector: 'Sector',
                subsector: 'Subsector',
                started_at: 'Started At',
                completed_at: 'Completed At',
                created_at: 'Created At',
                is_public: 'Public?'
            },
            employment: {
                id: 'ID',
                title: 'Title',
                location: 'Location',
                deadline: 'Deadline',
                posted_by_name: 'Posted By',
                created_at: 'Created At',
                applications_count: 'Applications'
            },
            users: {
                id: 'ID',
                name: 'Name',
                email: 'Email',
                role: 'Role',
                status: 'Status',
                phone: 'Phone',
                department: 'Department',
                electoral_area: 'Electoral Area',
                last_login: 'Last Login',
                created_at: 'Created At'
            },
            constituents: {
                id: 'ID',
                name: 'Name',
                phone: 'Phone',
                location: 'Location',
                created_at: 'Created At'
            },
            ideas: {
                id: 'ID',
                title: 'Title',
                reviewed: 'Reviewed',
                created_at: 'Created At'
            },
            activity: {
                id: 'ID',
                user_name: 'User',
                action: 'Action',
                created_at: 'Created At'
            }
        };

        // Filter controls per type
        const FILTER_DEFS = {
            issues: [{
                    key: 'status',
                    label: 'Status',
                    type: 'select',
                    options: ['pending', 'reviewed', 'approved', 'rejected', 'resolved']
                },
                {
                    key: 'severity',
                    label: 'Severity',
                    type: 'select',
                    options: ['low', 'medium', 'high']
                },
                {
                    key: 'issue_type',
                    label: 'Type',
                    type: 'select',
                    options: ['personal', 'community']
                }
            ],
            projects: [{
                    key: 'project_status',
                    label: 'Project Status',
                    type: 'select',
                    options: ['planned', 'ongoing', 'completed', 'abandoned']
                },
                {
                    key: 'project_type',
                    label: 'Project Type',
                    type: 'select',
                    options: ['infrastructure', 'service', 'initiative']
                }
            ],
            employment: [],
            users: [{
                    key: 'user_role',
                    label: 'Role',
                    type: 'select',
                    options: ['mp', 'mce', 'pa', 'officer', 'agent', 'admin']
                },
                {
                    key: 'user_status',
                    label: 'Status',
                    type: 'select',
                    options: ['active', 'inactive']
                }
            ],
            constituents: [],
            ideas: [{
                key: 'idea_reviewed',
                label: 'Reviewed',
                type: 'select',
                options: ['true', 'false']
            }],
            activity: []
        };

        const formEl = document.getElementById('reportForm');
        const typeEl = document.getElementById('reportType');
        const columnsEl = document.getElementById('columnsContainer');
        const filtersEl = document.getElementById('filtersContainer');
        const periodEl = document.getElementById('period');
        const customDatesEl = document.getElementById('customDates');

        function renderColumns() {
            const type = typeEl.value;
            const defs = COLUMN_DEFS[type];
            columnsEl.innerHTML = '';
            Object.entries(defs).forEach(([key, label]) => {
                const id = `col_${key}`;
                const div = document.createElement('div');
                div.className = 'flex items-center gap-2';
                div.innerHTML = `
					<input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary" id="${id}" name="columns[]" value="${key}" ${['id','title','name','status','created_at'].includes(key) ? 'checked' : ''}>
					<label for="${id}" class="text-sm text-gray-700">${label}</label>
				`;
                columnsEl.appendChild(div);
            });
        }

        function renderFilters() {
            const type = typeEl.value;
            const defs = FILTER_DEFS[type] || [];
            filtersEl.innerHTML = '';
            defs.forEach(def => {
                const id = `filter_${def.key}`;
                if (def.type === 'select') {
                    const options = ['<option value="">Any</option>'].concat(def.options.map(o => `<option value="${o}">${o.charAt(0).toUpperCase() + o.slice(1)}</option>`));
                    const wrap = document.createElement('div');
                    wrap.innerHTML = `
						<label class="block text-xs text-gray-600 mb-1" for="${id}">${def.label}</label>
						<select id="${id}" name="${def.key}" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">${options.join('')}</select>
					`;
                    filtersEl.appendChild(wrap);
                }
            });
        }

        function selectAllColumns(checked) {
            columnsEl.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = checked);
        }

        periodEl.addEventListener('change', () => {
            customDatesEl.style.display = periodEl.value === 'custom' ? '' : 'none';
        });

        typeEl.addEventListener('change', () => {
            renderColumns();
            renderFilters();
        });

        function serializeForm(form) {
            const data = new FormData(form);
            return new URLSearchParams([...data.entries()]);
        }

        async function doPreview() {
            const params = serializeForm(formEl);
            params.append('action', 'preview');
            const res = await fetch('export.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            });
            if (!res.ok) throw new Error('Failed to preview');
            const json = await res.json();
            renderPreview(json);
        }

        function downloadCsv() {
            const params = serializeForm(formEl);
            params.append('action', 'csv');
            const url = 'export.php';
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.style.display = 'none';
            for (const [k, v] of params.entries()) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = k;
                input.value = v;
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
            setTimeout(() => form.remove(), 1000);
        }

        const previewEmptyEl = document.getElementById('previewEmpty');
        const previewTableEl = document.getElementById('previewTable');
        const theadEl = document.getElementById('previewThead');
        const tbodyEl = document.getElementById('previewTbody');
        const previewMetaEl = document.getElementById('previewMeta');

        function renderPreview(data) {
            if (!data || !data.columns || !data.rows) {
                previewEmptyEl.classList.remove('hidden');
                previewTableEl.classList.add('hidden');
                return;
            }
            previewEmptyEl.classList.add('hidden');
            previewTableEl.classList.remove('hidden');
            theadEl.innerHTML = `<tr>${data.columns.map(c => `<th class='px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>${c}</th>`).join('')}</tr>`;
            tbodyEl.innerHTML = data.rows.map(row => `
				<tr>
					${data.columns.map(c => `<td class='px-3 py-2 text-sm text-gray-800'>${(row[c] ?? '')}</td>`).join('')}
				</tr>
			`).join('');
            previewMetaEl.textContent = `${data.rows.length} rows`;
        }

        function resetReportForm() {
            formEl.reset();
            customDatesEl.style.display = 'none';
            renderColumns();
            renderFilters();
            previewEmptyEl.classList.remove('hidden');
            previewTableEl.classList.add('hidden');
            theadEl.innerHTML = '';
            tbodyEl.innerHTML = '';
            previewMetaEl.textContent = '';
        }

        document.getElementById('btnPreview').addEventListener('click', () => doPreview().catch(err => alert(err.message)));
        document.getElementById('btnExportCsv').addEventListener('click', () => downloadCsv());

        // init
        renderColumns();
        renderFilters();
    </script>
</body>

</html>