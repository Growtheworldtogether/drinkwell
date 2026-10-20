<x-app-layout title="Profile">
    <div class="pf-page">
        <div class="grid max-w-xl gap-4">
            <x-panel title="Profile information">
                @include('portal.profile.partials.update-profile-information-form')
            </x-panel>

            <x-panel title="Update password">
                @include('portal.profile.partials.update-password-form')
            </x-panel>

            <x-panel title="Delete account">
                @include('portal.profile.partials.delete-user-form')
            </x-panel>
        </div>
    </div>
</x-app-layout>
