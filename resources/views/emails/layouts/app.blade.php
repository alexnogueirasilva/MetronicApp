<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'Notificação')</title>
    <script src="https://cdn.tailwindcss.com?plugins=typography,forms"></script>
    <style>
        body {
            background-color: #0f172a; /* slate-900 */
            color: #e2e8f0; /* slate-200 */
            font-family: ui-sans-serif, system-ui, sans-serif;
        }

        .email-container {
            max-width: 480px;
            margin: 3rem auto;
            background-color: #1e293b; /* slate-800 */
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }

        .footer {
            font-size: 0.75rem;
            color: #94a3b8; /* slate-400 */
            margin-top: 2rem;
            text-align: center;
        }

        .logo {
            margin-bottom: 2rem;
        }

        .logo img {
            max-height: 50px;
        }
        
    </style>
</head>
<body>
<div class="email-container">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-white">@yield('heading')</h1>
    </div>

    @yield('content')

    <div class="footer mt-10">
        © {{ now()->year }} DevAction. Todos os direitos reservados.
    </div>
</div>
</body>
</html>
