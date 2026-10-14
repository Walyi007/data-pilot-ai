<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'DataPilot AI') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            :root {
                --background: #fafafa;
                --surface: #ffffff;
                --primary: #007aff;
                --primary-dark: #0051d5;
                --text: #1d1d1f;
                --text-secondary: #86868b;
                --border: #e5e5e7;
                --danger: #ff3b30;
                --success: #34c759;
                --radius: 12px;
                --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
                --shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
                --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
                --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: var(--font-sans);
                background-color: var(--background);
                color: var(--text);
                line-height: 1.5;
            }
            a { color: var(--primary); text-decoration: none; }
            a:hover { text-decoration: underline; }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px 0;
                border-bottom: 1px solid var(--border);
            }
            .header .logo {
                font-size: 1.5rem;
                font-weight: 600;
                color: var(--primary);
            }
            .header nav {
                display: flex;
                gap: 20px;
                align-items: center;
            }
            .header nav a {
                font-weight: 500;
                color: var(--text-secondary);
                transition: var(--transition);
            }
            .header nav a:hover,
            .header nav a.active {
                color: var(--primary);
            }
            .header .user-actions {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .header .user-actions a {
                font-size: 0.9rem;
                color: var(--text-secondary);
            }
            .header .user-actions .logout-form {
                display: inline;
            }
            .header .user-actions button {
                background: none;
                border: none;
                color: var(--text-secondary);
                font: inherit;
                cursor: pointer;
                padding: 4px 8px;
                border-radius: 6px;
                transition: var(--transition);
            }
            .header .user-actions button:hover {
                background-color: rgba(0,0,0,0.05);
                color: var(--danger);
            }
            .content {
                min-height: calc(100vh - 200px);
                padding: 30px 0;
            }
            .card {
                background: var(--surface);
                border-radius: var(--radius);
                box-shadow: var(--shadow);
                overflow: hidden;
                margin-bottom: 24px;
                border: 1px solid var(--border);
            }
            .card-header {
                padding: 20px 24px;
                border-bottom: 1px solid var(--border);
                font-weight: 600;
                font-size: 1.25rem;
            }
            .card-body {
                padding: 24px;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                font-weight: 500;
                text-align: center;
                border-radius: var(--radius);
                cursor: pointer;
                transition: var(--transition);
                border: none;
                font-size: 0.95rem;
            }
            .btn-primary {
                background-color: var(--primary);
                color: white;
            }
            .btn-primary:hover {
                background-color: var(--primary-dark);
            }
            .btn-outline {
                background-color: transparent;
                color: var(--primary);
                border: 2px solid var(--primary);
            }
            .btn-outline:hover {
                background-color: var(--primary);
                color: white;
            }
            .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
            .input-group { margin-bottom: 16px; }
            .input-group label {
                display: block;
                margin-bottom: 6px;
                font-weight: 500;
            }
            .input-group input,
            .input-group select,
            .input-group textarea {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid var(--border);
                border-radius: var(--radius);
                font-size: 0.95rem;
                background-color: var(--surface);
                color: var(--text);
                transition: var(--transition);
            }
            .input-group input:focus,
            .input-group select:focus,
            .input-group textarea:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(0,122,255,0.1);
            }
            .table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }
            .table th,
            .table td {
                padding: 12px 16px;
                text-align: left;
                border-bottom: 1px solid var(--border);
            }
            .table th {
                background-color: var(--background);
                font-weight: 600;
                font-size: 0.9rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: var(--text-secondary);
            }
            .table tbody tr:last-child td {
                border-bottom: none;
            }
            .table tbody tr:hover td {
                background-color: rgba(0,0,0,0.02);
            }
            .badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 500;
            }
            .badge-success { background-color: rgba(52,199,89,0.1); color: var(--success); }
            .badge-warning { background-color: rgba(255,149,0,0.1); color: #ff9500; }
            .badge-danger { background-color: rgba(255,59,48,0.1); color: var(--danger); }
            .alert {
                padding: 16px;
                border-radius: var(--radius);
                margin-bottom: 20px;
                border: 1px solid transparent;
            }
            .alert-success { background-color: rgba(52,199,89,0.1); border-color: rgba(52,199,89,0.2); color: var(--success); }
            .alert-error { background-color: rgba(255,59,48,0.1); border-color: rgba(255,59,48,0.2); color: var(--danger); }
            .alert-warning { background-color: rgba(255,149,0,0.1); border-color: rgba(255,149,0,0.2); color: #ff9500; }
            .alert-info { background-color: rgba(0,122,255,0.1); border-color: rgba(0,122,255,0.2); color: var(--primary); }
            .footer {
                text-align: center;
                padding: 30px 0;
                color: var(--text-secondary);
                font-size: 0.9rem;
                border-top: 1px solid var(--border);
                margin-top: auto;
            }
            @media (max-width: 768px) {
                .header { flex-direction: column; gap: 16px; align-items: stretch; }
                .header nav { flex-wrap: wrap; }
                .header .user-actions { margin-top: 10px; align-self: flex-end; }
            }
        </style>
    @endif
</head>
<body>
    <div class="min-h-screen flex flex-col">
        <header class="header">
            <div class="container">
                <div class="logo">DataPilot AI</div>
                <nav>
                    @auth
                        <a href="{{ url('/dataset/import') }}" class="{{ Request::is('dataset/import*') ? 'active' : '' }}">Importer des données</a>
                        <a href="{{ url('/dataset/clean') }}" class="{{ Request::is('dataset/clean*') ? 'active' : '' }}">Nettoyer les données</a>
                        <a href="{{ url('/dataset/analytics') }}" class="{{ Request::is('dataset/analytics*') ? 'active' : '' }}">Profil d'analyse</a>
                    @endauth
                </nav>
                <div class="user-actions">
                    @auth
                        <a href="{{ url('/profile') }}">{{ Auth::user()->name }}</a>
                        <form action="{{ route('logout') }}" method="POST" class="logout-form" style="display: inline;">
                            @csrf
                            <button type="submit">Déconnexion</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">Se connecter</a>
                        <a href="{{ route('register') }}">S'inscrire</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="content">
            <div class="container">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                &copy; {{ now()->year }} DataPilot AI. Tous droits réservés.
            </div>
        </footer>
    </div>
</body>
</html>