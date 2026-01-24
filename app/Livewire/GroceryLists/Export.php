<?php

namespace App\Livewire\GroceryLists;

use App\Models\GroceryList;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;

class Export extends Component
{
    use AuthorizesRequests;

    public GroceryList $groceryList;

    public function mount(GroceryList $groceryList)
    {
        // Check if user owns this grocery list
        $this->authorize('view', $groceryList);

        $this->groceryList = $groceryList;
    }

    /**
     * Export grocery list as PDF
     */
    public function exportPdf()
    {
        $this->authorize('view', $this->groceryList);

        // Load grocery list with items grouped by category
        $this->groceryList->load(['groceryItems' => function ($query) {
            $query->orderBy('category')->orderBy('sort_order')->orderBy('name');
        }]);

        // Group items by category
        $itemsByCategory = $this->groceryList->groceryItems->groupBy(function ($item) {
            return $item->category->value;
        });

        // Generate PDF
        $pdf = Pdf::loadView('grocery-lists.pdf', [
            'groceryList' => $this->groceryList,
            'itemsByCategory' => $itemsByCategory,
        ]);

        // Generate filename
        $filename = $this->sanitizeFilename($this->groceryList->name).'.pdf';

        // Return PDF download
        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Export grocery list as plain text
     */
    public function exportText()
    {
        $this->authorize('view', $this->groceryList);

        // Load grocery list with items grouped by category
        $this->groceryList->load(['groceryItems' => function ($query) {
            $query->orderBy('category')->orderBy('sort_order')->orderBy('name');
        }]);

        // Group items by category
        $itemsByCategory = $this->groceryList->groceryItems->groupBy(function ($item) {
            return $item->category->value;
        });

        // Generate plain text content
        $content = $this->generatePlainText($itemsByCategory);

        // Generate filename
        $filename = $this->sanitizeFilename($this->groceryList->name).'.txt';

        // Return text download
        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            $filename,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    /**
     * Generate plain text format with markdown-style checkboxes
     */
    protected function generatePlainText($itemsByCategory): string
    {
        $text = '';

        // Header
        $text .= "# {$this->groceryList->name}\n\n";

        // Generated date
        if ($this->groceryList->generated_at) {
            $text .= "Generated: {$this->groceryList->generated_at->format('F j, Y')}\n";
        }

        if ($this->groceryList->regenerated_at) {
            $text .= "Last updated: {$this->groceryList->regenerated_at->format('F j, Y')}\n";
        }

        $text .= "\n";

        // Source
        if ($this->groceryList->is_meal_plan_linked) {
            $text .= "Source: {$this->groceryList->mealPlan->name}\n\n";
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
        $totalItems = $this->groceryList->total_items;
        $completedItems = $this->groceryList->completed_items;
        $text .= str_repeat('-', 50)."\n\n";
        $text .= "Total: {$totalItems} items | Completed: {$completedItems} ({$this->groceryList->completion_percentage}%)\n";

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

    public function render()
    {
        // This component is primarily for export actions, not rendering a view
        // But we need a render method for Livewire
        return view('livewire.grocery-lists.export');
    }
}
