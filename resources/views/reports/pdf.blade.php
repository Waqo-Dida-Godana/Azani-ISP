<!DOCTYPE html>
<html><head><meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
    h1 { font-size: 16px; margin: 0; color:#0b3d5c; }
    h2 { font-size: 13px; margin: 4px 0 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background:#0b3d5c; color:#fff; }
    th, td { border: 1px solid #999; padding: 4px; text-align: left; }
    .tot td { font-weight: bold; background: #eee; }
    .foot { margin-top: 12px; color:#666; }
</style></head>
<body>
    <h1>AZANI INTERNET SERVICE PROVIDER</h1>
    <h2>{{ $title }}</h2>
    <table>
        <tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
        @forelse($rows as $r)<tr>@foreach($r as $c)<td>{{ $c }}</td>@endforeach</tr>
        @empty <tr><td colspan="{{ count($headers) }}">No records.</td></tr>
        @endforelse
        @if($totals)<tr class="tot">@foreach($totals as $c)<td>{{ $c }}</td>@endforeach</tr>@endif
    </table>
    <p class="foot">Amounts in KSh. Generated {{ now()->format('d M Y H:i') }}</p>
</body></html>
