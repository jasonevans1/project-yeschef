<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        <flux:radio.group
            x-data="{
                appearance: localStorage.getItem('flux.appearance') || 'system',
                select(value) {
                    this.appearance = value;
                    window.dispatchEvent(new CustomEvent('appearance-change', { detail: value }));
                }
            }"
            variant="segmented"
        >
            <flux:radio value="light" icon="sun" ::checked="appearance === 'light'" @click="select('light')">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon" ::checked="appearance === 'dark'" @click="select('dark')">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop" ::checked="appearance === 'system'" @click="select('system')">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>
