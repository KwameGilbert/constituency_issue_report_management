<?php
// new/api/dashboard_data.php
// This would connect to your database, fetch actual data, and return JSON.

header('Content-Type: application/json');

// --- Replace with actual database queries ---
// Include your Database connection and Issue model here
// For demonstration, using static data
$totalIssues = 45;
$pendingReview = 12;
$approvedIssues = 28;
$rejectedIssues = 3;
$resolvedIssues = 25;

$issuesByStatus = [
    ['name' => 'Approved', 'value' => $approvedIssues, 'color' => '#10B981'],
    ['name' => 'Pending Review', 'value' => $pendingReview, 'color' => '#F59E0B'],
    ['name' => 'Rejected', 'value' => $rejectedIssues, 'color' => '#EF4444'],
    ['name' => 'Resolved', 'value' => $resolvedIssues, 'color' => '#3B82F6'],
];

$issuesByCategory = [
    ['name' => 'Category A', 'value' => 16],
    ['name' => 'Category B', 'value' => 12],
    ['name' => 'Category C', 'value' => 8],
    ['name' => 'Category D', 'value' => 14],
    ['name' => 'Category E', 'value' => 4],
    ['name' => 'Category F', 'value' => 7],
];

echo json_encode([
    'totalIssues' => $totalIssues,
    'pendingReview' => $pendingReview,
    'approvedIssues' => $approvedIssues,
    'rejectedIssues' => $rejectedIssues,
    'resolvedIssues' => $resolvedIssues,
    'issuesByStatus' => $issuesByStatus,
    'issuesByCategory' => $issuesByCategory,
    'lastUpdated' => date('n/j/Y H:i:s')
]);
