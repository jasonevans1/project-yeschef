<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $groceryList->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            margin: 20px;
        }
        h1 {
            font-size: 20pt;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        h2 {
            font-size: 14pt;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #333;
        }
        .header {
            margin-bottom: 20px;
        }
        .metadata {
            font-size: 10pt;
            color: #666;
            margin-bottom: 10px;
        }
        .category {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .item {
            margin-left: 0px;
            margin-bottom: 5px;
            display: flex;
            align-items: flex-start;
        }
        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #000;
            margin-right: 8px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .item-content {
            flex-grow: 1;
        }
        .item-name {
            font-weight: bold;
        }
        .item-quantity {
            color: #666;
            margin-left: 5px;
        }
        .item-notes {
            font-style: italic;
            color: #888;
            margin-left: 5px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 10pt;
            color: #666;
        }
        .separator {
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $groceryList->name }}</h1>

        <div class="metadata">
            @if($groceryList->generated_at)
                <div>Generated: {{ $groceryList->generated_at->format('F j, Y') }}</div>
            @endif

            @if($groceryList->regenerated_at)
                <div>Last Updated: {{ $groceryList->regenerated_at->format('F j, Y') }}</div>
            @endif

            @if($groceryList->is_meal_plan_linked)
                <div>Source: {{ $groceryList->mealPlan->name }}</div>
            @else
                <div>Source: Standalone List</div>
            @endif
        </div>
    </div>

    <div class="separator"></div>

    @forelse($itemsByCategory as $category => $items)
        <div class="category">
            <h2>{{ ucfirst(str_replace('_', ' ', $category)) }}</h2>

            @foreach($items as $item)
                <div class="item">
                    <span class="checkbox">{{ $item->purchased ? '✓' : '' }}</span>
                    <div class="item-content">
                        <span class="item-name">{{ $item->name }}</span>

                        @if($item->quantity !== null)
                            <span class="item-quantity">
                                - {{ $item->quantity }}{{ $item->unit ? ' ' . $item->unit->value : '' }}
                            </span>
                        @endif

                        @if($item->notes)
                            <span class="item-notes">({{ $item->notes }})</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="metadata">
            <p>No items in this list.</p>
        </div>
    @endforelse

    <div class="footer">
        <div>Total Items: {{ $groceryList->total_items }}</div>
        <div>Completed: {{ $groceryList->completed_items }} ({{ $groceryList->completion_percentage }}%)</div>
    </div>
</body>
</html>
