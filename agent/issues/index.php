<?php
// issues.php - Agent Issues Management Page
include __DIR__ . '/../components/sidebar.php';
$current_page = 'issues';

// Dummy data for demonstration
$issues = [

      [
    
        'id' => 1,
    
        'title' => 'Potholes on High Street',
    
        'category' => 'Roads',
    
        'status' => 'pending',
    
        'location' => 'Central Business District',
    
        'submitted_at' => '2025-06-15',
    
        'description' => 'Large potholes making driving difficult and dangerous on High Street near the market.'
    
      ],
    
      [

        'id' => 2,
    
        'title' => 'Water Shortage in North Hills',
    
        'category' => 'Water',
    
        'status' => 'approved',
    
        'location' => 'North Hills Residential',
    
        'submitted_at' => '2025-06-10',
    
        'description' => 'Intermittent water supply for the past week, affecting daily chores.'
    
      ],
    
      [
    
        'id' => 3,
    
        'title' => 'Broken Streetlight at Park Entrance',
    
        'category' => 'Electricity',
    
        'status' => 'resolved',
    
        'location' => 'Community Park',
    
        'submitted_at' => '2025-06-01',
    
        'description' => 'Streetlight at the main entrance of Community Park has been out for several nights.'
    
      ],
    
      [
    
        'id' => 4,
    
        'title' => 'Illegal Dumping in Riverfront Area',
    
        'category' => 'Environment',
    
        'status' => 'pending',
    
        'location' => 'Riverfront Pathway',
    
        'submitted_at' => '2025-06-18',
    
        'description' => 'Residents are illegally dumping waste near the river, causing pollution and foul smell.'
    
      ],
    
      [
    
        'id' => 5,
    
        'title' => 'Noise Complaint from Construction Site',
    
        'category' => 'Construction',
    
        'status' => 'reviewed',
    
        'location' => 'New Development Zone',
    
        'submitted_at' => '2025-06-14',
    
        'description' => 'Construction noise continues late into the night, disturbing residents in the vicinity.'
    
      ],
    
      [
    
        'id' => 6,
    
        'title' => 'Lack of Public Bins in Market',
    
        'category' => 'Sanitation',
    
        'status' => 'rejected',
    
        'location' => 'Main Market Square',
    
        'submitted_at' => '2025-06-12',
    
        'description' => 'Insufficient public waste bins leading to litter accumulation in the market area.'
    
      ],
    
      [
    
        'id' => 7,
    
        'title' => 'Damaged Pavement on Elm Street',
    
        'category' => 'Roads',
    
        'status' => 'approved',
    
        'location' => 'Elm Street, Residential',
    
        'submitted_at' => '2025-06-05',
    
        'description' => 'Cracked and uneven pavement causing trip hazards, especially for elderly residents.'
    
      ],
    
      [
    
        'id' => 8,
    
        'title' => 'Blocked Drainage System',
    
        'category' => 'Sanitation',
    
        'status' => 'resolved',
    
        'location' => 'Maple Avenue',
    
        'submitted_at' => '2025-05-28',
    
        'description' => 'Drainage system blocked by debris, leading to minor flooding during rain.'
    
      ],
    
      [
    
        'id' => 9,
    
        'title' => 'Request for Community Library',
    
        'category' => 'Education',
    
        'status' => 'pending',
    
        'location' => 'Town Center',
    
        'submitted_at' => '2025-06-17',
    
        'description' => 'Proposal for establishing a small community library to promote reading among youth.'
    
      ],
    
      [
    
        'id' => 10,
    
        'title' => 'Vandalism in Public Park',
    
        'category' => 'Security',
    
        'status' => 'reviewed',
    
        'location' => 'Greenwood Park',
    
        'submitted_at' => '2025-06-16',
    
        'description' => 'Graffiti and damage to park benches reported.'
    
      ]
    
    ];

