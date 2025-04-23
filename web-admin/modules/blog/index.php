<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';
$posts = $conn->query("SELECT id, title, created_at FROM blog_posts ORDER BY created_at DESC");
require_once '../../includes/header.php';
?>
<main class="p-8">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Blog Posts</h2>
        <a href="create.php" class="bg-green-600 text-white px-4 py-2 rounded">+ New Post</a>
    </div>
    <table class="min-w-full bg-white shadow rounded">
        <thead>
            <tr>
                <th class="p-2">Title</th>
                <th class="p-2">Date</th>
                <th class="p-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($p = $posts->fetch_assoc()): ?>
            <tr class="border-t">
                <td class="p-2"><?= htmlspecialchars($p['title']) ?></td>
                <td class="p-2"><?= date('Y-m-d',strtotime($p['created_at'])) ?></td>
                <td class="p-2 space-x-2">
                    <a href="edit.php?id=<?= $p['id'] ?>" class="text-blue-600">Edit</a>
                    <a href="delete.php?id=<?= $p['id'] ?>" class="text-red-600"
                        onclick="return confirm('Delete this post?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>
<?php require_once '../../includes/footer.php'; ?>