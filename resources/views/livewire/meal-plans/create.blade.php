<div>
    <div class="max-w-2xl mx-auto">
        <flux:heading size="xl" level="1" class="mb-6">Create Meal Plan</flux:heading>

        <div class="bg-white rounded-lg shadow p-6">
            <form wire:submit="save" class="space-y-6">
                {{-- Name Field --}}
                <flux:input
                    wire:model="name"
                    label="Meal Plan Name"
                    id="name"
                    name="name"
                    placeholder="e.g., Week of Oct 14"
                    required
                />

                {{-- Date Range Fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input
                        wire:model="start_date"
                        label="Start Date"
                        type="date"
                        id="start_date"
                        name="start_date"
                        required
                    />

                    <flux:field>
                        <flux:label>End Date</flux:label>
                        <flux:input
                            wire:model="end_date"
                            type="date"
                            id="end_date"
                            name="end_date"
                            required
                        />
                        <flux:description>Maximum 28 days</flux:description>
                        <flux:error name="end_date" />
                    </flux:field>
                </div>

                {{-- Description Field --}}
                <flux:field>
                    <flux:label>Description (Optional)</flux:label>
                    <flux:textarea
                        wire:model="description"
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Add any notes about this meal plan..."
                    />
                    <flux:error name="description" />
                </flux:field>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3">
                    <flux:button
                        href="{{ route('meal-plans.index') }}"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        Create Meal Plan
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
