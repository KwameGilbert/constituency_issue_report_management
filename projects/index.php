<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Build filters
$sector = $_GET['sector'] ?? '';
$loc    = $_GET['location'] ?? '';
$where  = [];
if ($sector)  $where[] = "sector = '". $conn->real_escape_string($sector) ."'";
if ($loc)     $where[] = "location = '". $conn->real_escape_string($loc) ."'";
$whereSql = $where ? "WHERE ". implode(' AND ', $where) : '';

// Fetch all projects with filters
$all = $conn
  ->query("SELECT * FROM projects $whereSql ORDER BY start_date DESC")
  ->fetch_all(MYSQLI_ASSOC);

// Fetch distinct sectors & locations for dropdowns
$sectors = $conn->query("SELECT DISTINCT sector FROM projects")->fetch_all(MYSQLI_ASSOC);
$locations = $conn->query("SELECT DISTINCT location FROM projects")->fetch_all(MYSQLI_ASSOC);
?>

<main class="py-12">
    <div class="max-w-6xl mx-auto px-4">
        <!-- Filters -->
        <form method="GET" class="mb-6 flex space-x-4 items-end">
            <div>
                <label class="block text-sm font-medium">Sector</label>
                <select name="sector" class="mt-1 block w-full border rounded p-2">
                    <option value="">All</option>
                    <?php foreach ($sectors as $s): ?>
                    <option value="<?= htmlspecialchars($s['sector']) ?>" <?= $s['sector']==$sector?'selected':'' ?>>
                        <?= htmlspecialchars($s['sector']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Location</label>
                <select name="location" class="mt-1 block w-full border rounded p-2">
                    <option value="">All</option>
                    <?php foreach ($locations as $l): ?>
                    <option value="<?= htmlspecialchars($l['location']) ?>" <?= $l['location']==$loc?'selected':'' ?>>
                        <?= htmlspecialchars($l['location']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                Filter
            </button>
        </form>

        <!-- Project Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($all as $proj): ?>
            <div class="bg-gray-50 rounded-lg shadow overflow-hidden">
                <div class="h-40 overflow-hidden">
                    <?php $thumb = json_decode($proj['images'],true)[0] ?? ''; ?>
                    <img src="<?= htmlspecialchars($thumb) ?>" class="w-full h-full object-cover">
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($proj['title']) ?></h3>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($proj['sector']) ?> &bull;
                        <?= htmlspecialchars($proj['location']) ?></p>
                    <a href="projects.php?project=<?= $proj['id'] ?>"
                        class="mt-2 inline-block text-amber-600 hover:underline">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>