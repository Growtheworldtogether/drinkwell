@props(['machine'])
{{-- Demo controls for one machine, admin and owner only. Each button is its own POST so the server decides what is allowed. --}}
<div {{ $attributes->class(['cc-demo']) }} data-sim-toolbar>
    <p>Demo tools (admin only)</p>
    <div class="btns cc-btns">
        <form method="POST" action="{{ route('sim.advance-day', $machine) }}">@csrf<button type="submit" class="af-btn af-btn-black">Fast-forward 24 hours</button></form>
        <form method="POST" action="{{ route('sim.sales-burst', $machine) }}">@csrf<button type="submit" class="af-btn cc-btn-outline" data-needs-stock @disabled($machine->stock === 0)>Sales burst</button></form>
        <form method="POST" action="{{ route('sim.driver-restock', $machine) }}">@csrf<button type="submit" class="af-btn cc-btn-outline" data-needs-empty @disabled($machine->stock > 0)>Driver restock</button></form>
    </div>
</div>
