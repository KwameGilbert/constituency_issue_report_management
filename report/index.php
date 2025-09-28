<?php
require_once '../config/db.php';

// Get constituency agents/officers from database using the users table
$agents_query = "SELECT u.*, 
                c.name as main_community_name,
                sc.name as smaller_community_name,
                s.name as suburb_name,
                ct.name as cottage_name
                FROM users u 
                LEFT JOIN communities c ON u.main_community_id = c.id 
                LEFT JOIN smaller_communities sc ON u.smaller_community_id = sc.id 
                LEFT JOIN suburbs s ON u.suburb_id = s.id 
                LEFT JOIN cottages ct ON u.cottage_id = ct.id 
                WHERE u.status = 'active' 
                AND u.role IN ('agent', 'officer')
                ORDER BY 
                    CASE u.role 
                        WHEN 'officer' THEN 1
                        WHEN 'agent' THEN 2
                        ELSE 3
                    END,
                    u.name";
$agents_result = $conn->query($agents_query);
$agents = [];
while ($agent = $agents_result->fetch_assoc()) {
    $agents[] = $agent;
}

// Group agents by role and area for better organization
$agents_by_role = [];
foreach ($agents as $agent) {
    $role = $agent['role'];
    if (!isset($agents_by_role[$role])) {
        $agents_by_role[$role] = [];
    }
    $agents_by_role[$role][] = $agent;
}

// Role display names mapping
$role_display_names = [
    'officer' => 'Constituency Officer',
    'agent' => 'Constituency Agent',
];

