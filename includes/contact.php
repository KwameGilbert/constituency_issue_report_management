<section class="py-12 bg-gray-50">
    <div class="max-w-md mx-auto px-4">
        <h2 class="text-2xl font-semibold mb-6 text-center">Contact Us</h2>
        <form action="contact.php" method="POST" class="space-y-4">
            <input type="text" name="name" placeholder="Your name" class="w-full px-4 py-2 border rounded" required>
            <input type="email" name="email" placeholder="Your email" class="w-full px-4 py-2 border rounded" required>
            <textarea name="message" placeholder="Your message" class="w-full px-4 py-2 border rounded h-32"
                required></textarea>
            <button type="submit" class="w-full py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Send Message
            </button>
        </form>
    </div>
</section>