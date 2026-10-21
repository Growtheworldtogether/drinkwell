<x-cc.adm title="Machines">
    <div class="cc-panel">
        <div class="cc-tablewrap">
            <table class="cc-tbl is-stack">
                <caption class="sr-only">Machines</caption>
                <thead><tr><th>Serial</th><th>Facility</th><th>Option</th><th>Stock</th><th>State</th><th>Assignment</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach ($machines as $m)
                        <tr>
                            <td data-label="Serial"><a class="cc-link" href="{{ route('admin.machines.show', $m) }}">{{ $m->serial_no }}</a></td>
                            <td data-label="Facility">{{ $m->facility_name }}, {{ $m->city }}</td>
                            <td data-label="Option">{{ $m->tier->label() }}</td>
                            <td data-label="Stock">{{ $m->stock }} / {{ $m->capacity }}</td>
                            <td data-label="State">{{ $m->hardware_state->label() }}</td>
                            <td data-label="Assignment">{{ $m->assignment_status->label() }}</td>
                            <td data-label="Date">{{ $m->operating_date->toDateString() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-cc.adm>
