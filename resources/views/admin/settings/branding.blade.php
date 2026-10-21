@php
    $state = ['name' => old('name', $brand->name), 'tagline' => old('tagline', (string) $brand->tagline), 'email' => old('support_email', $brand->supportEmail)];
@endphp
<x-cc.adm title="Settings" heading="Branding">
    @include('admin.settings._tabs')
    <div x-data="brandingForm(@js(['name' => $brand->name, 'tagline' => (string) $brand->tagline, 'email' => $brand->supportEmail, 'logoLight' => $brand->logoLightUrl, 'logoDark' => $brand->logoDarkUrl, 'favicon' => $favicon ? route('brand.icon', 180).'?v='.$brand->version : null]), @js(['name' => old('name'), 'tagline' => old('tagline'), 'email' => old('support_email')]))" class="cc-brand">
        <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" id="branding-form" class="cc-form">
            @csrf
            @method('PUT')

            <div class="cc-panel">
                <h2>Identity <span class="cc-tone" x-show="dirty" x-cloak>Unsaved changes</span></h2>
                <x-cc.field name="name" id="br-name" label="Platform name" x-model="name" :value="$state['name']" help="1 to 40 characters. No HTML." maxlength="40" />
                <x-cc.field name="tagline" id="br-tagline" label="Tagline" x-model="tagline" :value="$state['tagline']" help="Optional, up to 80 characters." maxlength="80" />
                <x-cc.field name="support_email" id="br-email" label="Support email" type="email" x-model="email" :value="$state['email']" help="Shown in the footer, Terms, Privacy, the Contact page and emails. Contact form messages are sent here." />
            </div>

            <div class="cc-panel">
                <h2>Logos</h2>
                <p class="cc-hint">PNG, WebP or JPG, up to 1 MB, at least 240 px wide. A transparent background is best. SVG is not accepted.</p>
                <x-cc.field name="logo_light" id="br-logo-light" label="Logo for light backgrounds" type="file" accept="image/png,image/webp,image/jpeg" x-on:change="pick($event, 'logoLight', 'removeLight')" />
                <label class="cc-check"><input type="checkbox" name="remove_logo_light" value="1" x-model="removeLight" x-on:change="if (removeLight) logoLight = null"><span>Remove this logo</span></label>
                <x-cc.field name="logo_dark" id="br-logo-dark" label="Logo for dark backgrounds" type="file" accept="image/png,image/webp,image/jpeg" x-on:change="pick($event, 'logoDark', 'removeDark')" />
                <label class="cc-check"><input type="checkbox" name="remove_logo_dark" value="1" x-model="removeDark" x-on:change="if (removeDark) logoDark = null"><span>Remove this logo</span></label>
                <p class="cc-hint">With no dark logo, dark areas show the name in white. The light logo is never put on black.</p>
            </div>

            <div class="cc-panel">
                <h2>Favicon</h2>
                <p class="cc-hint">A square PNG, at least 512 px, up to 512 KB. Every icon size is made for you.</p>
                <x-cc.field name="favicon" id="br-favicon" label="Favicon" type="file" accept="image/png" x-on:change="pick($event, 'favicon', 'removeFavicon')" />
                <label class="cc-check"><input type="checkbox" name="remove_favicon" value="1" x-model="removeFavicon" x-on:change="if (removeFavicon) favicon = null"><span>Remove this favicon</span></label>
            </div>

            <div class="cc-step-nav">
                <button type="submit" class="af-btn af-btn-black">Save changes</button>
                <a class="af-btn cc-btn-outline" href="{{ route('admin.settings.branding') }}">Discard</a>
            </div>
        </form>

        <aside class="cc-panel" aria-label="Live preview">
            <h2>Live preview</h2>

            <p class="cc-hint">Browser tab</p>
            <div class="cc-pv cc-pv-tab">
                <template x-if="favicon"><img :src="favicon" alt="" width="16" height="16"></template>
                <template x-if="!favicon"><span class="cc-pv-dot" aria-hidden="true"></span></template>
                <span x-text="'Overview · ' + shownName"></span>
            </div>

            <p class="cc-hint">Dark background</p>
            <div class="cc-pv cc-pv-nav">
                <template x-if="logoDark"><img :src="logoDark" :alt="shownName" height="24"></template>
                <template x-if="!logoDark"><span x-text="shownName"></span></template>
            </div>

            <p class="cc-hint">Light background</p>
            <div class="cc-pv cc-pv-nav is-light">
                <template x-if="logoLight"><img :src="logoLight" :alt="shownName" height="24"></template>
                <template x-if="!logoLight"><span x-text="shownName"></span></template>
            </div>

            <p class="cc-hint">Email header</p>
            <div class="cc-pv">
                <strong x-text="'From: ' + shownName"></strong>
                <p class="cc-hint" x-text="tagline"></p>
            </div>

            <p class="cc-hint">Terms</p>
            <p>Questions about these terms go to <span x-text="email" class="break-anywhere"></span>. <span x-text="shownName"></span> replies in writing within 10 business days.</p>

            <p class="cc-hint">Favicon sizes</p>
            <div class="cc-pv-icons">
                <template x-if="favicon"><img :src="favicon" alt="16 px icon" width="16" height="16"></template>
                <template x-if="favicon"><img :src="favicon" alt="180 px icon" width="64" height="64"></template>
                <template x-if="!favicon"><span class="cc-hint">The built-in mark is used.</span></template>
            </div>
        </aside>
    </div>

    <div class="cc-panel cc-danger">
        <h2>Reset to defaults</h2>
        <p>The name, tagline and support email go back to the defaults, and the uploaded logos and favicon are deleted.</p>
        <form method="POST" action="{{ route('admin.settings.branding.reset') }}" x-data="{ open: false }">
            @csrf
            <button type="button" class="af-btn cc-bad" style="width:auto" x-show="!open" x-on:click="open = true">Reset to defaults</button>
            <div x-show="open" x-cloak class="cc-step-nav">
                <button type="submit" class="af-btn cc-bad">Yes, reset it</button>
                <button type="button" class="af-btn cc-btn-outline" x-on:click="open = false">Cancel</button>
            </div>
        </form>
    </div>
</x-cc.adm>
