import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.data('ingredientCheckboxes', () => ({
    checkedIngredients: [],

    isChecked(ingredientId) {
        return this.checkedIngredients.includes(String(ingredientId));
    }
}));

Livewire.start();
