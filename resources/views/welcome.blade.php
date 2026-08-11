<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'TaskFlow API') }} — Multi-tenant Laravel SaaS backend</title>
        <meta name="description" content="A multi-tenant SaaS backend for project & task management, built on Laravel 12 with Sanctum auth, Policy-based authorization, and Spatie roles.">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        <style>
            :root {
                --ink: #0b0e13;
                --surface: #12171f;
                --surface-2: #171d27;
                --line: #232b36;
                --fg: #e9edf2;
                --fg-dim: #8b96a5;
                --fg-faint: #57616f;
                --amber: #f2b544;
                --teal: #5fc9c0;
            }

            * { box-sizing: border-box; }

            html, body {
                margin: 0;
                padding: 0;
                background: var(--ink);
                color: var(--fg);
                font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                background-image:
                    linear-gradient(180deg, rgba(242,181,68,0.05), transparent 40%),
                    radial-gradient(circle at 15% 0%, rgba(95,201,192,0.06), transparent 45%);
            }

            main {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2.25rem 1.5rem;
            }

            .container { width: 100%; max-width: 760px; }

            h1 {
                font-family: 'IBM Plex Mono', ui-monospace, monospace;
                font-weight: 700;
                font-size: clamp(2.2rem, 5vw, 3.1rem);
                letter-spacing: -0.02em;
                line-height: 1.05;
                margin: 0 0 0.85rem;
            }

            h1 .dot { color: var(--amber); }

            .tagline {
                font-size: 1.15rem;
                color: var(--fg);
                line-height: 1.5;
                max-width: 60ch;
                margin: 0 0 0.6rem;
            }

            .subtext {
                font-size: 0.98rem;
                color: var(--fg-dim);
                line-height: 1.6;
                max-width: 62ch;
                margin: 0 0 1.6rem;
            }

            .subtext strong { color: var(--fg); font-weight: 500; }

            .badges {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 1.7rem;
            }

            .badge {
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.78rem;
                color: var(--fg-dim);
                border: 1px solid var(--line);
                background: var(--surface);
                border-radius: 4px;
                padding: 0.3rem 0.6rem;
                white-space: nowrap;
            }

            .badge b { color: var(--teal); font-weight: 600; }

            .terminal {
                border: 1px solid var(--line);
                background: var(--surface);
                border-radius: 8px;
                overflow: hidden;
                margin-bottom: 1.3rem;
                box-shadow: 0 20px 60px -30px rgba(0,0,0,0.7);
            }

            .terminal-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.55rem 0.9rem;
                border-bottom: 1px solid var(--line);
                background: var(--surface-2);
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.72rem;
                color: var(--fg-faint);
            }

            .terminal pre {
                margin: 0;
                padding: 1.1rem 1.2rem 1.3rem;
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.83rem;
                line-height: 1.65;
                color: var(--fg-dim);
                overflow-x: auto;
            }

            .terminal pre .prompt { color: var(--teal); }
            .terminal pre .path { color: var(--fg); }
            .terminal pre .out { color: var(--fg-faint); }
            .terminal pre .node { color: var(--fg); }
            .terminal pre .edge { color: var(--amber); }

            .notes {
                list-style: none;
                margin: 0;
                padding: 0;
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.82rem;
                line-height: 1.75;
                color: var(--fg-dim);
            }

            .notes li::before { content: '# '; color: var(--fg-faint); }
            .notes b { color: var(--fg); font-weight: 500; }

            footer {
                border-top: 1px solid var(--line);
                padding: 1.1rem 1.5rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 0.6rem;
                font-family: 'IBM Plex Mono', monospace;
                font-size: 0.78rem;
                color: var(--fg-faint);
            }

            footer a {
                color: var(--fg-dim);
                text-decoration: none;
                border-bottom: 1px solid var(--line);
                padding-bottom: 1px;
                transition: color 0.15s ease, border-color 0.15s ease;
            }

            footer a:hover { color: var(--amber); border-color: var(--amber); }
            footer a:focus-visible { outline: 2px solid var(--amber); outline-offset: 3px; border-radius: 2px; }

            @media (max-width: 560px) {
                .terminal pre { font-size: 0.76rem; }
            }
        </style>
    </head>
    <body>
        <main>
            <div class="container">
                <h1>TaskFlow API<span class="dot">.</span></h1>

                <p class="tagline">A multi-tenant SaaS backend for project &amp; task management, built on Laravel 12.</p>
                <p class="subtext">Each company gets an isolated workspace to manage projects and tasks. <strong>API-only</strong>, Sanctum-authenticated, with authorization modeled as a real Laravel Policy instead of scattered checks — no scaffolding magic, no black boxes.</p>

                <div class="badges" aria-label="Tech stack">
                    <span class="badge"><b>laravel</b> 12</span>
                    <span class="badge">sanctum</span>
                    <span class="badge">spatie/laravel-permission</span>
                    <span class="badge">sqlite</span>
                    <span class="badge">rest api</span>
                </div>

                <div class="terminal">
                    <div class="terminal-bar">
                        <span>bash — ~/taskflow-api</span>
                        <span>tenant tree</span>
                    </div>
                    <pre><span class="prompt">$</span> <span class="path">php artisan tinker</span>
<span class="out">&gt;&gt;&gt; $company-&gt;projects</span>

<span class="node">Company</span>                       <span class="out"># the tenant</span>
 <span class="edge">├──</span> <span class="node">User</span>              <span class="out"># admin · manager · developer</span>
 <span class="edge">└──</span> <span class="node">Project</span>
      <span class="edge">└──</span> <span class="node">Task</span> <span class="edge">──assigned_to──&gt;</span> <span class="node">User</span></pre>
                </div>

                <ul class="notes">
                    <li><b>tenant isolation</b> — every query chains explicitly through auth()->user()->company</li>
                    <li><b>policy authorization</b> — TaskPolicy governs who can view, update, assign, delete</li>
                    <li><b>role-based access</b> — admin/manager see all company tasks, developer sees their own</li>
                </ul>
            </div>
        </main>

        <footer>
            <span>&copy; {{ date('Y') }} taskflow-api — portfolio project</span>
            <a href="https://github.com/jsoftsol/TaskFlow-API" target="_blank" rel="noopener noreferrer">View source on GitHub →</a>
        </footer>
    </body>
</html>
