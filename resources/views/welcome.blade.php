<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YesChef - Plan your meals, simplify your shopping</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100">
    <!-- Hero Section -->
    <section class="min-h-screen flex flex-col items-center justify-center px-6 py-12">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Logo -->
            <div class="mb-8 flex justify-center">
                <x-app-logo-icon class="size-24 fill-current text-zinc-900 dark:text-zinc-100" />
            </div>

            <!-- Heading -->
            <h1 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-zinc-900 to-zinc-600 dark:from-zinc-100 dark:to-zinc-400 bg-clip-text text-transparent">
                Plan your meals,<br>simplify your shopping
            </h1>

            <!-- Subtitle -->
            <p class="text-xl md:text-2xl text-zinc-600 dark:text-zinc-400 mb-12 max-w-2xl mx-auto">
                YesChef helps you organize recipes, plan your weekly meals, and generate smart grocery lists—all in one place.
            </p>

            <!-- CTAs -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 rounded-lg hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-lg hover:shadow-xl">
                    Get Started
                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 size-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-800 border-2 border-zinc-200 dark:border-zinc-700 rounded-lg hover:border-zinc-300 dark:hover:border-zinc-600 transition-colors">
                    Log In
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-6 bg-zinc-50 dark:bg-zinc-800/50">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16">
                Everything you need for meal planning
            </h2>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Feature 1: Recipe Management -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
                    <div class="size-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Recipe Management</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        Import recipes from any URL or create your own. Organize ingredients with precise measurements and easily adjust serving sizes.
                    </p>
                </div>

                <!-- Feature 2: Meal Planning -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
                    <div class="size-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Meal Planning</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        Plan your week by assigning recipes to specific dates and meal types. Adjust servings per meal and add custom notes for variations.
                    </p>
                </div>

                <!-- Feature 3: Smart Grocery Lists -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
                    <div class="size-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Smart Grocery Lists</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        Auto-generate shopping lists from your meal plans with intelligent autocomplete. Track purchases and organize items by category.
                    </p>
                </div>

                <!-- Feature 4: Share & Export -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
                    <div class="size-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Share & Export</h3>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        Share grocery lists with family members via links or export them as PDF for easy printing. Keep everyone on the same page.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 px-6 text-center text-zinc-600 dark:text-zinc-400">
        <p>&copy; {{ date('Y') }} YesChef. All rights reserved.</p>
    </footer>
</body>
</html>
