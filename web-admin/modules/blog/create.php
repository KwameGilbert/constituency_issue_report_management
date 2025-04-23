<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';
if ($_POST) {
  // Save post with content from TinyMCE
  $stmt = $conn->prepare("
    INSERT INTO blog_posts (title, slug, excerpt, content, image_url)
    VALUES (?, ?, ?, ?, ?)
  ");
  $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $_POST['title']));
  $stmt->bind_param('sssss',
    $_POST['title'], $slug, $_POST['excerpt'], $_POST['content'], $_POST['image_url']
  );
  $stmt->execute();
  header('Location: index.php');
  exit;
}
require_once '../../includes/header.php';
?>
<main class="p-8">
    <h2 class="text-2xl font-bold mb-4">New Blog Post</h2>
    <form method="POST">
        <input name="title" placeholder="Title" class="w-full p-2 border rounded mb-3" required>
        <input name="excerpt" placeholder="Excerpt" class="w-full p-2 border rounded mb-3" required>
        <input name="image_url" placeholder="Header Image URL" class="w-full p-2 border rounded mb-3">
        <textarea id="editor" name="content"></textarea>
        <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">Save</button>
    </form>
</main>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#editor',
    plugins: 'image media link table code lists',
    toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | image media link | code',
    height: 400,
    media_live_embeds: true
});
</script>
<?php require_once '../../includes/footer.php'; ?>