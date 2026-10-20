<x-admin-layout title="Machines">
    <div class="pf-page">
        <section class="pf-panel" aria-label="Machines">
            <div class="pf-tablewrap">
                <table class="pf-table">
                    <caption class="sr-only">Machines</caption>
                    <thead><tr><th>Serial</th><th>Facility</th><th>Option</th><th>Stock</th><th>State</th><th>Assignment</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach ($machines as $m)
                            <tr>
                                <td data-label="Serial" class="pf-mono"><a class="pf-link" href="{{ route('admin.machines.show', $m) }}">{{ $m->serial_no }}</a></td>
                                <td data-label="Facility">{{ $m->facility_name }}, {{ $m->city }}</td>
                                <td data-label="Option">{{ $m->tier->label() }}</td>
                                <td data-label="Stock" class="pf-mono">{{ $m->stock }} / {{ $m->capacity }}</td>
                                <td data-label="State">{{ $m->hardware_state->label() }}</td>
                                <td data-label="Assignment">{{ $m->assignment_status->label() }}</td>
                                <td data-label="Date" class="pf-mono">{{ $m->operating_date->toDateString() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
