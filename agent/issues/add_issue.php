<?php
// add_issue.php
include __DIR__ . '/../components/sidebar.php';
include __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../../config/db_connection.php';
// include_once __DIR__ . '/../login/session_check.php';
$database = new Database();
$conn = $database->getConnection();

// Set current page for sidebar highlighting
$current_page = 'issues';

// Initialize message variables
$message = '';
$message_type = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Process the form submission here
        // This would be handled by IssueController
        $message = "Issue submitted successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch data for dropdowns from database
$electoralAreas = [];
$categories = [];
$sectors = [];

try {
    // Fetch electoral areas
    $stmt = $conn->prepare("SELECT id, name FROM electoral_areas ORDER BY name");
    $stmt->execute();
    $electoralAreas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch issue categories
    $stmt = $conn->prepare("SELECT id, name FROM issue_categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch sectors
    $stmt = $conn->prepare("SELECT id, name FROM issue_sectors ORDER BY name");
    $stmt->execute();
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Log error but continue with empty arrays
    error_log("Error fetching dropdown data: " . $e->getMessage());
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Issues',
        'href' => './',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

// Get current user data for display
$userName = $_SESSION['user_name'] ?? 'Agent';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add New Issue - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        slate: {
                            50: '#f8fafc',
                            900: '#0f172a',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
    </script>
</head>

<body class="bg-slate-50 min-h-screen font-sans">
    <?php renderAgentSidebar($current_page); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAgentHeader('Add New Issue', 'Submit a new issue on behalf of a constituent or community', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-4 p-3 rounded-xl text-xs <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <form id="issueForm" action="add_issue.php" method="POST">
                    <!-- Form tabs -->
                    <div class="border-b border-gray-200 mb-4">
                        <div class="flex -mb-px space-x-6">
                            <button type="button" id="tab-issue" class="text-xs font-medium py-2 border-b-2 border-slate-900 text-slate-900">Issue Details</button>
                            <button type="button" id="tab-constituent" class="text-xs font-medium py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">Constituent Details</button>
                            <button type="button" id="tab-location" class="text-xs font-medium py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">Location</button>
                        </div>
                    </div>

                    <!-- Tab content -->
                    <div id="tab-content">
                        <!-- Issue Details Tab -->
                        <div id="content-issue" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="title" class="block text-xs font-medium text-gray-700 mb-1">Issue Title <span class="text-red-500">*</span></label>
                                    <input type="text" id="title" name="title" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                </div>

                                <div>
                                    <label for="type" class="block text-xs font-medium text-gray-700 mb-1">Issue Type <span class="text-red-500">*</span></label>
                                    <select id="type" name="type" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Type</option>
                                        <option value="personal">Personal</option>
                                        <option value="community">Community</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="description" class="block text-xs font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                                <textarea id="description" name="description" rows="3" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs"></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="category_id" class="block text-xs font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                                    <select id="category_id" name="category_id" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat) : ?>
                                            <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="severity" class="block text-xs font-medium text-gray-700 mb-1">Severity <span class="text-red-500">*</span></label>
                                    <select id="severity" name="severity" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Severity</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="sector_id" class="block text-xs font-medium text-gray-700 mb-1">Sector <span class="text-red-500">*</span></label>
                                    <select id="sector_id" name="sector_id" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Sector</option>
                                        <?php foreach ($sectors as $sector) : ?>
                                            <option value="<?= htmlspecialchars($sector['id']) ?>"><?= htmlspecialchars($sector['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="subsector_id" class="block text-xs font-medium text-gray-700 mb-1">Subsector</label>
                                    <select id="subsector_id" name="subsector_id" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Subsector (Optional)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="people_affected" class="block text-xs font-medium text-gray-700 mb-1">People Affected (Approx.)</label>
                                    <input type="number" id="people_affected" name="people_affected" min="0" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs" placeholder="e.g., 100">
                                </div>

                             </div>

                            <div>
                                <label for="additional_notes" class="block text-xs font-medium text-gray-700 mb-1">Additional Notes</label>
                                <textarea id="additional_notes" name="additional_notes" rows="2" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs"></textarea>
                            </div>
                        </div>

                        <!-- Constituent Details Tab (hidden by default) -->
                        <div id="content-constituent" class="hidden space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="constituent_name" class="block text-xs font-medium text-gray-700 mb-1">Constituent Name <span class="text-red-500">*</span></label>
                                    <input type="text" id="constituent_name" name="constituent_name" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                </div>

                                <div>
                                    <label for="constituent_phone" class="block text-xs font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                                    <input type="tel" id="constituent_phone" name="constituent_phone" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="constituent_email" class="block text-xs font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" id="constituent_email" name="constituent_email" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                </div>

                                <div>
                                    <label for="constituent_gender" class="block text-xs font-medium text-gray-700 mb-1">Gender</label>
                                    <select id="constituent_gender" name="constituent_gender" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="constituent_address" class="block text-xs font-medium text-gray-700 mb-1">Home Address</label>
                                <input type="text" id="constituent_address" name="constituent_address" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                            </div>
                        </div>

                        <!-- Location Tab (hidden by default) -->
                        <div id="content-location" class="hidden space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="electoral_area_id" class="block text-xs font-medium text-gray-700 mb-1">Electoral Area <span class="text-red-500">*</span></label>
                                    <select id="electoral_area_id" name="electoral_area_id" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Electoral Area</option>
                                        <?php foreach ($electoralAreas as $area) : ?>
                                            <option value="<?= htmlspecialchars($area['id']) ?>"><?= htmlspecialchars($area['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="community_id" class="block text-xs font-medium text-gray-700 mb-1">Community <span class="text-red-500">*</span></label>
                                    <select id="community_id" name="community_id" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Community</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="suburb_id" class="block text-xs font-medium text-gray-700 mb-1">Suburb</label>
                                    <select id="suburb_id" name="suburb_id" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                        <option value="">Select Suburb (Optional)</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="location" class="block text-xs font-medium text-gray-700 mb-1">Specific Location Details</label>
                                    <input type="text" id="location" name="location" placeholder="e.g., 'In front of Building 5'" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-xs text-gray-500">
                            <span class="text-red-500">*</span> Required fields
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" id="prev-btn" class="px-4 py-1.5 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-colors text-xs hidden">
                                <i class="fas fa-arrow-left mr-1"></i> Previous
                            </button>
                            <button type="button" id="next-btn" class="px-4 py-1.5 bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition-colors text-xs">
                                Next <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                            <button type="submit" id="submit-btn" class="px-5 py-1.5 bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition-colors text-xs hidden">
                                <i class="fas fa-plus-circle mr-1"></i> Submit Issue
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation functionality  
            const tabs = ['issue', 'constituent', 'location'];
            let currentTabIndex = 0;

            const tabButtons = tabs.map(tab => document.getElementById(`tab-${tab}`));
            const tabContents = tabs.map(tab => document.getElementById(`content-${tab}`));
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const submitBtn = document.getElementById('submit-btn');

            function showTab(index) {
                tabButtons.forEach((btn, i) => {
                    if (i === index) {
                        btn.classList.add('border-slate-900', 'text-slate-900');
                        btn.classList.remove('border-transparent', 'text-gray-500');
                    } else {
                        btn.classList.remove('border-slate-900', 'text-slate-900');
                        btn.classList.add('border-transparent', 'text-gray-500');
                    }
                });

                tabContents.forEach((content, i) => {
                    if (i === index) {
                        content.classList.remove('hidden');
                    } else {
                        content.classList.add('hidden');
                    }
                });

                // Update navigation buttons
                prevBtn.classList.toggle('hidden', index === 0);
                nextBtn.classList.toggle('hidden', index === tabs.length - 1);
                submitBtn.classList.toggle('hidden', index !== tabs.length - 1);

                currentTabIndex = index;
            }

            // Set up tab click handlers
            tabButtons.forEach((btn, i) => {
                btn.addEventListener('click', () => showTab(i));
            });

            // Set up navigation buttons
            prevBtn.addEventListener('click', () => {
                if (currentTabIndex > 0) {
                    showTab(currentTabIndex - 1);
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentTabIndex < tabs.length - 1) {
                    showTab(currentTabIndex + 1);
                }
            });

            // Initialize dynamic dropdowns
            const electoralAreaSelect = document.getElementById('electoral_area_id');
            const communitySelect = document.getElementById('community_id');
            const suburbSelect = document.getElementById('suburb_id');
            const sectorSelect = document.getElementById('sector_id');
            const subsectorSelect = document.getElementById('subsector_id');

            // Function to load communities based on selected electoral area
            async function loadCommunities() {
                const electoralAreaId = electoralAreaSelect.value;
                communitySelect.innerHTML = '<option value="">Loading Communities...</option>';
                suburbSelect.innerHTML = '<option value="">Select Suburb (Optional)</option>'; // Reset suburbs

                if (electoralAreaId) {
                    try {
                        const response = await fetch(`../../api/get_communities.php?electoral_area_id=${electoralAreaId}`);
                        const communities = await response.json();
                        populateSelect(communitySelect, communities, 'Select Community');
                    } catch (error) {
                        console.error('Error fetching communities:', error);
                        populateSelect(communitySelect, [], 'Error loading communities');
                    }
                } else {
                    populateSelect(communitySelect, [], 'Select Community');
                }
            }

            // Function to load suburbs based on selected community
            async function loadSuburbs() {
                const communityId = communitySelect.value;
                suburbSelect.innerHTML = '<option value="">Loading Suburbs...</option>';

                if (communityId) {
                    try {
                        const response = await fetch(`../../api/get_suburbs.php?community_id=${communityId}`);
                        const suburbs = await response.json();
                        populateSelect(suburbSelect, suburbs, 'Select Suburb (Optional)');
                    } catch (error) {
                        console.error('Error fetching suburbs:', error);
                        populateSelect(suburbSelect, [], 'Error loading suburbs');
                    }
                } else {
                    populateSelect(suburbSelect, [], 'Select Suburb (Optional)');
                }
            }

            // Function to load subsectors based on selected sector
            async function loadSubsectors() {
                const sectorId = sectorSelect.value;
                subsectorSelect.innerHTML = '<option value="">Loading Subsectors...</option>';

                if (sectorId) {
                    try {
                        const response = await fetch(`../../api/get_issue_subsectors.php?sector_id=${sectorId}`);
                        const subsectors = await response.json();
                        populateSelect(subsectorSelect, subsectors, 'Select Subsector (Optional)');
                    } catch (error) {
                        console.error('Error fetching subsectors:', error);
                        populateSelect(subsectorSelect, [], 'Error loading subsectors');
                    }
                } else {
                    populateSelect(subsectorSelect, [], 'Select Subsector (Optional)');
                }
            }

            function populateSelect(selectElement, data, defaultOptionText) {
                selectElement.innerHTML = `<option value="">${defaultOptionText}</option>`;
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    selectElement.appendChild(option);
                });
            }

            electoralAreaSelect.addEventListener('change', loadCommunities);
            communitySelect.addEventListener('change', loadSuburbs);
            sectorSelect.addEventListener('change', loadSubsectors);

            // Show initial tab
            showTab(0);
        });
    </script>
</body>

</html>