@php
    $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { margin: 0; background: #f6f7f9; color: #172033; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .wrap { max-width: 920px; margin: 0 auto; padding: 32px 18px; }
        .card { background: #ffffff; border: 1px solid #e6e8ee; border-radius: 14px; overflow: hidden; }
        .header { background: #1f2937; color: #ffffff; padding: 24px; }
        .header h1 { margin: 0 0 8px; font-size: 22px; line-height: 1.3; }
        .header p { margin: 0; color: #d1d5db; }
        .section { padding: 22px 24px; border-top: 1px solid #e6e8ee; }
        .section h2 { margin: 0 0 14px; font-size: 15px; text-transform: uppercase; letter-spacing: .05em; color: #4b5563; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta th { width: 150px; padding: 8px 12px 8px 0; color: #6b7280; font-weight: 600; text-align: left; vertical-align: top; }
        .meta td { padding: 8px 0; word-break: break-word; }
        pre { margin: 0; padding: 14px; overflow-x: auto; background: #111827; color: #f9fafb; border-radius: 10px; font-size: 12px; line-height: 1.55; }
        .trace { margin: 0; padding: 0; list-style: none; }
        .trace li { padding: 12px 0; border-top: 1px solid #eef0f4; }
        .trace li:first-child { border-top: 0; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; padding: 4px 8px; background: #fee2e2; color: #991b1b; border-radius: 999px; font-size: 12px; font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="header">
            <h1>{{ $exception['short_class'] }}</h1>
            <p>{{ $exception['message'] ?: 'No exception message.' }}</p>
        </div>

        <div class="section">
            <h2>Exception</h2>
            <table class="meta">
                <tr>
                    <th>Class</th>
                    <td><span class="badge">{{ $exception['class'] }}</span></td>
                </tr>
                <tr>
                    <th>File</th>
                    <td>{{ $exception['file'] }}:{{ $exception['line'] }}</td>
                </tr>
                <tr>
                    <th>Code</th>
                    <td>{{ $exception['code'] }}</td>
                </tr>
            </table>
        </div>

        @if (! empty($request))
            <div class="section">
                <h2>Request</h2>
                <table class="meta">
                    @foreach ($request as $key => $value)
                        @continue(in_array($key, ['inputs', 'headers'], true))
                        <tr>
                            <th>{{ str_replace('_', ' ', ucfirst($key)) }}</th>
                            <td>{{ is_scalar($value) || is_null($value) ? $value : json_encode($value, $jsonOptions) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        @if (! empty($request['inputs']))
            <div class="section">
                <h2>Inputs</h2>
                <pre>{{ json_encode($request['inputs'], $jsonOptions) }}</pre>
            </div>
        @endif

        @if (! empty($request['headers']))
            <div class="section">
                <h2>Headers</h2>
                <pre>{{ json_encode($request['headers'], $jsonOptions) }}</pre>
            </div>
        @endif

        @if (! empty($exception['previous']))
            <div class="section">
                <h2>Previous Exception</h2>
                <table class="meta">
                    <tr>
                        <th>Class</th>
                        <td>{{ $exception['previous']['class'] }}</td>
                    </tr>
                    <tr>
                        <th>Message</th>
                        <td>{{ $exception['previous']['message'] }}</td>
                    </tr>
                    <tr>
                        <th>File</th>
                        <td>{{ $exception['previous']['file'] }}:{{ $exception['previous']['line'] }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <div class="section">
            <h2>Trace</h2>
            <ol class="trace">
                @forelse ($exception['trace'] as $frame)
                    <li>
                        <strong>{{ $frame['call'] ?: 'unknown call' }}</strong><br>
                        <span class="muted">{{ $frame['file'] ?? 'unknown file' }}{{ $frame['line'] ? ':' . $frame['line'] : '' }}</span>
                    </li>
                @empty
                    <li class="muted">No trace frames available.</li>
                @endforelse
            </ol>
        </div>
    </div>
</div>
</body>
</html>
