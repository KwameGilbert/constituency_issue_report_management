<section class="py-8 md:py-16 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col md:flex-row gap-8 md:gap-12">
            <!-- Left side content -->
            <div class="w-full md:w-1/2">
                <h2 class="text-3xl md:text-4xl font-bold mb-4 md:mb-6 text-gray-800">Get in Touch</h2>
                <p class="text-gray-600 mb-4 text-base md:text-lg">We'd love to hear from you. Whether you have a
                    question about our
                    services, pricing, or anything else, our team is ready to answer all your questions.</p>
                <p class="text-gray-600 mb-4">
                    📍 123 Business Street<br>
                    📱 (555) 123-4567<br>
                    ✉️ contact@example.com
                </p>
            </div>

            <!-- Right side form -->
            <div class="w-full md:w-1/2">
                <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST"
                        class="space-y-4 md:space-y-6">
                        <div class="space-y-2">
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" id="name" name="name"
                                class="w-full px-3 py-2 md:px-4 md:py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                required>
                        </div>

                        <div class="space-y-2">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" id="email" name="email"
                                class="w-full px-3 py-2 md:px-4 md:py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                required>
                        </div>

                        <div class="space-y-2">
                            <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                            <textarea id="message" name="message" rows="4"
                                class="w-full px-3 py-2 md:px-4 md:py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors resize-none"
                                required></textarea>
                        </div>

                        <button type="submit"
                            class="w-full py-2 md:py-3 px-4 md:px-6 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>