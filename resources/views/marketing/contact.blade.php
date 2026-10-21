<x-marketing-layout title="Contact us">
    <section class="cc-pagehead" data-bg="amber">
        <div class="cc-wrap">
            <x-crumb>Contact us</x-crumb>
            <h1 class="cc-title1">Contact us</h1>
            <p class="cc-lead">Write to us in your own words. We usually reply within 1 business day.</p>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap cc-contact">
            <div class="cc-panel">
                @if (session('status'))
                    <x-cc.notice>{{ session('status') }}</x-cc.notice>
                @endif
                <h2>Send a message</h2>
                <form method="POST" action="{{ route('contact.send') }}" class="cc-form">
                    @csrf
                    <x-form.guard />
                    <div class="cc-two">
                        <x-cc.field name="name" label="Your name" :value="old('name', auth()->user()?->name)" required autocomplete="name" />
                        <x-cc.field name="email" label="Your email" type="email" :value="old('email', auth()->user()?->email)" required autocomplete="email" />
                    </div>
                    <x-cc.field name="topic" label="What is it about?" type="select" required>
                        <option value="">Choose one</option>
                        @foreach ($topics as $t)
                            <option value="{{ $t }}" @selected(old('topic') === $t)>{{ $t }}</option>
                        @endforeach
                    </x-cc.field>
                    <div class="cc-field">
                        <label for="f-message">Your message</label>
                        <textarea id="f-message" name="message" rows="6" required maxlength="2000" @class(['is-bad' => $errors->has('message')])>{{ old('message') }}</textarea>
                        @error('message')<p class="cc-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="af-btn af-btn-black">Send message</button>
                </form>
            </div>

            <aside class="cc-panel">
                <h2>Other ways to reach us</h2>
                <dl class="cc-dl">
                    <div class="cc-kv"><dt>Email</dt><dd><a href="mailto:{{ $brand->supportEmail }}" class="break-anywhere"><strong>{{ $brand->supportEmail }}</strong></a></dd></div>
                    <div class="cc-kv"><dt>Reply time</dt><dd>Within 1 business day</dd></div>
                </dl>
                <p class="cc-hint" style="margin-top:12px">Please never send your bank account number or password by email. We will never ask for them.</p>
                <p style="margin-top:16px"><a href="{{ route('questions') }}"><strong>Read the questions first</strong></a>, they may already have your answer.</p>
            </aside>
        </div>
    </section>
    <x-related :links="[['questions', 'Read the questions'], ['program', 'How it works']]" />
</x-marketing-layout>
