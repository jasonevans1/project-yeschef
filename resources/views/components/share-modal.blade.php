@props(['model', 'title' => 'Share'])

<flux:modal wire:model="showShareModal" class="max-w-md">
    <flux:heading size="lg" class="mb-4">{{ $title }}</flux:heading>

    <form wire:submit="shareWith">
        <div class="space-y-4">
            <flux:field>
                <flux:label>Email Address *</flux:label>
                <flux:input
                    wire:model="shareEmail"
                    type="email"
                    placeholder="user@example.com"
                    required
                />
                <flux:description>Enter the email of the person you want to share with</flux:description>
                <flux:error name="shareEmail" />
            </flux:field>

            <flux:field>
                <flux:label>Permission *</flux:label>
                <flux:select wire:model="sharePermission" required>
                    <option value="read">Read Only</option>
                    <option value="write">Read & Write</option>
                </flux:select>
                <flux:description>Read Only allows viewing; Read & Write also allows editing</flux:description>
                <flux:error name="sharePermission" />
            </flux:field>
        </div>

        <div class="flex gap-2 justify-end mt-6">
            <flux:button type="button" wire:click="closeShareModal" variant="ghost">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary">
                <span wire:loading.remove wire:target="shareWith">Share</span>
                <span wire:loading wire:target="shareWith">Sharing...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