// Role priority for ordering
$role_priority = ['officer', 'agent'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Constituency Team | Sefwi Wiawso Constituency</title>
    <meta name="description" content="Contact our constituency team including MP, MCE, officers and agents for assistance">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/styles/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
</head>

<body class="bg-gray-50">
    <?php include_once '../includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="bg-amber-600 text-white py-12 md:py-20">
            <div class="max-w-6xl mx-auto px-4">
                <h1 class="text-3xl md:text-4xl font-bold mb-4">Contact Constituency Team</h1>
                <p class="text-lg md:text-xl max-w-3xl">Get in touch with our dedicated constituency team including MP, MCE, officers and agents.</p>
            </div>
        </section>

        <div class="max-w-6xl mx-auto px-4 py-12">
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="bg-blue-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-phone text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Emergency Hotline</h3>
                    <p class="text-gray-600 mb-4">For urgent matters requiring immediate attention</p>
                    <a href="tel:+233244123456" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                        <i class="fas fa-phone mr-2"></i> +233 244 123 456
                    </a>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="bg-green-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-envelope text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Email Support</h3>
                    <p class="text-gray-600 mb-4">Send detailed issues to our constituency office</p>
                    <a href="mailto:office@sefwiwiawso.gov.gh" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-envelope mr-2"></i> Send Email
                    </a>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="bg-purple-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-map-marker-alt text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Visit Office</h3>
                    <p class="text-gray-600 mb-4">Come to our main constituency office</p>
                    <a href="/contact" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        <i class="fas fa-directions mr-2"></i> Get Directions
                    </a>
                </div>
            </div>

            <!-- Team Directory -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 bg-gray-50 border-b">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Constituency Team Directory</h2>
                            <p class="text-gray-600 mt-1">Contact information for our constituency team members</p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <div class="relative">
                                <input type="text" id="searchTeam" placeholder="Search team members..." 
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-amber-500 focus:border-amber-500 w-full md:w-64">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <?php if (empty($agents_by_role)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-users text-gray-300 text-6xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Team Members Available</h3>
                            <p class="text-gray-500">Constituency team information will be updated soon.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($role_priority as $role): ?>
                            <?php if (isset($agents_by_role[$role])): ?>
                                <div class="mb-8 last:mb-0">
                                    <?php
                                    $role_color = [
                                        'mp' => 'bg-purple-100 text-purple-800',
                                        'mce' => 'bg-blue-100 text-blue-800',
                                        'pa' => 'bg-green-100 text-green-800',
                                        'officer' => 'bg-amber-100 text-amber-800',
                                        'agent' => 'bg-gray-100 text-gray-800',
                                        'admin' => 'bg-red-100 text-red-800'
                                    ][$role];
                                    ?>
                                    
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200 flex items-center">
                                        <i class="fas fa-user-shield text-amber-600 mr-2"></i>
                                        <?= htmlspecialchars($role_display_names[$role]) ?>
                                        <span class="ml-2 <?= $role_color ?> text-xs px-2 py-1 rounded-full">
                                            <?= count($agents_by_role[$role]) ?> member<?= count($agents_by_role[$role]) > 1 ? 's' : '' ?>
                                        </span>
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <?php foreach ($agents_by_role[$role] as $member): ?>
                                            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 hover:border-amber-300 transition-colors team-member-card">
                                                <!-- Profile Image and Basic Info -->
                                                <div class="flex items-start justify-between mb-4">
                                                    <div class="flex items-center">
                                                        <?php if (!empty($member['profile_image'])): ?>
                                                            <img src="<?= htmlspecialchars($member['profile_image']) ?>" 
                                                                 alt="<?= htmlspecialchars($member['name']) ?>" 
                                                                 class="w-12 h-12 rounded-full object-cover mr-3">
                                                        <?php else: ?>
                                                            <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mr-3">
                                                                <i class="fas fa-user text-amber-600"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <h4 class="font-semibold text-gray-900 text-lg"><?= htmlspecialchars($member['name']) ?></h4>
                                                            <p class="text-amber-600 font-medium"><?= htmlspecialchars($role_display_names[$member['role']]) ?></p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Location Information -->
                                                <?php 
                                                $location_parts = array_filter([
                                                    $member['cottage_name'],
                                                    $member['suburb_name'],
                                                    $member['smaller_community_name'],
                                                    $member['main_community_name']
                                                ]);
                                                if (!empty($location_parts)): ?>
                                                    <div class="flex items-center mb-3 text-sm text-gray-600">
                                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                                        <span><?= htmlspecialchars(implode(', ', $location_parts)) ?></span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($member['department'])): ?>
                                                    <div class="flex items-center mb-3 text-sm text-gray-600">
                                                        <i class="fas fa-building mr-2"></i>
                                                        <span><?= htmlspecialchars($member['department']) ?></span>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Contact Information -->
                                                <div class="space-y-2">
                                                    <?php if (!empty($member['phone'])): ?>
                                                        <div class="flex items-center">
                                                            <i class="fas fa-phone text-gray-400 mr-3 w-4"></i>
                                                            <div>
                                                                <a href="tel:<?= htmlspecialchars($member['phone']) ?>" 
                                                                   class="text-gray-700 hover:text-amber-600 transition-colors text-sm">
                                                                    <?= htmlspecialchars($member['phone']) ?>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($member['email'])): ?>
                                                        <div class="flex items-center">
                                                            <i class="fas fa-envelope text-gray-400 mr-3 w-4"></i>
                                                            <div>
                                                                <a href="mailto:<?= htmlspecialchars($member['email']) ?>" 
                                                                   class="text-gray-700 hover:text-amber-600 transition-colors text-sm break-all">
                                                                    <?= htmlspecialchars($member['email']) ?>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="mt-4 flex space-x-2">
                                                    <?php if (!empty($member['phone'])): ?>
                                                        <a href="tel:<?= htmlspecialchars($member['phone']) ?>" 
                                                           class="flex-1 bg-amber-600 text-white text-center py-2 px-3 rounded-md hover:bg-amber-700 transition-colors text-sm">
                                                            <i class="fas fa-phone mr-1"></i> Call
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($member['email'])): ?>
                                                        <a href="mailto:<?= htmlspecialchars($member['email']) ?>" 
                                                           class="flex-1 bg-blue-600 text-white text-center py-2 px-3 rounded-md hover:bg-blue-700 transition-colors text-sm">
                                                            <i class="fas fa-envelope mr-1"></i> Email
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Last Login Info -->
                                                <?php if (!empty($member['last_login'])): ?>
                                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                                        <p class="text-xs text-gray-500">
                                                            Last active: <?= date('M j, Y', strtotime($member['last_login'])) ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Guidelines -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-blue-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-blue-900 mb-3 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Role-Based Contact Guidelines
                    </h3>
                    <div class="space-y-3 text-blue-800">
                        <div>
                            <strong>MP & MCE:</strong> Policy matters, major development projects, inter-governmental issues
                        </div>
                        <div>
                            <strong>Presidential Appointees:</strong> Special government programs, national initiatives
                        </div>
                        <div>
                            <strong>Officers:</strong> Department-specific issues, ongoing projects, official documentation
                        </div>
                        <div>
                            <strong>Agents:</strong> Community-level issues, grassroots concerns, local coordination
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-green-900 mb-3 flex items-center">
                        <i class="fas fa-lightbulb mr-2"></i> Effective Communication Tips
                    </h3>
                    <ul class="list-disc pl-5 space-y-2 text-green-800">
                        <li>Identify the right team member based on your issue type</li>
                        <li>Have your location details ready (community/suburb)</li>
                        <li>Be clear and specific about the issue</li>
                        <li>Contact during regular business hours when possible</li>
                        <li>Allow 24-48 hours for response during busy periods</li>
                        <li>Follow up politely if you don't hear back</li>
                    </ul>
                </div>
            </div>

            <!-- Emergency Notice -->
            <div class="mt-8 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Emergency Situations</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>For life-threatening emergencies, criminal activities, or urgent medical situations, please contact:</p>
                            <ul class="list-disc pl-5 mt-2 space-y-1">
                                <li><strong>Police Emergency:</strong> <a href="tel:191" class="font-bold">191</a> or <a href="tel:18555" class="font-bold">18555</a></li>
                                <li><strong>Fire Service:</strong> <a href="tel:192" class="font-bold">192</a></li>
                                <li><strong>Ambulance Service:</strong> <a href="tel:193" class="font-bold">193</a></li>
                                <li><strong>Constituency Emergency:</strong> <a href="tel:+233244123456" class="font-bold">+233 244 123 456</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once '../includes/footer.php'; ?>

    <script>
        // Search functionality
        document.getElementById('searchTeam').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const memberCards = document.querySelectorAll('.team-member-card');
            
            memberCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>