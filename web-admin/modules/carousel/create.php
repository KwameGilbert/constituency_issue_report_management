<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';
if ($_POST) {
  $stmt = $conn->prepare("
    INSERT INTO carousel_items (title, image_url, link, position)
    VALUES (?, ?, ?, ?)
  ");
  $stmt->bind_param('sssi',
    $_POST['title'], $_POST['image_url'], $_POST['link'], $_POST['position']
  );
  $stmt->execute();
  header('Location: index.php');
  exit;
}
require_once '../../includes/header.php';
?>
<main class="p-8">
    <h2 class="text-2xl font-bold mb-4">New Carousel Slide</h2>
    <form method="POST">
        <input name="title" placeholder="Title" class="w-full mb-3 p-2 border rounded" required>
        <input name="image_url" placeholder="Image URL" class="w-full mb-3 p-2 border rounded" required>
        <input name="link" placeholder="Target Link" class="w-full mb-3 p-2 border rounded" required>
        <input type="number" name="position" placeholder="Position" class="w-full mb-3 p-2 border rounded" value="0">
        <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">Save</button>
    </form>
</main>
<?php require_once '../../includes/footer.php'; ?>