<x-cc.adm title="Integrity">
    <div class="cc-panel">
        <p>These checks make sure the money always adds up. Run them any time.</p>
        <form method="POST" action="{{ route('admin.integrity.check') }}">@csrf<button type="submit" class="af-btn af-btn-black" style="width:auto">Run all checks now</button></form>
    </div>
    <div class="cc-panel">
        <dl class="cc-dl">
            @foreach ($descriptions as $id => $text)
                @php
                    $state = $results === null ? ($last ? (in_array($id, $last['failed'], true) ? 'Failed' : 'Passed') : 'Not run') : ($results[$id] === [] ? 'Passed' : 'Failed');
                @endphp
                <div class="cc-kv"><dt>{{ $text }}</dt><dd><span class="cc-tone {{ $state === 'Passed' ? 'ok' : '' }}">{{ $state }}</span></dd></div>
                @if ($results !== null && $results[$id] !== [])
                    <div class="cc-kv"><dt>Problems</dt><dd>{{ implode(' ', array_slice($results[$id], 0, 10)) }}</dd></div>
                @endif
            @endforeach
        </dl>
    </div>
</x-cc.adm>
