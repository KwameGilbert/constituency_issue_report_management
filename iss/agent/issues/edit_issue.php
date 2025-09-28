<?php
// edit_issue.php - Edit existing issue
include __DIR__ . '/../components/sidebar.php';
include __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../../config/db_connection.php';
// include_once __DIR__ . '/../login/session_check.php';
$database = new Database();
$conn = $database->getConnection();

// Set current page for sidebar highlighting
$current_page = 'issues';

// Get issue ID from URL parameter
$issue_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize message variables
$message = '';
$message_type = '';

// Initialize issue variable
$issue = null;

// Check if valid issue ID was provided
if ($issue_id <= 0) {
    $message = "Invalid issue ID.";
    $message_type = "error";
} else {
    // No direct POST handling here; updates are handled via the API endpoint (update_issue.php) through AJAX.

    // Fetch current issue data
    try {
        $stmt = $conn->prepare("
            SELECT
                i.*,
                ic.name AS category_name,
                isec.name AS sector_name,
                issub.name AS subsector_name,
                mc.name AS main_community_name,
                sc.name AS smaller_community_name,
                cot.name AS cottage_name,
                s.name AS suburb_name,
                const.id AS constituent_id,
                const.name AS constituent_name,
                const.phone AS constituent_phone,
                const.location AS constituent_location
            FROM issues i
            LEFT JOIN issue_categories ic ON i.category_id = ic.id
            LEFT JOIN issue_sectors isec ON i.sector_id = isec.id
            LEFT JOIN issue_subsectors issub ON i.subsector_id = issub.id
            LEFT JOIN communities mc ON i.main_community_id = mc.id
            LEFT JOIN smaller_communities sc ON i.smaller_community_id = sc.id
            LEFT JOIN cottages cot ON i.cottage_id = cot.id
            LEFT JOIN suburbs s ON i.suburb_id = s.id
            LEFT JOIN constituents const ON i.constituent_id = const.id
            WHERE i.id = ?
        ");

        $stmt->execute([$issue_id]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$issue) {
            $message = "Issue not found.";
            $message_type = "error";
        }
    } catch (Exception $e) {
        $message = "Error loading issue details: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch data for dropdown options
$communities = [];
$smallerCommunities = [];
$categories = [];
$sectors = [];

try {
    // Fetch main communities
    $stmt = $conn->prepare("SELECT id, name FROM communities ORDER BY name");
    $stmt->execute();
    $communities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch smaller communities
    $stmt = $conn->prepare("SELECT id, name FROM smaller_communities ORDER BY name");
    $stmt->execute();
    $smallerCommunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch issue categories
    $stmt = $conn->prepare("SELECT id, name FROM issue_categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch sectors
    $stmt = $conn->prepare("SELECT id, name FROM issue_sectors ORDER BY name");
    $stmt->execute();
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch suburbs for this main community
    if ($issue && $issue['main_community_id']) {
        $stmt = $conn->prepare("SELECT id, name FROM suburbs WHERE community_id = ? ORDER BY name");
        $stmt->execute([$issue['main_community_id']]);
        $suburbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $suburbs = [];
    }

    // Fetch cottages for this smaller community
    if ($issue && $issue['smaller_community_id']) {
        $stmt = $conn->prepare("SELECT id, name FROM cottages WHERE smaller_community_id = ? ORDER BY name");
        $stmt->execute([$issue['smaller_community_id']]);
        $cottages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $cottages = [];
    }

    // Fetch subsectors for this sector
    if ($issue && $issue['sector_id']) {
        $stmt = $conn->prepare("SELECT id, name FROM issue_subsectors WHERE sector_id = ? ORDER BY name");
        $stmt->execute([$issue['sector_id']]);
        $subsectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $subsectors = [];
    }
} catch (Exception $e) {
    $message = "Error fetching dropdown data: " . $e->getMessage();
    $message_type = "error";
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Issue',
        'href' => 'view_issue.php?id=' . $issue_id,
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
    <title>Edit Issue #<?php echo $issue_id; ?> - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <?php renderAgentHeader('Edit Issue #' . $issue_id, $issue['title'] ?? 'Issue not found', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-4 p-3 rounded-xl text-xs <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($issue) : ?>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <form id="issueForm" action="edit_issue.php?id=<?php echo $issue_id; ?>" method="POST">
                        <div class="border-b border-gray-200 mb-4">
                            <div class="flex -mb-px space-x-6">
                                <button type="button" id="tab-issue" class="text-xs font-medium py-2 border-b-2 border-slate-900 text-slate-900">Issue Details</button>
                                <button type="button" id="tab-constituent" class="text-xs font-medium py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">Constituent Details</button>
                                <button type="button" id="tab-location_description" class="text-xs font-medium py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">Location</button>
                            </div>
                        </div>

                        <div id="tab-content">
                            <div id="content-issue" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="title" class="block text-xs font-medium text-gray-700 mb-1">Issue Title <span class="text-red-500">*</span></label>
                                        <input type="text" id="title" name="title" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs" value="<?php echo htmlspecialchars($issue['title'] ?? ''); ?>">
                                    </div>

                                    <div>
                                        <label for="type" class="block text-xs font-medium text-gray-700 mb-1">Issue Type <span class="text-red-500">*</span></label>
                                        <select id="type" name="type" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Type</option>
                                            <option value="personal" <?php echo ($issue['type'] ?? '') === 'personal' ? 'selected' : ''; ?>>Personal</option>
                                            <option value="community" <?php echo ($issue['type'] ?? '') === 'community' ? 'selected' : ''; ?>>Community</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="description" class="block text-xs font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                                    <textarea id="description" name="description" rows="3" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs"><?php echo htmlspecialchars($issue['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="category_id" class="block text-xs font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                                        <select id="category_id" name="category_id" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat) : ?>
                                                <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($issue['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="severity" class="block text-xs font-medium text-gray-700 mb-1">Severity <span class="text-red-500">*</span></label>
                                        <select id="severity" name="severity" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Severity</option>
                                            <option value="low" <?php echo ($issue['severity'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                                            <option value="medium" <?php echo ($issue['severity'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="high" <?php echo ($issue['severity'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="sector_id" class="block text-xs font-medium text-gray-700 mb-1">Sector <span class="text-red-500">*</span></label>
                                        <select id="sector_id" name="sector_id" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Sector</option>
                                            <?php foreach ($sectors as $sector) : ?>
                                                <option value="<?php echo htmlspecialchars($sector['id']); ?>" <?php echo ($issue['sector_id'] ?? '') == $sector['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sector['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="subsector_id" class="block text-xs font-medium text-gray-700 mb-1">Subsector</label>
                                        <select id="subsector_id" name="subsector_id" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Subsector (Optional)</option>
                                            <?php foreach ($subsectors ?? [] as $subsector) : ?>
                                                <option value="<?php echo htmlspecialchars($subsector['id']); ?>" <?php echo ($issue['subsector_id'] ?? '') == $subsector['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($subsector['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="people_affected" class="block text-xs font-medium text-gray-700 mb-1">People Affected (Approx.)</label>
                                        <input type="number" id="people_affected" name="people_affected" min="0" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs" placeholder="e.g., 100" value="<?php echo htmlspecialchars($issue['people_affected'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div>
                                    <label for="additional_notes" class="block text-xs font-medium text-gray-700 mb-1">Additional Notes</label>
                                    <textarea id="additional_notes" name="additional_notes" rows="2" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs"><?php echo htmlspecialchars($issue['additional_notes'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div id="content-constituent" class="hidden space-y-4">
                                <input type="hidden" name="constituent_id" value="<?php echo htmlspecialchars($issue['constituent_id'] ?? ''); ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="constituent_name" class="block text-xs font-medium text-gray-700 mb-1">Constituent Name <span class="text-red-500">*</span></label>
                                        <input type="text" id="constituent_name" name="constituent_name" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs" value="<?php echo htmlspecialchars($issue['constituent_name'] ?? ''); ?>">
                                    </div>

                                    <div>
                                        <label for="constituent_phone" class="block text-xs font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                                        <input type="tel" id="constituent_phone" name="constituent_phone" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs" value="<?php echo htmlspecialchars($issue['constituent_phone'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="constituent_email" class="block text-xs font-medium text-gray-700 mb-1">Email Address</label>
                                        <input type="email" id="constituent_email" name="constituent_email" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs" value="<?php echo htmlspecialchars($issue['constituent_email'] ?? ''); ?>">
                                    </div>

                                    <div>
                                        <label for="constituent_gender" class="block text-xs font-medium text-gray-700 mb-1">Gender</label>
                                        <select id="constituent_gender" name="constituent_gender" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo ($issue['constituent_gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($issue['constituent_gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="constituent_address" class="block text-xs font-medium text-gray-700 mb-1">Home Address</label>
                                    <input type="text" id="constituent_address" name="constituent_address" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs" value="<?php echo htmlspecialchars($issue['constituent_location'] ?? ''); ?>">
                                </div>
                            </div>

                            <div id="content-location_description" class="hidden space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="main_community_id" class="block text-xs font-medium text-gray-700 mb-1">Main Community <span class="text-red-500">*</span></label>
                                        <select id="main_community_id" name="main_community_id" required class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Main Community</option>
                                            <?php foreach ($communities as $community) : ?>
                                                <option value="<?php echo htmlspecialchars($community['id']); ?>" <?php echo ($issue['main_community_id'] ?? '') == $community['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($community['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="smaller_community_id" class="block text-xs font-medium text-gray-700 mb-1">Smaller Community</label>
                                        <select id="smaller_community_id" name="smaller_community_id" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Smaller Community (Optional)</option>
                                            <?php foreach ($smallerCommunities ?? [] as $smallerCommunity) : ?>
                                                <option value="<?php echo htmlspecialchars($smallerCommunity['id']); ?>" <?php echo ($issue['smaller_community_id'] ?? '') == $smallerCommunity['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($smallerCommunity['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="suburb_id" class="block text-xs font-medium text-gray-700 mb-1">Suburb</label>
                                        <select id="suburb_id" name="suburb_id" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Suburb (Optional)</option>
                                            <?php foreach ($suburbs ?? [] as $suburb) : ?>
                                                <option value="<?php echo htmlspecialchars($suburb['id']); ?>" <?php echo ($issue['suburb_id'] ?? '') == $suburb['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($suburb['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="cottage_id" class="block text-xs font-medium text-gray-700 mb-1">Cottage</label>
                                        <select id="cottage_id" name="cottage_id" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs">
                                            <option value="">Select Cottage (Optional)</option>
                                            <?php foreach ($cottages ?? [] as $cottage) : ?>
                                                <option value="<?php echo htmlspecialchars($cottage['id']); ?>" <?php echo ($issue['cottage_id'] ?? '') == $cottage['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cottage['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="location_description" class="block text-xs font-medium text-gray-700 mb-1">Specific Location Details</label>
                                        <input type="text" id="location_description" name="location_description" placeholder="e.g., 'In front of Building 5'" class="w-full px-3 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-1 focus:ring-primary focus:border-primary text-xs" value="<?php echo htmlspecialchars($issue['location_description'] ?? ''); ?>">
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
                                    <i class="fas fa-save mr-1"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php else : ?>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 text-center">
                    <div class="text-red-500 mb-3"><i class="fas fa-exclamation-triangle fa-3x"></i></div>
                    <h2 class="text-xl font-medium text-gray-800 mb-2">Issue Not Found</h2>
                    <p class="text-gray-600 mb-4">The issue you are trying to edit does not exist or you don't have permission to access it.</p>
                    <a href="./" class="px-4 py-2 bg-slate-900 text-white rounded-xl inline-block hover:bg-slate-800 transition-colors text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Issues
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation functionality  
            const tabs = ['issue', 'constituent', 'location_description'];
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
            const mainCommunitySelect = document.getElementById('main_community_id');
            const smallerCommunitySelect = document.getElementById('smaller_community_id');
            const suburbSelect = document.getElementById('suburb_id');
            const cottageSelect = document.getElementById('cottage_id');
            const sectorSelect = document.getElementById('sector_id');
            const subsectorSelect = document.getElementById('subsector_id');

            // Function to load suburbs based on selected main community
            async function loadSuburbs() {
                const mainCommunityId = mainCommunitySelect.value;
                suburbSelect.innerHTML = '<option value="">Loading Suburbs...</option>';

                if (mainCommunityId) {
                    try {
                        const response = await fetch(`../../api/get_suburbs.php?community_id=${mainCommunityId}`);
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

            // Function to load cottages based on selected smaller community
            async function loadCottages() {
                const smallerCommunityId = smallerCommunitySelect.value;
                cottageSelect.innerHTML = '<option value="">Loading Cottages...</option>';

                if (smallerCommunityId) {
                    try {
                        const response = await fetch(`../../api/get_cottages.php?smaller_community_id=${smallerCommunityId}`);
                        const cottages = await response.json();
                        populateSelect(cottageSelect, cottages, 'Select Cottage (Optional)');
                    } catch (error) {
                        console.error('Error fetching cottages:', error);
                        populateSelect(cottageSelect, [], 'Error loading cottages');
                    }
                } else {
                    populateSelect(cottageSelect, [], 'Select Cottage (Optional)');
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

            mainCommunitySelect.addEventListener('change', loadSuburbs);
            smallerCommunitySelect.addEventListener('change', loadCottages);
            sectorSelect.addEventListener('change', loadSubsectors);

            // Show initial tab
            showTab(0);





            // Get the form element for editing issues
            const editIssueForm = document.getElementById('issueForm');

            // Add an event listener for when the form is submitted
            editIssueForm.addEventListener('submit', async function(e) {
                e.preventDefault(); // Stop the default form submission behavior

                // Create a FormData object from the form, which makes it easy to send form data
                const formData = new FormData(editIssueForm);

                // Initialize SweetAlert Toast for user notifications
                const Toast = Swal.mixin({
                    toast: true, // Display as a toast notification
                    position: 'top-end', // Position the toast at the top-right of the screen
                    showConfirmButton: false, // Don't show a confirmation button
                    timer: 3000, // Hide the toast after 3 seconds
                    timerProgressBar: true, // Show a progress bar for the timer
                    didOpen: (toast) => {
                        // Pause the timer when the mouse enters the toast
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        // Resume the timer when the mouse leaves the toast
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });

                try {

                    // Send the form data to the PHP API endpoint for updating issues
                    const issueId = <?php echo json_encode($issue_id); ?>;
                    const response = await fetch(`../../api/update_agent_issue.php.php?id=${issueId}`, {
                        method: 'POST',
                        body: formData
                    });

                    // Parse the JSON response from the server
                    const result = await response.json();

                    if (result.success) {
                        // Show a success toast if the update was successful
                        Toast.fire({
                            icon: 'success',
                            title: result.message || 'Issue updated successfully!'
                        });

                        // Redirect to the dashboard after a short delay
                        setTimeout(() => {
                            window.location_description.href = './../dashboard/';
                        }, 3200); // Wait for the toast to be visible for a moment
                    } else {
                        // Show an error toast if the update failed
                        Toast.fire({
                            icon: 'error',
                            title: result.message || 'Failed to update issue.'
                        });
                        // Log the server's error message to the console for debugging
                        console.error(result.error);
                    }
                } catch (error) {
                    // Catch any network or other unexpected errors
                    console.error('Submission error:', error);
                    // Show a generic error toast for unexpected issues
                    Toast.fire({
                        icon: 'error',
                        title: 'Something went wrong while updating the form.'
                    });
                }
            });




        });
    </script>
</body>

</html>