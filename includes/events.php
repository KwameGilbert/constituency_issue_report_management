<?php
$evs = $conn->query(
  "SELECT * FROM events WHERE start_date >= CURDATE() ORDER BY start_date ASC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);
?>
<section class="py-12">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-2xl font-semibold mb-6">Upcoming Events</h2>
        <ul class="space-y-4">
            <?php foreach ($evs as $e): ?>
            <li class="flex items-start bg-gray-50 p-4 rounded">
                <div class="w-24 text-sm text-gray-500">
                    <?= date('M d, Y', strtotime($e['start_date'])) ?>
                </div>
                <div class="ml-4">
                    <h4 class="font-bold"><?= htmlspecialchars($e['name']) ?></h4>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($e['location']) ?></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>