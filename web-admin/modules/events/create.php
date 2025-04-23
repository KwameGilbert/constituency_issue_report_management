<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';
if ($_POST) {
  $stmt = $conn->prepare("
    INSERT INTO events (name, slug, description, start_date, end_date, location, image_url)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $_POST['name']));
  $stmt->bind_param('sssssss',
    $_POST['name'], $slug, $_POST['description'],
    $_POST['start_date'], $_POST['end_date'], $_POST['location'], $_POST['image_url']
  );
  $stmt->execute();
  header('Location: index.php');
  exit;
}
require_once '../../includes/header.php';
?>
<main class="p-8">
    <h2 class="text-2xl font-bold mb-4">New Event</h2>
    <form method="POST">
        <input name="name" placeholder="Event Name" class="w-full p-2 border rounded mb-3" required>
        <input type="date" name="start_date" required class="w-full p-2 border rounded mb-3">
        <input type="date" name="end_date" class="w-full p-2 border rounded mb-3">
        <input name="location" placeholder="Location" class="w-full p-2 border rounded mb-3">
        <input name="image_url" placeholder="Image URL" class="w-full p-2 border rounded mb-3">
        <textarea id="editor" name="description"></textarea>
        <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">Save</button>
    </form>
</main>

<!-- TinyMCE for event description -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#editor',
    plugins: 'image media link lists',
    toolbar: 'undo redo | bold italic | bullist numlist | image media link',
    height: 300
});
</script>
<?php require_once '../../includes/footer.php'; ?>