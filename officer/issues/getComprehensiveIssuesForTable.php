<?php 
// Prepare and execute the query
$issuesStmt = $conn->prepare("
SELECT
i.id,
i.title,
i.description,
i.type,
i.severity,
i.status,
DATE(i.created_at) AS submitted_at,
i.location,
ic.name AS category,
isec.name AS sector,
iss.name AS subsector,
ea.name AS electoral_area,
c.name AS community,
u.name AS agent
FROM issues i
LEFT JOIN issue_categories ic ON i.category_id = ic.id
LEFT JOIN issue_sectors isec ON i.sector_id = isec.id
LEFT JOIN issue_subsectors iss ON i.subsector_id = iss.id
LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
LEFT JOIN communities c ON i.community_id = c.id
LEFT JOIN users u ON i.agent_id = u.id
ORDER BY i.created_at DESC
");
$issuesStmt->execute();
$issues = $issuesStmt->fetchAll(PDO::FETCH_ASSOC);

function extractAndSortUnique($array, $key) {
$values = array_unique(array_column($array, $key));
sort($values);
return $values;
}

$categories = extractAndSortUnique($issues, 'category');
$statuses = extractAndSortUnique($issues, 'status');
$types = extractAndSortUnique($issues, 'type');
$sectors = extractAndSortUnique($issues, 'sector');
$subsectors = extractAndSortUnique($issues, 'subsector');
$electoralAreas = extractAndSortUnique($issues, 'electoral_area');
$communities = extractAndSortUnique($issues, 'community');
$severities = extractAndSortUnique($issues, 'severity');
$agents = extractAndSortUnique($issues, 'agent');