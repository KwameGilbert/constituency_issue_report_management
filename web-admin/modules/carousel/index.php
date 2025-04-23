<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';
$items = $conn->query("SELECT * FROM carousel_items ORDER BY position ASC");
require_once '../../includes/header.php';
?>
<main class="p-8">
    <div class="flex justify-between mb-4">
        <h2 class="text-2xl font-bold">Homepage Carousel</h2>
        <a href="create.php" class="bg-green-600 text-white px-4 py-2 rounded">+ New Slide</a>
    </div>
    <table class="min-w-full bg-white shadow rounded">
        <thead>
            <tr>
                <th class="p-2">Position</th>
                <th>Title</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($c = $items->fetch_assoc()): ?>
            <tr class="border-t">
                <td class="p-2"><?= $c['position'] ?></td>
                <td class="p-2"><?= htmlspecialchars($c['title']) ?></td>
                <td class="p-2"><img src="<?= htmlspecialchars($c['image_url']) ?>" class="w-16 h-8 object-cover"></td>
                <td class="p-2">
                    <a href="edit.php?id=<?= $c['id'] ?>" class="text-blue-600">Edit</a>
                    <a href="delete.php?id=<?= $c['id'] ?>" class="text-red-600"
                        onclick="return confirm('Delete slide?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>
<?php require_once '../../includes/footer.php'; ?>