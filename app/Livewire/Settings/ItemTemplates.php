<?php

namespace App\Livewire\Settings;

use App\Models\UserItemTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class ItemTemplates extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function delete(int $templateId): void
    {
        $template = UserItemTemplate::findOrFail($templateId);

        $this->authorize('delete', $template);

        $userId = $template->user_id;

        $template->delete();

        // Invalidate autocomplete cache for this user
        Cache::forget("user_item_templates_{$userId}");

        session()->flash('message', 'Item template deleted successfully.');
    }

    public function render()
    {
        $templates = UserItemTemplate::where('user_id', auth()->id())
            ->orderBy('usage_count', 'desc')
            ->orderBy('last_used_at', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.settings.item-templates', [
            'templates' => $templates,
        ]);
    }
}
