@php
    $state = [
        'name' => old('name', $brand->name),
        'tagline' => old('tagline', (string) $brand->tagline),
        'email' => old('support_email', $brand->supportEmail),
        'logoLight' => $brand->logoLightUrl,
        'logoDark' => $brand->logoDarkUrl,
        'favicon' => $favicon ? route('brand.icon', 180) : null,
    ];
@endphp
<x-admin-layout title="Settings">
    <div class="pf-page" x-data="brandingForm(@js(['name' => $brand->name, 'tagline' => (string) $brand->tagline, 'email' => $brand->supportEmail, 'logoLight' => $brand->logoLightUrl, 'logoDark' => $brand->logoDarkUrl, 'favicon' => $favicon ? route('brand.icon', 180).'?v='.$brand->version : null]), @js(['name' => old('name'), 'tagline' => old('tagline'), 'email' => old('support_email')]))">
        @include('admin.settings._tabs')

        @if (session('status'))
            <p class="pf-notice" role="status">{{ session('status') }}</p>
        @endif

        <div class="pf-split pf-brand-split">
            <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" class="pf-split-list pf-brand-form" id="branding-form">
                @csrf
                @method('PUT')

                <div class="pf-panel-head">
                    <p class="pf-label">Branding</p>
                    <span class="pf-chip" x-show="dirty" x-cloak>Unsaved changes</span>
                </div>

                <section class="pf-panel pf-form" aria-labelledby="br-id">
                    <div class="pf-panel-head"><p class="pf-label" id="br-id">Identity</p></div>
                    <x-field name="name" id="br-name" label="Platform name" x-model="name" :value="$state['name']" help="1 to 40 characters. No HTML." maxlength="40" />
                    <x-field name="tagline" id="br-tagline" label="Tagline" x-model="tagline" :value="$state['tagline']" help="Optional, up to 80 characters." maxlength="80" />
                    <x-field name="support_email" id="br-email" label="Support email" type="email" x-model="email" :value="$state['email']" help="Shown in the footer, Terms, Privacy and emails." />
                </section>

                <section class="pf-panel pf-form" aria-labelledby="br-logos">
                    <div class="pf-panel-head"><p class="pf-label" id="br-logos">Logos</p></div>
                    <p class="pf-note">PNG, WebP or JPG, up to 1 MB, at least 240 px wide. A transparent background is best. SVG is not accepted.</p>

                    <x-field name="logo_light" id="br-logo-light" label="Logo for light backgrounds" type="file" accept="image/png,image/webp,image/jpeg" x-on:change="pick($event, 'logoLight', 'removeLight')" />
                    <label class="pf-check"><input type="checkbox" name="remove_logo_light" value="1" x-model="removeLight" x-on:change="if (removeLight) logoLight = null"> Remove this logo</label>

                    <x-field name="logo_dark" id="br-logo-dark" label="Logo for dark backgrounds" type="file" accept="image/png,image/webp,image/jpeg" x-on:change="pick($event, 'logoDark', 'removeDark')" />
                    <label class="pf-check"><input type="checkbox" name="remove_logo_dark" value="1" x-model="removeDark" x-on:change="if (removeDark) logoDark = null"> Remove this logo</label>
                    <p class="pf-note">With no dark logo, dark areas show the name in white. The light logo is never put on black.</p>
                </section>

                <section class="pf-panel pf-form" aria-labelledby="br-fav">
                    <div class="pf-panel-head"><p class="pf-label" id="br-fav">Favicon</p></div>
                    <p class="pf-note">A square PNG, at least 512 px, up to 512 KB. Every icon size is made for you.</p>
                    <x-field name="favicon" id="br-favicon" label="Favicon" type="file" accept="image/png" x-on:change="pick($event, 'favicon', 'removeFavicon')" />
                    <label class="pf-check"><input type="checkbox" name="remove_favicon" value="1" x-model="removeFavicon" x-on:change="if (removeFavicon) favicon = null"> Remove this favicon</label>
                </section>

                <div class="pf-actions">
                    <button type="submit" class="pf-btn">Save changes</button>
                    <a class="pf-btn pf-btn-quiet" href="{{ route('admin.settings.branding') }}">Discard</a>
                </div>
            </form>

            <aside class="pf-split-detail pf-brand-preview" aria-label="Live preview">
                <section class="pf-panel">
                    <div class="pf-panel-head"><p class="pf-label">Live preview</p></div>

                    <p class="pf-label">Browser tab</p>
                    <div class="pf-pv-tab">
                        <template x-if="favicon"><img :src="favicon" alt="" width="16" height="16"></template>
                        <template x-if="!favicon"><span class="pf-pv-dot" aria-hidden="true"></span></template>
                        <span x-text="'Overview · ' + shownName"></span>
                    </div>

                    <p class="pf-label">Black navigation</p>
                    <div class="pf-pv-nav">
                        <template x-if="logoDark"><img :src="logoDark" :alt="shownName" height="24"></template>
                        <template x-if="!logoDark"><span class="pf-pv-word" x-text="shownName"></span></template>
                    </div>

                    <p class="pf-label">White navigation</p>
                    <div class="pf-pv-nav pf-pv-light">
                        <template x-if="logoLight"><img :src="logoLight" :alt="shownName" height="24"></template>
                        <template x-if="!logoLight"><span class="pf-pv-word" x-text="shownName"></span></template>
                    </div>

                    <p class="pf-label">Email header</p>
                    <div class="pf-pv-mail">
                        <p class="pf-mono" x-text="'From: ' + shownName"></p>
                        <p class="pf-note" x-text="tagline"></p>
                    </div>

                    <p class="pf-label">Terms</p>
                    <p class="pf-text">Questions about these terms go to <span x-text="email"></span>. <span x-text="shownName"></span> replies in writing within 10 business days.</p>

                    <p class="pf-label">Favicon sizes</p>
                    <div class="pf-pv-icons">
                        <template x-if="favicon"><img :src="favicon" alt="16 px icon" width="16" height="16"></template>
                        <template x-if="favicon"><img :src="favicon" alt="180 px icon" width="64" height="64"></template>
                        <template x-if="!favicon"><span class="pf-note">The built-in mark is used.</span></template>
                    </div>
                </section>
            </aside>
        </div>

        <section class="pf-panel" aria-labelledby="br-reset">
            <div class="pf-panel-head"><p class="pf-label" id="br-reset">Reset to defaults</p></div>
            <form method="POST" action="{{ route('admin.settings.branding.reset') }}">
                @csrf
                <x-inline-confirm trigger="Reset to defaults" title="Reset the branding" confirm="Reset">
                    <p class="pf-text">The name, tagline and support email go back to the defaults, and the uploaded logos and favicon are deleted.</p>
                </x-inline-confirm>
            </form>
        </section>
    </div>
</x-admin-layout>
