<x-admin-layout title="Integrity">
    <div class="pf-page">
        <form method="POST" action="{{ route('admin.integrity.check') }}">@csrf<button type="submit" class="pf-btn">Run all checks now</button></form>
        <section class="pf-panel mt-4" aria-label="Checks">
            <dl class="pf-rows">
                @foreach ($descriptions as $id => $text)
                    <div>
                        <dt>{{ $text }}</dt>
                        <dd>
                            @if ($results === null)
                                {{ $last ? (in_array($id, $last['failed'], true) ? 'Failed' : 'Passed') : 'Not run' }}
                            @elseif ($results[$id] === [])
                                Passed
                            @else
                                Failed
                            @endif
                        </dd>
                    </div>
                    @if ($results !== null && $results[$id] !== [])
                        <div><dt>Problems</dt><dd class="pf-mono">{{ implode(' ', array_slice($results[$id], 0, 10)) }}</dd></div>
                    @endif
                @endforeach
            </dl>
        </section>
    </div>
</x-admin-layout>
