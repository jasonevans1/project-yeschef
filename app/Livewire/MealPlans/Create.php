<?php

namespace App\Livewire\MealPlans;

use App\Models\MealPlan;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Create extends Component
{
    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    #[Validate('required|date|after_or_equal:today')]
    public string $start_date = '';

    #[Validate('required|date|after_or_equal:start_date')]
    public string $end_date = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public function mount()
    {
        // Set default dates to make UX better
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addDays(6)->format('Y-m-d');
    }

    public function save()
    {
        $this->validate();

        // Custom validation: max 28 days duration
        $start = \Carbon\Carbon::parse($this->start_date);
        $end = \Carbon\Carbon::parse($this->end_date);

        if ($start->diffInDays($end) > 28) {
            $this->addError('end_date', 'The meal plan duration cannot exceed 28 days.');
            return;
        }

        $mealPlan = auth()->user()->mealPlans()->create([
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'description' => $this->description,
        ]);

        session()->flash('success', 'Meal plan created successfully!');

        return redirect()->route('meal-plans.show', $mealPlan);
    }

    public function render()
    {
        return view('livewire.meal-plans.create');
    }
}
