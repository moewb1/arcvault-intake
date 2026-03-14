<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArcVault Intake Triage</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0b1220;
            --muted: #4b5565;
            --paper: #f6f8fb;
            --surface: #ffffff;
            --surface-soft: #f9fbff;
            --line: #d7dfeb;
            --brand: #0b6dff;
            --brand-deep: #0548ac;
            --teal: #047f72;
            --warning: #a16207;
            --danger: #b42318;
            --ok: #136c3f;
            --shadow-lg: 0 24px 55px rgba(9, 18, 35, 0.1);
            --shadow-sm: 0 8px 20px rgba(9, 18, 35, 0.06);
            --radius-xl: 22px;
            --radius-md: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Sora", "Avenir Next", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 20%, #dbeafe 0, rgba(219, 234, 254, 0) 38%),
                radial-gradient(circle at 92% 10%, #ccfbf1 0, rgba(204, 251, 241, 0) 34%),
                linear-gradient(180deg, #f8fbff 0%, #f4f8fe 100%);
            padding: 2rem 1rem 3rem;
        }

        .shell {
            max-width: 1280px;
            margin: 0 auto;
        }

        .hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .title {
            margin: 0;
            font-size: clamp(1.55rem, 3.4vw, 2.5rem);
            letter-spacing: -0.02em;
            line-height: 1.05;
        }

        .subtitle {
            margin: 0.4rem 0 0;
            color: var(--muted);
            max-width: 72ch;
            font-size: 0.9rem;
        }

        .json-link {
            text-decoration: none;
            color: #083344;
            font-size: 0.84rem;
            font-weight: 600;
            background: #ccfbf1;
            border: 1px solid #99f6e4;
            border-radius: 999px;
            padding: 0.5rem 0.9rem;
            white-space: nowrap;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 1rem;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            padding: 1rem;
            animation: rise 420ms ease-out both;
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .panel h2 {
            margin: 0 0 0.75rem;
            font-size: 1.05rem;
            letter-spacing: -0.01em;
        }

        .flash {
            border-radius: 12px;
            padding: 0.65rem 0.8rem;
            margin-bottom: 0.75rem;
            font-size: 0.88rem;
        }

        .flash.success {
            border: 1px solid #9ae6b4;
            background: #e9fff2;
            color: var(--ok);
        }

        .flash.error {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: var(--danger);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.85rem;
        }

        label {
            display: block;
            margin-bottom: 0.35rem;
            font-size: 0.84rem;
            font-weight: 600;
            color: #334155;
        }

        select,
        textarea,
        input[type="text"] {
            width: 100%;
            border: 1px solid #c7d4e7;
            border-radius: 12px;
            padding: 0.72rem 0.8rem;
            font: inherit;
            color: var(--ink);
            background: #fff;
        }

        textarea {
            min-height: 145px;
            resize: vertical;
        }

        select:focus,
        textarea:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #8eb5f7;
            box-shadow: 0 0 0 3px rgba(11, 109, 255, 0.14);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: 0.75rem;
        }

        button,
        .btn {
            border: none;
            border-radius: 999px;
            cursor: pointer;
            font: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.58rem 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        button:hover,
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .primary {
            background: linear-gradient(110deg, var(--brand), var(--brand-deep));
            color: #fff;
        }

        .secondary {
            background: linear-gradient(110deg, #0f766e, #0b4f56);
            color: #fff;
        }

        .ghost {
            color: #1e3a8a;
            background: #e0eaff;
            border: 1px solid #c7d7fb;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }

        .stat {
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 0.75rem 0.8rem;
            background: var(--surface-soft);
        }

        .stat .k {
            display: block;
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1;
            margin-top: 0.2rem;
        }

        .stat .l {
            color: #526175;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
        }

        .records-panel {
            margin-top: 1rem;
            padding-top: 0.8rem;
        }

        .records-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .results-meta {
            font-size: 0.82rem;
            color: var(--muted);
        }

        .pager {
            margin-top: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .pager-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 0.7rem;
            border-radius: 10px;
            text-decoration: none;
            border: 1px solid #c7d7fb;
            background: #fff;
            color: #24427e;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .pager-link:hover {
            background: #eff4ff;
        }

        .pager-link.active {
            border-color: #8eb5f7;
            background: #e6efff;
            color: #11326f;
        }

        .pager-link.disabled {
            pointer-events: none;
            opacity: 0.45;
        }

        .filters {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr auto auto;
            gap: 0.55rem;
            margin-bottom: 0.75rem;
        }

        .filters .tiny-btn {
            height: 41px;
        }

        .table-wrap {
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: auto;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1120px;
        }

        th,
        td {
            border-bottom: 1px solid #e5eaf2;
            text-align: left;
            vertical-align: top;
            padding: 0.65rem;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8fafe;
            color: #516073;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            font-weight: 700;
        }

        tbody tr:hover {
            background: #f8fbff;
        }

        .is-hidden {
            display: none;
        }

        .tag {
            display: inline-block;
            border-radius: 999px;
            border: 1px solid #bfd5ff;
            background: #eef4ff;
            color: #1d3f89;
            font-size: 0.71rem;
            font-weight: 700;
            padding: 0.17rem 0.48rem;
            white-space: nowrap;
        }

        .tag.high {
            border-color: #f8ce8a;
            background: #fff7ea;
            color: var(--warning);
        }

        .tag.danger {
            border-color: #fecaca;
            background: #fff1f2;
            color: var(--danger);
        }

        .tag.ok {
            border-color: #9ae6b4;
            background: #e9fff2;
            color: var(--ok);
        }

        pre {
            margin: 0;
            font-size: 0.72rem;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: "JetBrains Mono", Menlo, Consolas, monospace;
            max-width: 300px;
            color: #334155;
        }

        .mono {
            font-family: "JetBrains Mono", Menlo, Consolas, monospace;
        }

        @media (max-width: 1080px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 840px) {
            body {
                padding: 1rem 0.65rem 1.4rem;
            }

            .hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .filters .tiny-btn {
                height: 40px;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
<main class="shell">
    <section class="hero">
        <div>
            <h1 class="title">ArcVault Intake Triage</h1>
            <p class="subtitle">AI intake form with triage, routing, escalation, and JSON output.</p>
        </div>
        <a class="json-link" href="{{ route('intake.export') }}" target="_blank" rel="noopener">Open JSON Output</a>
    </section>

    <section class="layout">
        <article class="panel">
            <h2>Submit Request</h2>

            @if (session('status'))
                <div class="flash success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="flash error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('intake.store') }}">
                @csrf
                <div class="form-grid">
                    <div>
                        <label for="source">Source</label>
                        <select id="source" name="source" required>
                            @foreach($sources as $source)
                                <option value="{{ $source }}" @selected(old('source') === $source)>{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="raw_message">Raw Message</label>
                        <textarea id="raw_message" name="raw_message" required>{{ old('raw_message') }}</textarea>
                    </div>
                </div>

                <div class="actions">
                    <button class="primary" type="submit">Process Request</button>
                </div>
            </form>

            <div class="actions">
                <form method="POST" action="{{ route('intake.samples') }}">
                    @csrf
                    <button class="secondary" type="submit">Process 5 Synthetic Samples</button>
                </form>
            </div>
        </article>

        <article class="panel">
            <h2>Quick Snapshot</h2>
            <section class="stats">
                <div class="stat">
                    <span class="l">Total Records</span>
                    <span class="k">{{ $totalRecords }}</span>
                </div>
                <div class="stat">
                    <span class="l">Escalated</span>
                    <span class="k">{{ $escalatedRecords }}</span>
                </div>
                <div class="stat">
                    <span class="l">Avg Confidence</span>
                    <span class="k">{{ $avgConfidence }}%</span>
                </div>
                <div class="stat">
                    <span class="l">Live LLM Rows</span>
                    <span class="k">{{ $liveLlmRecords }}</span>
                </div>
            </section>
        </article>
    </section>

    <section class="panel records-panel">
        <section class="records-head">
            <h2 style="margin: 0;">Processed Records</h2>
            <span class="results-meta">
                Page {{ $records->currentPage() }} of {{ $records->lastPage() }}
                ·
                <span id="resultsCount">{{ $records->count() }}</span> visible on page
            </span>
        </section>

        <section class="filters">
            <input type="text" id="searchInput" placeholder="Search records..." aria-label="Search records">
            <select id="categoryFilter" aria-label="Filter by category">
                <option value="">All Categories</option>
                <option value="Bug Report">Bug Report</option>
                <option value="Feature Request">Feature Request</option>
                <option value="Billing Issue">Billing Issue</option>
                <option value="Technical Question">Technical Question</option>
                <option value="Incident/Outage">Incident/Outage</option>
            </select>
            <select id="queueFilter" aria-label="Filter by queue">
                <option value="">All Queues</option>
                <option value="Engineering">Engineering</option>
                <option value="Product">Product</option>
                <option value="Billing">Billing</option>
                <option value="IT/Security">IT/Security</option>
                <option value="Escalation Queue">Escalation Queue</option>
            </select>
            <button class="ghost tiny-btn" id="escalationOnlyBtn" type="button" data-on="0">Escalations Only</button>
            <button class="ghost tiny-btn" id="resetFiltersBtn" type="button">Reset</button>
        </section>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>Source</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Confidence</th>
                    <th>Queue</th>
                    <th>Escalation</th>
                    <th>Core Issue</th>
                    <th>Identifiers</th>
                    <th>Summary</th>
                    <th>Model</th>
                </tr>
                </thead>
                <tbody id="recordsTbody">
                @forelse($records as $record)
                    @php
                        $rowSearch = strtolower(implode(' ', [
                            $record->source,
                            $record->category,
                            $record->priority,
                            $record->routing_queue,
                            $record->core_issue,
                            $record->human_summary,
                            $record->model_used,
                            $record->raw_message,
                            json_encode($record->identifiers, JSON_UNESCAPED_UNICODE),
                            json_encode($record->escalation_reasons, JSON_UNESCAPED_UNICODE),
                        ]));
                    @endphp
                    <tr
                        data-row="1"
                        data-category="{{ $record->category }}"
                        data-queue="{{ $record->routing_queue }}"
                        data-escalation="{{ $record->escalation_flag ? '1' : '0' }}"
                        data-search="{{ $rowSearch }}"
                    >
                        <td class="mono">{{ $record->id }}</td>
                        <td class="mono">{{ optional($record->processed_at)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $record->source }}</td>
                        <td><span class="tag">{{ $record->category }}</span></td>
                        <td><span class="tag {{ $record->priority === 'High' ? 'high' : '' }}">{{ $record->priority }}</span></td>
                        <td class="mono">{{ $record->confidence_score }}%</td>
                        <td>{{ $record->routing_queue }}</td>
                        <td>
                            @if($record->escalation_flag)
                                <span class="tag danger">Yes</span>
                                <pre>{{ json_encode($record->escalation_reasons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                <span class="tag ok">No</span>
                            @endif
                        </td>
                        <td>{{ $record->core_issue }}</td>
                        <td>
                            <pre>{{ json_encode($record->identifiers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </td>
                        <td>{{ $record->human_summary }}</td>
                        <td class="mono">{{ $record->model_used }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">No records yet. Submit one above or run the sample batch.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            @php
                $startPage = max(1, $records->currentPage() - 2);
                $endPage = min($records->lastPage(), $records->currentPage() + 2);
            @endphp
            <nav class="pager" aria-label="Pagination">
                <a class="pager-link {{ $records->onFirstPage() ? 'disabled' : '' }}" href="{{ $records->previousPageUrl() ?? '#' }}">Prev</a>

                @for ($page = $startPage; $page <= $endPage; $page++)
                    <a class="pager-link {{ $page === $records->currentPage() ? 'active' : '' }}" href="{{ $records->url($page) }}">
                        {{ $page }}
                    </a>
                @endfor

                <a class="pager-link {{ $records->hasMorePages() ? '' : 'disabled' }}" href="{{ $records->nextPageUrl() ?? '#' }}">Next</a>
            </nav>
        @endif
    </section>
</main>

<script>
    (() => {
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const queueFilter = document.getElementById('queueFilter');
        const escalationOnlyBtn = document.getElementById('escalationOnlyBtn');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        const resultsCount = document.getElementById('resultsCount');
        const rows = Array.from(document.querySelectorAll('tr[data-row="1"]'));

        const applyFilters = () => {
            const term = (searchInput.value || '').trim().toLowerCase();
            const category = categoryFilter.value;
            const queue = queueFilter.value;
            const escalationOnly = escalationOnlyBtn.dataset.on === '1';

            let visible = 0;

            rows.forEach((row) => {
                const rowSearch = row.dataset.search || '';
                const rowCategory = row.dataset.category || '';
                const rowQueue = row.dataset.queue || '';
                const rowEscalation = row.dataset.escalation || '0';

                const matchesTerm = term === '' || rowSearch.includes(term);
                const matchesCategory = category === '' || rowCategory === category;
                const matchesQueue = queue === '' || rowQueue === queue;
                const matchesEscalation = !escalationOnly || rowEscalation === '1';

                const show = matchesTerm && matchesCategory && matchesQueue && matchesEscalation;
                row.classList.toggle('is-hidden', !show);
                if (show) {
                    visible += 1;
                }
            });

            resultsCount.textContent = String(visible);
        };

        const toggleEscalationOnly = () => {
            const on = escalationOnlyBtn.dataset.on === '1';
            escalationOnlyBtn.dataset.on = on ? '0' : '1';
            escalationOnlyBtn.textContent = on ? 'Escalations Only' : 'Escalations: ON';
            applyFilters();
        };

        const resetFilters = () => {
            searchInput.value = '';
            categoryFilter.value = '';
            queueFilter.value = '';
            escalationOnlyBtn.dataset.on = '0';
            escalationOnlyBtn.textContent = 'Escalations Only';
            applyFilters();
        };

        searchInput.addEventListener('input', applyFilters);
        categoryFilter.addEventListener('change', applyFilters);
        queueFilter.addEventListener('change', applyFilters);
        escalationOnlyBtn.addEventListener('click', toggleEscalationOnly);
        resetFiltersBtn.addEventListener('click', resetFilters);
    })();
</script>
</body>
</html>
