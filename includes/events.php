<?php
$evs = $conn
    ->query("
      SELECT id, name, location, image_url, 
             DATE_FORMAT(start_date, '%d') AS day,
             DATE_FORMAT(start_date, '%b %Y') AS month_year,
             DATE_FORMAT(event_time, '%h:%i %p') AS event_time
      FROM events
      WHERE start_date >= CURDATE()
      ORDER BY start_date ASC
      LIMIT 5
    ")
    ->fetch_all(MYSQLI_ASSOC);
?>
<section class="bg-white py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Upcoming Events</h2>
            <div class="mt-2 w-20 h-1 bg-blue-500"></div>
        </div>

        <?php if (empty($evs)): ?>
        <p class="text-gray-500">No upcoming events scheduled.</p>
        <?php else: ?>
        <div class="grid gap-6">
            <?php foreach ($evs as $e): ?>
            <div
                class="group hover:bg-gray-50 transition-colors duration-200 rounded-lg border border-gray-200 p-4 flex items-start">

                <!-- Date Block -->
                <div class="flex flex-col items-center text-center w-16 mr-4">
                    <div class="text-2xl font-bold text-blue-600"><?= htmlspecialchars($e['day']) ?></div>
                    <div class="text-sm text-gray-600"><?= htmlspecialchars($e['month_year']) ?></div>
                </div>

                <!-- Details -->
                <div class="flex-1">
                    <h4 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 mb-2">
                        <?= htmlspecialchars($e['name']) ?>
                    </h4>
                    <div class="flex flex-wrap items-center text-sm text-gray-600 space-x-4">

                        <!-- Time -->
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?= htmlspecialchars($e['event_time']) ?>
                        </div>

                        <!-- Location -->
                        <div class="flex items-center">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <?= htmlspecialchars($e['location']) ?>
                        </div>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>