<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    <title>@yield('title') — Replisa</title>
    <style>
        :root { --ink: #1a1a2e; --muted: #5b5b73; --line: #e6e6ef; --accent: #16a34a; }
        * { box-sizing: border-box; }
        body {
            margin: 0; color: var(--ink); background: #fafafc;
            font: 16px/1.65 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .wrap { max-width: 760px; margin: 0 auto; padding: 48px 22px 80px; }
        header.site { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 40px; }
        .brand { font-weight: 700; font-size: 20px; letter-spacing: -.01em; text-decoration: none; color: var(--ink); }
        .brand span { color: var(--accent); }
        nav a { color: var(--muted); text-decoration: none; font-size: 14px; margin-left: 18px; }
        nav a:hover { color: var(--ink); }
        h1 { font-size: 30px; line-height: 1.2; letter-spacing: -.02em; margin: 0 0 6px; }
        .updated { color: var(--muted); font-size: 14px; margin-bottom: 36px; }
        h2 { font-size: 19px; margin: 38px 0 10px; letter-spacing: -.01em; }
        h3 { font-size: 16px; margin: 22px 0 6px; }
        p, li { color: #2a2a3c; }
        ul { padding-left: 22px; }
        li { margin: 4px 0; }
        a { color: var(--accent); }
        table { width: 100%; border-collapse: collapse; margin: 14px 0; font-size: 15px; }
        th, td { text-align: left; padding: 8px 10px; border: 1px solid var(--line); vertical-align: top; }
        th { background: #f1f1f6; }
        .ph { background: #fff3cd; color: #7a5b00; padding: 0 5px; border-radius: 3px; font-weight: 600; }
        footer.site { margin-top: 56px; padding-top: 20px; border-top: 1px solid var(--line); color: var(--muted); font-size: 13px; }
        footer.site a { color: var(--muted); }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="site">
            <a class="brand" href="/">Repl<span>i</span>sa</a>
            <nav>
                <a href="/privacy">Privacy</a>
                <a href="/termini">Condizioni d'uso</a>
                <a href="/eliminazione-dati">Eliminazione dati</a>
            </nav>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="site">
            <p>Replisa — Automazione WhatsApp per PMI · <a href="https://replisa.com">replisa.com</a></p>
        </footer>
    </div>
</body>
</html>