// Extract unique categories and statuses
$categories = array_unique(array_column($issues, 'category'));
sort($categories);
$statuses = array_unique(array_column($issues, 'status'));
sort($statuses);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Issues - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

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
                        dark: '#1f2937',
                        light: '#f8fafc'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
    </script>
</head>

<body class="bg-gray-50 min-h-screen font-sans bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <?php renderAgentSidebar($current_page); ?>

    <main class="lg:ml-60 px-3 pb-6 transition-all duration-300">
        <!-- Header -->
        <div class="p-3 flex justify-between items-center border-b border-gray-100 mb-4">
            <div>
                <h3 class="text-gray-800 font-medium text-sm">Issues</h3>
                <p class="text-gray-600 text-xs">View and manage all issues submitted by you.</p>
            </div>
            <a href="add_issue.php" class="px-4 py-1.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2 text-sm">
                <i class="fas fa-plus"></i>
                Add New Issue
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6 flex flex-col md:flex-row gap-4">
            <div class="relative w-full md:w-1/2">
                <input type="text" id="searchInput" placeholder="Search issues..." class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" />
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <select id="categoryFilter" class="w-full md:w-1/4 py-2 px-3 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="statusFilter" class="w-full md:w-1/4 py-2 px-3 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $stat): ?>
                    <option value="<?= htmlspecialchars($stat) ?>"><?= htmlspecialchars($stat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 text-xs text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3 whitespace-nowrap">Submitted On</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="issuesTableBody" class="divide-y divide-gray-100 bg-white">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const issues = <?= json_encode($issues) ?>;
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const statusFilter = document.getElementById('statusFilter');
            const tbody = document.getElementById('issuesTableBody');

            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }

            function renderTable(data) {
                tbody.innerHTML = data.length ? '' : '<tr><td colspan="7" class="py-4 text-center text-gray-500">No issues found.</td></tr>';
                data.forEach(issue => {
                    tbody.innerHTML += `
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="py-3 px-4 text-gray-700">${issue.id}</td>
                            <td class="py-3 px-4 text-gray-700">${escapeHtml(issue.title)}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    ${issue.status === 'approved' ? 'bg-green-100 text-green-800' : ''}
                                    ${issue.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ''}
                                    ${issue.status === 'resolved' ? 'bg-blue-100 text-blue-800' : ''}
                                    ${issue.status === 'rejected' ? 'bg-red-100 text-red-800' : ''}
                                    ${issue.status === 'reviewed' ? 'bg-purple-100 text-purple-800' : ''}
                                ">
                                    ${issue.status.charAt(0).toUpperCase() + issue.status.slice(1)}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-gray-700">${escapeHtml(issue.category)}</td>
                            <td class="py-3 px-4 text-gray-700">${escapeHtml(issue.location)}</td>
                            <td class="py-3 px-4 text-gray-700">${escapeHtml(issue.submitted_at)}</td>
                            <td class="py-3 px-4 text-center space-x-2">
                                <button class="text-blue-600 hover:text-blue-800" title="View"><i class="fas fa-eye"></i></button>
                                <button class="text-indigo-600 hover:text-indigo-800" title="Edit"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                    `;
                });
            }

            function filterIssues() {
                const search = searchInput.value.toLowerCase();
                const cat = categoryFilter.value;
                const stat = statusFilter.value;

                const filtered = issues.filter(i => {
                    const matchSearch = i.title.toLowerCase().includes(search) ||
                        i.description.toLowerCase().includes(search) ||
                        i.location.toLowerCase().includes(search) ||
                        i.category.toLowerCase().includes(search);
                    const matchCat = !cat || i.category === cat;
                    const matchStat = !stat || i.status === stat;
                    return matchSearch && matchCat && matchStat;
                });

                renderTable(filtered);
            }

            searchInput.addEventListener('input', filterIssues);
            categoryFilter.addEventListener('change', filterIssues);
            statusFilter.addEventListener('change', filterIssues);

            renderTable(issues);
        });
    </script>
</body>

</html>