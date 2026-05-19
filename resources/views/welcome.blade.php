<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Promo') }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f8fb;
            --panel: #ffffff;
            --text: #151922;
            --muted: #667085;
            --line: #d9dee8;
            --accent: #0f766e;
            --accent-soft: #e6f5f3;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px;
            background:
                linear-gradient(135deg, rgba(15, 118, 110, .08), transparent 34%),
                var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        main {
            width: min(100%, 760px);
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: clamp(28px, 5vw, 56px);
            box-shadow: 0 24px 70px rgba(21, 25, 34, .08);
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 14px;
            font-weight: 700;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--accent);
        }

        h1 {
            margin: 24px 0 14px;
            font-size: clamp(42px, 8vw, 72px);
            line-height: 1;
            letter-spacing: 0;
        }

        p {
            margin: 0;
            max-width: 620px;
            color: var(--muted);
            font-size: clamp(17px, 2.5vw, 20px);
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <main>
        <div class="status"><span class="dot"></span> Laravel is running</div>
        <h1>Promo</h1>
        <p>
            The Promo project has been installed successfully and is ready for the next stage of development.
        </p>
    </main>
</body>
</html>
