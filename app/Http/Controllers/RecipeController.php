<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RecipeController extends Controller
{
    use AuthorizesRequests;

    /**
     * Remove the specified recipe from storage.
     */
    public function destroy(Recipe $recipe)
    {
        $this->authorize('delete', $recipe);

        try {
            $recipe->delete();

            session()->flash('success', 'Recipe deleted successfully.');

            return redirect()->route('recipes.index');
        } catch (QueryException $e) {
            // Foreign key constraint violation - recipe is in use
            session()->flash('error', 'This recipe cannot be deleted because it is assigned to one or more meal plans. Please remove it from all meal plans first.');

            return redirect()->route('recipes.show', $recipe);
        }
    }
}
