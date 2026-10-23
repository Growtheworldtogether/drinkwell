<x-cc.adm title="Settings" heading="Email">
    @include('admin.settings._tabs')

    <div class="cc-panel">
        <h2>Developer code needed</h2>
        <p>The email settings control how every code and notice reaches members. Enter the developer code to open them.</p>
        <form method="POST" action="{{ route('admin.settings.email.code') }}" class="cc-form" autocomplete="off">
            @csrf
            <x-cc.field name="developer_code" label="Developer code" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false" required autofocus help="Stays open for 30 minutes." />
            <button type="submit" class="af-btn af-btn-black" style="width:auto">Open email settings</button>
        </form>
    </div>
</x-cc.adm>
