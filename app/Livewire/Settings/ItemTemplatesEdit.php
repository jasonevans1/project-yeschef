<?php

namespace App\Livewire\Settings;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Models\UserItemTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ItemTemplatesEdit extends Component
{
    use AuthorizesRequests;

    public ?int $template = null;

    protected ?UserItemTemplate $templateModel = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string')]
    public string $category = '';

    #[Validate('required|string')]
    public string $unit = '';

    #[Validate('nullable|numeric|min:0')]
    public ?float $default_quantity = 1;

    public function mount(): void
    {
        if ($this->template) {
            $this->templateModel = UserItemTemplate::findOrFail($this->template);
            $this->authorize('update', $this->templateModel);

            $this->name = $this->templateModel->name;
            $this->category = $this->templateModel->category->value;
            $this->unit = $this->templateModel->unit->value;
            $this->default_quantity = $this->templateModel->default_quantity;
        }
    }

    public function save(): void
    {
        $this->validate();

        // Ensure templateModel is loaded if template ID exists
        if ($this->template && ! $this->templateModel) {
            $this->templateModel = UserItemTemplate::findOrFail($this->template);
        }

        if ($this->templateModel) {
            $this->authorize('update', $this->templateModel);

            $this->templateModel->update([
                'name' => $this->name,
                'category' => IngredientCategory::from($this->category),
                'unit' => MeasurementUnit::from($this->unit),
                'default_quantity' => $this->default_quantity,
            ]);

            // Invalidate autocomplete cache
            Cache::forget("user_item_templates_{$this->templateModel->user_id}");

            session()->flash('message', 'Item template updated successfully.');
        } else {
            UserItemTemplate::create([
                'user_id' => auth()->id(),
                'name' => $this->name,
                'category' => IngredientCategory::from($this->category),
                'unit' => MeasurementUnit::from($this->unit),
                'default_quantity' => $this->default_quantity ?? 1,
                'usage_count' => 0,
                'last_used_at' => now(),
            ]);

            // Invalidate autocomplete cache
            Cache::forget('user_item_templates_'.auth()->id());

            session()->flash('message', 'Item template created successfully.');
        }

        $this->redirect(route('settings.item-templates'));
    }

    public function render()
    {
        return view('livewire.settings.item-templates-edit', [
            'categories' => IngredientCategory::cases(),
            'units' => MeasurementUnit::cases(),
        ]);
    }
}
