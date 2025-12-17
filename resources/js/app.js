import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.data('ingredientCheckboxes', () => ({
    checkedIngredients: [],

    isChecked(ingredientId) {
        return this.checkedIngredients.includes(String(ingredientId));
    }
}));

Alpine.data('servingsMultiplier', () => ({
    multiplier: 1,
    originalServings: 0,

    // Computed property for adjusted servings count
    scaledServings() {
        return Math.round(this.originalServings * this.multiplier);
    },

    // Scale individual ingredient quantity
    scaleQuantity(originalQuantity) {
        if (!originalQuantity) return null;
        const scaled = parseFloat(originalQuantity) * this.multiplier;
        return this.formatQuantity(scaled);
    },

    // Format quantity (remove trailing zeros)
    formatQuantity(value) {
        if (value === null) return null;
        let formatted = value.toFixed(3);
        formatted = formatted.replace(/\.?0+$/, '');
        return formatted;
    },

    // Set multiplier with validation
    setMultiplier(value) {
        const numValue = parseFloat(value);
        if (isNaN(numValue)) return;
        this.multiplier = Math.max(0.25, Math.min(10, numValue));
    }
}));

// Combined component for recipe show page (includes both checkboxes and multiplier)
Alpine.data('recipeShowPage', () => ({
    // From ingredientCheckboxes
    checkedIngredients: [],

    isChecked(ingredientId) {
        return this.checkedIngredients.includes(String(ingredientId));
    },

    // From servingsMultiplier
    multiplier: 1,
    originalServings: 0,

    scaledServings() {
        return Math.round(this.originalServings * this.multiplier);
    },

    scaleQuantity(originalQuantity) {
        if (!originalQuantity) return null;
        const scaled = parseFloat(originalQuantity) * this.multiplier;
        return this.formatQuantity(scaled);
    },

    formatQuantity(value) {
        if (value === null) return null;
        let formatted = value.toFixed(3);
        formatted = formatted.replace(/\.?0+$/, '');
        return formatted;
    },

    setMultiplier(value) {
        const numValue = parseFloat(value);
        if (isNaN(numValue)) return;
        this.multiplier = Math.max(0.25, Math.min(10, numValue));
    }
}));

Livewire.start();
