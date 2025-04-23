<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';
$evs = $conn->query("SELECT * FROM events ORDER BY start_date DESC");
require_once '../../includes/header.php';
?>
<main class="p-8">
    <div class="flex justify-between mb-4">
        <h2 class="text-2xl font-bold">Events</h2>
        <a href="create.php" class="bg-green-600 text-white px-4 py-2 rounded">+ New Event</a>
    </div>
    <table class="min-w-full bg-white shadow rounded">
        <thead>
            <tr>
                <th class="p-2">Name</th>
                <th>Date</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($e = $evs->fetch_assoc()): ?>
            <tr class="border-t">
                <td class="p-2"><?= htmlspecialchars($e['name']) ?></td>
                <td class="p-2"><?= date('Y-m-d',strtotime($e['start_date'])) ?></td>
                <td class="p-2"><?= htmlspecialchars($e['location']) ?></td>
                <td class="p-2">
                    <a href="edit.php?id=<?= $e['id'] ?>" class="text-blue-600">Edit</a>
                    <a href="delete.php?id=<?= $e['id'] ?>" class="text-red-600"
                        onclick="return confirm('Delete event?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>
<?php require_once '../../includes/footer.php'; ?>