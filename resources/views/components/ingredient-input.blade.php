@props(['index', 'ingredient', 'showRemove' => true])

<div class="flex flex-col gap-2 p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg md:flex-row md:items-start" wire:key="ingredient-{{ $index }}">
    <div class="grid grid-cols-2 gap-2 md:flex-1 md:grid-cols-6">
        {{-- Ingredient Name --}}
        <div class="col-span-2">
            <flux:input
                wire:model="ingredients.{{ $index }}.ingredient_name"
                placeholder="e.g., Flour"
                id="ingredient_name_{{ $index }}"
            />
            @error("ingredients.{$index}.ingredient_name")
                <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
            @enderror
        </div>

        {{-- Quantity --}}
        <div class="col-span-1">
            <flux:input
                wire:model="ingredients.{{ $index }}.quantity"
                type="text"
                inputmode="decimal"
                placeholder="Qty"
                id="quantity_{{ $index }}"
            />
            @error("ingredients.{$index}.quantity")
                <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
            @enderror
        </div>

        {{-- Unit --}}
        <div class="col-span-1">
            <flux:select wire:model="ingredients.{{ $index }}.unit" id="unit_{{ $index }}">
                <option value="">Unit</option>
                <optgroup label="Volume">
                    <option value="tsp">tsp</option>
                    <option value="tbsp">tbsp</option>
                    <option value="fl_oz">fl oz</option>
                    <option value="cup">cup</option>
                    <option value="pint">pint</option>
                    <option value="quart">quart</option>
                    <option value="gallon">gallon</option>
                    <option value="ml">ml</option>
                    <option value="liter">liter</option>
                </optgroup>
                <optgroup label="Weight">
                    <option value="oz">oz</option>
                    <option value="lb">lb</option>
                    <option value="gram">gram</option>
                    <option value="kg">kg</option>
                </optgroup>
                <optgroup label="Count">
                    <option value="whole">whole</option>
                    <option value="clove">clove</option>
                    <option value="slice">slice</option>
                    <option value="piece">piece</option>
                </optgroup>
                <optgroup label="Other">
                    <option value="pinch">pinch</option>
                    <option value="dash">dash</option>
                    <option value="to_taste">to taste</option>
                </optgroup>
            </flux:select>
            @error("ingredients.{$index}.unit")
                <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
            @enderror
        </div>

        {{-- Notes (optional) --}}
        <div class="col-span-2">
            <flux:input
                wire:model="ingredients.{{ $index }}.notes"
                placeholder="Notes (e.g., finely chopped)"
                id="notes_{{ $index }}"
            />
        </div>
    </div>

    {{-- Remove Button --}}
    @if($showRemove)
        <flux:button
            type="button"
            wire:click="removeIngredient({{ $index }})"
            variant="ghost"
            size="sm"
            icon="trash"
            class="self-end md:self-auto text-red-600 hover:text-red-800"
        />
    @endif
</div>
