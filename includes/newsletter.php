<section class="py-8 w-full bg-gray-50">
    <div class="w-full bg-white p-6 rounded-lg shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:space-x-8">
            <!-- Left side: Text content -->
            <div class="md:w-1/2 mb-5 md:mb-0">
                <h3 class="text-2xl font-bold mb-2 text-gray-800">Stay Updated</h3>
                <p class="text-gray-600 mb-2">Join our newsletter to receive the latest updates and promotions.</p>
                <p class="text-xs text-gray-500">We respect your privacy. Unsubscribe at any time.</p>
            </div>

            <!-- Right side: Form -->
            <div class="md:w-1/2">
                <form action="subscribe.php" method="POST" class="space-y-3">
                    <div>
                        <label for="email" class="text-sm font-medium text-gray-700 mb-1 block">Email address</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200"
                            required>
                    </div>

                    <button type="submit"
                        class="w-full py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-all duration-200">
                        Subscribe
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 inline" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>