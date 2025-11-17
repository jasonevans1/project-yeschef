{{-- Share Dialog Modal (US8 - T132) --}}
<flux:modal wire:model="showShareDialog" class="max-w-md">
    <flux:heading size="lg" class="mb-4">Share Grocery List</flux:heading>

    @if($groceryList->is_shared)
        <div class="space-y-4" x-data="{ copied: false }">
            <div>
                <flux:text class="text-sm font-medium text-gray-700 mb-2">Shareable Link</flux:text>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        readonly
                        value="{{ $groceryList->share_url }}"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-text select-all"
                        id="shareLink"
                        x-ref="shareLink"
                        @click="$refs.shareLink.select()"
                    />
                    <flux:button
                        variant="primary"
                        size="sm"
                        @click="
                            navigator.clipboard.writeText($refs.shareLink.value).then(() => {
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            }).catch(() => {
                                // Fallback for older browsers
                                $refs.shareLink.select();
                                document.execCommand('copy');
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            });
                        "
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Copied!
                        </span>
                    </flux:button>
                </div>
            </div>

            @if($groceryList->share_expires_at)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <flux:text class="text-sm font-medium text-blue-900">Link Expiration</flux:text>
                            <flux:text class="text-sm text-blue-700 mt-1">
                                Expires {{ $groceryList->share_expires_at->diffForHumans() }}
                                <br>
                                <span class="text-xs">({{ $groceryList->share_expires_at->format('F j, Y \a\t g:i A') }})</span>
                            </flux:text>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-gray-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <flux:text class="text-xs text-gray-600">
                        Anyone with this link can view your grocery list. The list is read-only and viewers cannot make changes.
                    </flux:text>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <flux:button
                    wire:click="revokeShare"
                    wire:confirm="Are you sure you want to revoke share access? The current link will stop working and cannot be recovered."
                    variant="ghost"
                    size="sm"
                >
                    Revoke Access
                </flux:button>
                <flux:button wire:click="closeShareDialog" variant="primary">
                    Close
                </flux:button>
            </div>
        </div>
    @else
        <div class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    <div>
                        <flux:text class="text-sm font-medium text-blue-900 mb-1">Share Your List</flux:text>
                        <flux:text class="text-sm text-blue-700">
                            Generate a shareable link that others can use to view this grocery list. The link will expire in 7 days.
                        </flux:text>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:button wire:click="closeShareDialog" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="share" variant="primary">
                    Generate Link
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>
