<nav class="bg-gray-800 p-4">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Logo or Brand Name -->
        <div class="text-white font-bold text-xl">
            <a href="{{ url('/') }}">Oro Integrated Cooperative</a>
        </div>

        <!-- Menu Items -->
        <div class="hidden md:flex space-x-4">
            <a href="{{ route('posts.index') }}" class="text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">Home</a>
            <a href="{{ route('about') }}" class="text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">About</a>
            <a href="{{ route('services') }}" class="text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">Services</a>
            <a href="{{ route('contact') }}" class="text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">Contact</a>
        </div>

        <!-- Mobile Menu Button -->
        <div class="md:hidden">
            <button id="mobile-menu-button" class="text-white">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="md:hidden" id="mobile-menu" style="display: none;">
        <div class="space-y-1 px-2 pt-2 pb-3 sm:px-3">
            <a href="{{ route('home') }}" class="text-white block px-3 py-2 rounded-md text-base font-medium">Home</a>
            <a href="{{ route('about') }}" class="text-white block px-3 py-2 rounded-md text-base font-medium">About</a>
            <a href="{{ route('services') }}" class="text-white block px-3 py-2 rounded-md text-base font-medium">Services</a>
            <a href="{{ route('contact') }}" class="text-white block px-3 py-2 rounded-md text-base font-medium">Contact</a>
        </div>
    </div>
</nav>

<script>
    // Toggle the mobile menu
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.style.display = mobileMenu.style.display === 'none' ? 'block' : 'none';
    });
</script>
