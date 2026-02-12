<?php

namespace App\Livewire\Settings;

use App\Enums\ShareableType;
use App\Enums\SharePermission;
use App\Models\ContentShare;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Sharing extends Component
{
    #[Validate('required|email|max:255')]
    public string $shareAllEmail = '';

    #[Validate('required|in:recipe,meal_plan,grocery_list')]
    public string $shareAllType = '';

    #[Validate('required|in:read,write')]
    public string $shareAllPermission = 'read';

    public function shareAll(): void
    {
        $this->validate();

        // Prevent self-sharing
        if ($this->shareAllEmail === auth()->user()->email) {
            $this->addError('shareAllEmail', 'You cannot share with yourself.');

            return;
        }

        // Map type string to ShareableType enum
        $shareableType = match ($this->shareAllType) {
            'recipe' => ShareableType::Recipe,
            'meal_plan' => ShareableType::MealPlan,
            'grocery_list' => ShareableType::GroceryList,
        };

        $recipient = User::where('email', $this->shareAllEmail)->first();

        // Manual upsert since NULL shareable_id won't match in SQL WHERE
        $existing = ContentShare::where('owner_id', auth()->id())
            ->where('recipient_email', $this->shareAllEmail)
            ->where('shareable_type', $shareableType->value)
            ->whereNull('shareable_id')
            ->first();

        if ($existing) {
            $existing->update([
                'recipient_id' => $recipient?->id,
                'permission' => SharePermission::from($this->shareAllPermission),
            ]);
        } else {
            ContentShare::create([
                'owner_id' => auth()->id(),
                'recipient_id' => $recipient?->id,
                'recipient_email' => $this->shareAllEmail,
                'shareable_type' => $shareableType->value,
                'shareable_id' => null,
                'permission' => SharePermission::from($this->shareAllPermission),
                'share_all' => true,
            ]);
        }

        session()->flash('success', "Shared all {$shareableType->label()}s with {$this->shareAllEmail}");

        $this->reset(['shareAllEmail', 'shareAllType', 'shareAllPermission']);
        $this->shareAllPermission = 'read';
    }

    public function updatePermission(int $shareId, string $newPermission): void
    {
        if (! in_array($newPermission, ['read', 'write'])) {
            $this->addError('permission', 'Invalid permission value.');

            return;
        }

        $share = ContentShare::findOrFail($shareId);

        if ($share->owner_id !== auth()->id()) {
            abort(403);
        }

        $share->update([
            'permission' => SharePermission::from($newPermission),
        ]);
    }

    public function revokeShare(int $shareId): void
    {
        $share = ContentShare::findOrFail($shareId);

        if ($share->owner_id !== auth()->id()) {
            abort(403);
        }

        $share->delete();
    }

    public function render(): View
    {
        $shares = auth()->user()->outgoingShares()
            ->with('recipient', 'shareable')
            ->latest()
            ->get();

        return view('livewire.settings.sharing', [
            'shares' => $shares,
        ]);
    }
}
