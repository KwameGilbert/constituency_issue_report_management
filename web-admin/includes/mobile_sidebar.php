 <!-- Mobile sidebar (hidden by default) -->
 <div id="mobile-sidebar" class="fixed inset-0 z-40 hidden">
     <div class="absolute inset-0 bg-gray-600 opacity-75" id="mobile-sidebar-backdrop"></div>

     <div class="absolute inset-y-0 left-0 max-w-xs w-full bg-gray-800 text-white">
         <div class="flex items-center justify-between p-4 border-b border-gray-700">
             <div class="flex items-center space-x-2">
                 <img src="/assets/images/coat-of-arms.png" alt="Logo" class="h-8 w-8">
                 <h1 class="text-xl font-bold">Admin Panel</h1>
             </div>
             <button id="close-sidebar" class="text-white focus:outline-none">
                 <i class="fas fa-times"></i>
             </button>
         </div>

         <nav class="mt-5 px-2">
             <ul class="space-y-2">
                 <li>
                     <a href="/web-admin/dashboard/"
                         class="flex items-center space-x-2 p-2 rounded-md bg-blue-600 text-white">
                         <i class="fas fa-tachometer-alt w-6"></i>
                         <span>Dashboard</span>
                     </a>
                 </li>
                 <li>
                     <a href="/web-admin/modules/blog/"
                         class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-700">
                         <i class="fas fa-newspaper w-6"></i>
                         <span>Blog Posts</span>
                     </a>
                 </li>
                 <li>
                     <a href="/web-admin/modules/events/"
                         class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-700">
                         <i class="fas fa-calendar-alt w-6"></i>
                         <span>Events</span>
                     </a>
                 </li>
                 <li>
                     <a href="/web-admin/modules/carousel/"
                         class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-700">
                         <i class="fas fa-images w-6"></i>
                         <span>Carousel</span>
                     </a>
                 </li>
                 <li>
                     <a href="/web-admin/modules/profile/"
                         class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-700">
                         <i class="fas fa-user-cog w-6"></i>
                         <span>Profile</span>
                     </a>
                 </li>
                 <li>
                     <a href="/web-admin/logout.php"
                         class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-700 text-red-400">
                         <i class="fas fa-sign-out-alt w-6"></i>
                         <span>Logout</span>
                     </a>
                 </li>
             </ul>
         </nav>
     </div>
 </div>


 <script>
// Mobile menu toggle
const mobileMenuButton = document.getElementById('mobile-menu-button');
const mobileSidebar = document.getElementById('mobile-sidebar');
const closeSidebar = document.getElementById('close-sidebar');
const backdrop = document.getElementById('mobile-sidebar-backdrop');

function toggleMobileMenu() {
    mobileSidebar.classList.toggle('hidden');
}

mobileMenuButton.addEventListener('click', toggleMobileMenu);
closeSidebar.addEventListener('click', toggleMobileMenu);
backdrop.addEventListener('click', toggleMobileMenu);
 </script>