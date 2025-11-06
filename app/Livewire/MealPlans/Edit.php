<?php

namespace App\Livewire\MealPlans;

use App\Models\MealPlan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Edit extends Component
{
    use AuthorizesRequests;

    public MealPlan $mealPlan;

    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    #[Validate('required|date')]
    public string $start_date = '';

    #[Validate('required|date|after_or_equal:start_date')]
    public string $end_date = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public function mount(MealPlan $mealPlan)
    {
        $this->authorize('update', $mealPlan);

        $this->mealPlan = $mealPlan;
        $this->name = $mealPlan->name;
        $this->start_date = $mealPlan->start_date->format('Y-m-d');
        $this->end_date = $mealPlan->end_date->format('Y-m-d');
        $this->description = $mealPlan->description ?? '';
    }

    public function update()
    {
        $this->validate();

        // Custom validation: max 28 days duration
        $start = \Carbon\Carbon::parse($this->start_date);
        $end = \Carbon\Carbon::parse($this->end_date);

        if ($start->diffInDays($end) > 28) {
            $this->addError('end_date', 'The meal plan duration cannot exceed 28 days.');

            return;
        }

        $this->mealPlan->update([
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'description' => $this->description,
        ]);

        session()->flash('success', 'Meal plan updated successfully!');

        return redirect()->route('meal-plans.show', $this->mealPlan);
    }

    public function render()
    {
        return view('livewire.meal-plans.edit');
    }
}
