<x-auth-page title="Create your account" intro="It takes about a minute." bg="mint">
    <form method="POST" action="{{ route('register') }}" class="cc-form">
        @csrf
        <x-form.guard />

        <x-cc.field name="reference_code" label="Reference code" :value="old('reference_code', request('code'))" required autocomplete="off" placeholder="DW-XXXX-XXXX" help="You need a reference code to join. Ask the person who invited you." />
        <x-cc.field name="name" label="Full name" :value="old('name')" required autofocus autocomplete="name" />
        <x-cc.field name="email" label="Email" type="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
        <x-cc.field name="password" label="Password" type="password" required autocomplete="new-password" help="At least 10 characters, with letters and numbers." />
        <x-cc.field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

        <button type="submit" class="af-btn af-btn-black">Create account</button>
        <div class="cc-links"><span>Already have an account?</span><a href="{{ route('login') }}">Sign in</a></div>
    </form>
</x-auth-page>
