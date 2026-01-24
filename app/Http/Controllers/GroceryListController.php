<?php

namespace App\Http\Controllers;

use App\Models\GroceryList;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;

class GroceryListController extends Controller
{
    use AuthorizesRequests;

    /**
     * Export grocery list as PDF
     */
    public function exportPdf(GroceryList $groceryList)
    {
        // Check if user owns this grocery list
        $this->authorize('view', $groceryList);

        // Load grocery list with items grouped by category
        $groceryList->load(['groceryItems' => function ($query) {
            $query->orderBy('category')->orderBy('sort_order')->orderBy('name');
        }]);

        // Group items by category
        $itemsByCategory = $groceryList->groceryItems->groupBy(function ($item) {
            return $item->category->value;
        });

        // Generate PDF
        $pdf = Pdf::loadView('grocery-lists.pdf', [
            'groceryList' => $groceryList,
            'itemsByCategory' => $itemsByCategory,
        ]);

        // Generate filename
        $filename = $this->sanitizeFilename($groceryList->name).'.pdf';

        // Return PDF download
        return $pdf->download($filename);
    }

    /**
     * Export grocery list as plain text
     */
    public function exportText(GroceryList $groceryList)
    {
        // Check if user owns this grocery list
        $this->authorize('view', $groceryList);

        // Load grocery list with items grouped by category
        $groceryList->load(['groceryItems' => function ($query) {
            $query->orderBy('category')->orderBy('sort_order')->orderBy('name');
        }]);

        // Group items by category
        $itemsByCategory = $groceryList->groceryItems->groupBy(function ($item) {
            return $item->category->value;
        });

        // Generate plain text content
        $content = $this->generatePlainText($groceryList, $itemsByCategory);

        // Generate filename
        $filename = $this->sanitizeFilename($groceryList->name).'.txt';

        // Return text download
        return response($content)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * Generate plain text format with markdown-style checkboxes
     */
    protected function generatePlainText(GroceryList $groceryList, $itemsByCategory): string
    {
        $text = '';

        // Header
        $text .= "# {$groceryList->name}\n\n";

        // Generated date
        if ($groceryList->generated_at) {
            $text .= "Generated: {$groceryList->generated_at->format('F j, Y')}\n";
        }

        if ($groceryList->regenerated_at) {
            $text .= "Last updated: {$groceryList->regenerated_at->format('F j, Y')}\n";
        }

        $text .= "\n";

        // Source
        if ($groceryList->is_meal_plan_linked) {
            $text .= "Source: {$groceryList->mealPlan->name}\n\n";
        } else {
            $text .= "Source: Standalone List\n\n";
        }

        // Separator
        $text .= str_repeat('-', 50)."\n\n";

        // Items grouped by category
        foreach ($itemsByCategory as $category => $items) {
            // Category header
            $categoryLabel = ucfirst(str_replace('_', ' ', $category));
            $text .= "## {$categoryLabel}\n\n";

            foreach ($items as $item) {
                // Checkbox - checked if purchased, unchecked if not
                $checkbox = $item->purchased ? '[X]' : '[ ]';

                // Item name
                $line = "- {$checkbox} {$item->name}";

                // Quantity and unit
                if ($item->quantity !== null) {
                    $line .= " - {$item->quantity}";

                    if ($item->unit !== null) {
                        $line .= " {$item->unit->value}";
                    }
                }

                // Notes
                if ($item->notes) {
                    $line .= " ({$item->notes})";
                }

                $text .= $line."\n";
            }

            $text .= "\n";
        }

        // Footer with item count
        $totalItems = $groceryList->total_items;
        $completedItems = $groceryList->completed_items;
        $text .= str_repeat('-', 50)."\n\n";
        $text .= "Total: {$totalItems} items | Completed: {$completedItems} ({$groceryList->completion_percentage}%)\n";

        return $text;
    }

    /**
     * Sanitize filename for safe download
     */
    protected function sanitizeFilename(string $name): string
    {
        // Remove special characters and replace spaces with hyphens
        $name = Str::slug($name);

        // Limit length
        return Str::limit($name, 50, '');
    }
}
