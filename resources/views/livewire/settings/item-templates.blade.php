<x-settings.layout
    heading="My Item Templates"
    subheading="Manage your grocery item autocomplete templates"
    :fullWidth="true"
>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <flux:button
            href="{{ route('settings.item-templates.create') }}"
            variant="primary"
            class="w-full sm:w-auto"
        >
            <flux:icon.plus class="size-4 mr-1" />
            Create Template
        </flux:button>
    </div>

    @if(session('message'))
        <flux:callout variant="success" class="mb-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    @if($templates->isEmpty())
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 max-w-2xl">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <flux:heading size="lg" class="mt-4">No item templates yet</flux:heading>
                <flux:text class="mt-2">Create templates to customize your autocomplete suggestions</flux:text>
                <div class="mt-4">
                    <flux:button href="{{ route('settings.item-templates.create') }}" variant="primary">
                        Create Your First Template
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700" data-test="template-list">
                <thead class="bg-gray-50 dark:bg-zinc-950">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                            Item Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                            Unit
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                            Usage Count
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                            Last Used
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-700">
                    @foreach($templates as $template)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:text class="font-medium">{{ $template->name }}</flux:text>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:badge>{{ $template->category->label() }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:text>{{ $template->unit->label() }}</flux:text>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:text>{{ $template->usage_count }}</flux:text>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:text class="text-sm text-gray-600 dark:text-zinc-400">
                                    {{ $template->last_used_at->diffForHumans() }}
                                </flux:text>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button
                                        href="{{ route('settings.item-templates.edit', $template) }}"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        Edit
                                    </flux:button>
                                    <flux:button
                                        wire:click="delete({{ $template->id }})"
                                        wire:confirm="Are you sure you want to delete this template?"
                                        variant="danger"
                                        size="sm"
                                    >
                                        Delete
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $templates->links() }}
        </div>
    @endif
</x-settings.layout>
