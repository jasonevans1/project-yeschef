// Livewire and Alpine are already loaded by @fluxScripts
// We just need to wait for them to be available
// console.log('[APP.JS] Loading...');

document.addEventListener('livewire:init', () => {
    // console.log('[APP.JS] Livewire initialized');

    // reCAPTCHA v3 Form Handler
    window.Alpine.data('recaptchaForm', (action) => ({
        async handleSubmit(event) {
            const form = event.target;
            const siteKey = window.recaptchaSiteKey;

            // Skip if reCAPTCHA not configured
            if (!siteKey || !window.grecaptcha) {
                this.$wire.call(form.getAttribute('wire:submit'));
                return;
            }

            try {
                // Execute reCAPTCHA and get token
                const token = await grecaptcha.execute(siteKey, { action: action });

                // Set token in Livewire component
                this.$wire.recaptcha_token = token;

                // Submit the Livewire form
                this.$wire.call(form.getAttribute('wire:submit'));
            } catch (error) {
                console.error('reCAPTCHA error:', error);

                // Allow form submission anyway (fail open)
                this.$wire.call(form.getAttribute('wire:submit'));
            }
        }
    }));

    window.Alpine.data('ingredientCheckboxes', () => ({
    checkedIngredients: [],

    isChecked(ingredientId) {
        return this.checkedIngredients.includes(String(ingredientId));
    }
    }));

    window.Alpine.data('servingsMultiplier', () => ({
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
    window.Alpine.data('recipeShowPage', () => ({
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

    // Preserve appearance preference during Livewire navigation
    // Use sessionStorage as backup since localStorage keeps getting cleared
    let preservedAppearance = sessionStorage.getItem('_preservedAppearance') || null;

    // Listen for appearance changes from the settings page
    window.addEventListener('appearance-change', (e) => {
        const value = e.detail;
        // console.log('[Appearance] Custom event received:', value);

        // Update preservedAppearance FIRST so the localStorage interceptor doesn't block
        if (value === 'dark') {
            preservedAppearance = 'dark';
            sessionStorage.setItem('_preservedAppearance', 'dark');
        } else if (value === 'light') {
            preservedAppearance = 'light';
            sessionStorage.setItem('_preservedAppearance', 'light');
        } else if (value === 'system') {
            preservedAppearance = null;
            sessionStorage.removeItem('_preservedAppearance');
        }

        const root = document.documentElement;

        if (value === 'dark') {
            root.classList.add('dark');
            localStorage.setItem('flux.appearance', 'dark');
        } else if (value === 'light') {
            root.classList.remove('dark');
            localStorage.setItem('flux.appearance', 'light');
        } else if (value === 'system') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
            localStorage.removeItem('flux.appearance');
        }

        // console.log('[Appearance] Applied from custom event:', value);
    });
    let isNavigating = false;
    let restorationTarget = null;

    // console.log('[APP.JS] Setting up appearance preservation...');
    // console.log('[APP.JS] Initial preserved value from sessionStorage:', preservedAppearance);

    // Immediately restore if we have a preserved value
    if (preservedAppearance && preservedAppearance !== 'system') {
        localStorage.setItem('flux.appearance', preservedAppearance);
        const root = document.documentElement;
        if (preservedAppearance === 'dark') {
            root.classList.add('dark');
        } else if (preservedAppearance === 'light') {
            root.classList.remove('dark');
        }
        // console.log('[APP.JS] Immediately restored on page load:', preservedAppearance);
    }

    // Watch for changes to localStorage and preserve them
    const originalSetItem = localStorage.setItem;
    localStorage.setItem = function(key, value) {
        if (key === 'flux.appearance') {
            // console.log('[Appearance] localStorage.setItem called:', value);

            // Block ALL conflicting writes if we have a different preserved value
            if (preservedAppearance && value !== preservedAppearance && value !== 'system') {
                // console.log('[Appearance] BLOCKED conflicting write. Enforcing preserved:', preservedAppearance);

                // Fix the DOM immediately since the conflicting code might have changed it
                const root = document.documentElement;
                if (preservedAppearance === 'dark') {
                    root.classList.add('dark');
                } else if (preservedAppearance === 'light') {
                    root.classList.remove('dark');
                }

                // Don't update preservedAppearance, keep the current value in localStorage
                return originalSetItem.call(this, key, preservedAppearance);
            }

            sessionStorage.setItem('_preservedAppearance', value);
            preservedAppearance = value;
        }
        return originalSetItem.apply(this, arguments);
    };

    const originalRemoveItem = localStorage.removeItem;
    localStorage.removeItem = function(key) {
        if (key === 'flux.appearance') {
            // console.log('[Appearance] localStorage.removeItem called - BLOCKING (preserving value:', preservedAppearance, ')');
            // Don't actually remove if we have a preserved value
            if (preservedAppearance && preservedAppearance !== 'system') {
                // console.log('[Appearance] Prevented removal - keeping:', preservedAppearance);
                localStorage.setItem('flux.appearance', preservedAppearance);
                return;
            }
        }
        return originalRemoveItem.apply(this, arguments);
    };

    // Save appearance BEFORE navigation
    document.addEventListener('livewire:navigating', (e) => {
        isNavigating = true;
        restorationTarget = preservedAppearance;
        const current = localStorage.getItem('flux.appearance') || preservedAppearance;
        // console.log('[Appearance] Navigating - Current:', current);
    });

    document.addEventListener('livewire:navigated', (e) => {
        // console.log('[Appearance] Navigated - Restoring:', preservedAppearance);

        if (preservedAppearance && preservedAppearance !== 'system') {
            const targetTheme = preservedAppearance;

            // Aggressively force the correct theme multiple times
            const forceTheme = () => {
                const current = localStorage.getItem('flux.appearance');
                if (current !== targetTheme) {
                    // console.log('[Appearance] Forcing correction:', targetTheme, '(was:', current, ')');
                    localStorage.setItem('flux.appearance', targetTheme);
                }

                const root = document.documentElement;
                if (targetTheme === 'dark') {
                    root.classList.add('dark');
                } else {
                    root.classList.remove('dark');
                }
            };

            // Force immediately and repeatedly to override any lingering old component code
            forceTheme();
            setTimeout(forceTheme, 50);
            setTimeout(forceTheme, 100);
            setTimeout(forceTheme, 150);
            setTimeout(forceTheme, 200);
            setTimeout(forceTheme, 300);
            setTimeout(forceTheme, 400);
            setTimeout(forceTheme, 500);

            setTimeout(() => {
                // console.log('[Appearance] Applied theme:', targetTheme);
                isNavigating = false;
                restorationTarget = null;
            }, 600);
        } else {
            setTimeout(() => {
                isNavigating = false;
                restorationTarget = null;
            }, 250);
        }
    });
});

// Note: Livewire is started by @fluxScripts, not here
