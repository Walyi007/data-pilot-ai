<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /* Placeholder for Tailwind CSS - in development, Vite will handle this */
            </style>
        @endif
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col">
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <img class="h-8 w-auto" src="{{ asset('logo.svg') }}" alt="DataPilot AI">
                            </div>
                            <div class="hidden md:block">
                                <div class="ml-10 flex items-baseline space-x-4">
                                    @auth
                                        <a href="{{ url('/dataset/import') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">{{ __('Import Data') }}</a>
                                        <a href="{{ url('/dataset/clean') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">{{ __('Clean Data') }}</a>
                                        <a href="{{ url('/dataset/analytics') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">{{ __('Analytics') }}</a>
                                        <a href="{{ url('/profile') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900">{{ __('Profile') }}</a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                        <div class="hidden md:block">
                            @auth
                                <div class="ml-4 flex items-baseline space-x-4">
                                    <a href="{{ url('/profile) }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700">{{ Auth::user()->name }}</a>
                                    <form action="{{ route('logout') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="underline text-sm text-gray-500 hover:text-gray-700">{{ __('Logout') }}</button>
                                    </form>
                                </div>
                            @else
                                <div class="ml-4">
                                    <a href="{{ route('login') }}" class="underline text-sm text-gray-500 hover:text-gray-700 mr-4">{{ __('Login') }}</a>
                                    <a href="{{ route('register') }}" class="underline text-sm text-gray-500 hover:text-gray-700">{{ __('Register') }}</a>
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
                    @yield('content')
                </div>
            </main>
        </div>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            <script>
                // Initialize dropdowns for Bootstrap 5
                document.addEventListener('DOMContentLoaded', function() {
                    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
                    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                        return new bootstrap.Dropdown(dropdownToggleEl)
                    })
                })
            </script>
        @endif
    </body>
</html>