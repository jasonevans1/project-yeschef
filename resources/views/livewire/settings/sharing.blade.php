<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Sharing" subheading="Share your content with others" :fullWidth="true">
        {{-- Share All Form --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 mb-6">
            <flux:heading size="lg" class="mb-4">Share All Content</flux:heading>
            <flux:text class="mb-4">Share all items of a content type with another user. Future items will be automatically included.</flux:text>

            <form wire:submit="shareAll">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
                    <flux:field>
                        <flux:label>Email Address *</flux:label>
                        <flux:input
                            wire:model="shareAllEmail"
                            type="email"
                            placeholder="user@example.com"
                            required
                        />
                        <flux:error name="shareAllEmail" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Content Type *</flux:label>
                        <flux:select wire:model="shareAllType" required>
                            <option value="">Select type...</option>
                            <option value="recipe">Recipes</option>
                            <option value="meal_plan">Meal Plans</option>
                            <option value="grocery_list">Grocery Lists</option>
                        </flux:select>
                        <flux:error name="shareAllType" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Permission *</flux:label>
                        <flux:select wire:model="shareAllPermission" required>
                            <option value="read">Read Only</option>
                            <option value="write">Read & Write</option>
                        </flux:select>
                        <flux:error name="shareAllPermission" />
                    </flux:field>

                    <div class="flex items-end h-full">
                        <flux:button type="submit" variant="primary" class="w-full sm:w-auto mt-5">
                            <span wire:loading.remove wire:target="shareAll">Share</span>
                            <span wire:loading wire:target="shareAll">Sharing...</span>
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>

        @if(session('success'))
            <flux:callout variant="success" class="mb-6">
                {{ session('success') }}
            </flux:callout>
        @endif

        {{-- Active Shares Table --}}
        @if($shares->isEmpty())
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <flux:heading size="lg" class="mt-4">No active shares</flux:heading>
                    <flux:text class="mt-2">Use the form above to share your content with others</flux:text>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-950">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                Recipient
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                Item
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                Permission
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-700">
                        @foreach($shares as $share)
                            <tr wire:key="share-{{ $share->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        @if($share->recipient)
                                            <flux:text class="font-medium">{{ $share->recipient->name }}</flux:text>
                                        @endif
                                        <flux:text class="text-sm text-gray-500 dark:text-zinc-400">{{ $share->recipient_email }}</flux:text>
                                        @if(!$share->recipient)
                                            <flux:badge variant="outline" size="sm" class="ml-1">Pending</flux:badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:badge>{{ \App\Enums\ShareableType::from($share->shareable_type)->label() }}</flux:badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($share->share_all)
                                        <flux:text class="font-medium">All {{ \App\Enums\ShareableType::from($share->shareable_type)->label() }}s</flux:text>
                                    @elseif($share->shareable)
                                        <flux:text>{{ $share->shareable->name }}</flux:text>
                                    @else
                                        <flux:text class="text-gray-400 dark:text-zinc-500 italic">Deleted</flux:text>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($share->permission === \App\Enums\SharePermission::Write)
                                        <flux:badge variant="warning">Read & Write</flux:badge>
                                    @else
                                        <flux:badge variant="outline">Read Only</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-settings.layout>
</section>
