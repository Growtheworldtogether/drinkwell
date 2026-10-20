@props(['machine'])
{{-- Demo controls. Each button is its own POST so the server decides what is allowed (SimulationController). --}}
<div {{ $attributes->class(['dw-toolbar']) }} data-sim-toolbar>
    <p>Simulation toolbar (demo only)</p>
    <form method="POST" action="{{ route('sim.advance-day', $machine) }}">
        @csrf
        <button type="submit" class="pf-btn">Fast-forward 24 hours</button>
    </form>
    <form method="POST" action="{{ route('sim.sales-burst', $machine) }}">
        @csrf
        <button type="submit" class="pf-btn" data-needs-stock @disabled($machine->stock === 0)>Trigger sales burst ({{ config('drinkwell.sales_burst_units') }} drinks)</button>
    </form>
    <form method="POST" action="{{ route('sim.driver-restock', $machine) }}">
        @csrf
        <button type="submit" class="pf-btn" data-needs-empty @disabled($machine->stock > 0)>Driver restock</button>
    </form>
    @if (session('sim_error'))
        <span class="pf-note" role="alert">{{ session('sim_error') }}</span>
    @endif
</div>
