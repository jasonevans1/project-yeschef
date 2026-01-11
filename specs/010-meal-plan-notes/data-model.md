# Data Model: Meal Plan Notes

**Feature Branch**: `010-meal-plan-notes`
**Date**: 2026-01-11

## Entity: MealPlanNote

### Description

Represents a free-form note within a meal plan, associated with a specific date and meal type. Notes provide flexibility for users to record meal-related information that isn't tied to a recipe (e.g., "Eating out", "Leftovers", "Fasting day").

### Fields

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, auto-increment | Unique identifier |
| meal_plan_id | bigint | FK, NOT NULL, CASCADE on delete | Reference to parent meal plan |
| date | date | NOT NULL | The date within the meal plan |
| meal_type | enum | NOT NULL | MealType enum (breakfast, lunch, dinner, snack) |
| title | varchar(255) | NOT NULL | The note title (displayed in calendar) |
| details | text | NULLABLE | Optional extended details |
| created_at | timestamp | auto | Record creation timestamp |
| updated_at | timestamp | auto | Record last update timestamp |

### Indexes

| Index Name | Columns | Type | Purpose |
|------------|---------|------|---------|
| PRIMARY | id | Primary | Unique identification |
| meal_plan_notes_meal_plan_id_date_index | meal_plan_id, date | Composite | Efficient queries by meal plan and date |

### Foreign Keys

| Column | References | On Delete | On Update |
|--------|------------|-----------|-----------|
| meal_plan_id | meal_plans.id | CASCADE | RESTRICT |

### Relationships

```text
MealPlanNote
├── belongsTo: MealPlan (meal_plan_id)
└── through MealPlan -> belongsTo: User

MealPlan
└── hasMany: MealPlanNote
```

### Validation Rules

| Field | Rules |
|-------|-------|
| meal_plan_id | required, exists:meal_plans,id |
| date | required, date, within meal plan range |
| meal_type | required, valid MealType enum value |
| title | required, string, max:255 |
| details | nullable, string, max:2000 |

### State Transitions

Notes have no explicit state machine. They exist or they don't.

```text
[Created] <-> [Updated] -> [Deleted]
```

---

## Entity Relationship Diagram

```text
┌─────────────┐         ┌──────────────────┐         ┌────────────────┐
│    User     │         │    MealPlan      │         │ MealAssignment │
├─────────────┤         ├──────────────────┤         ├────────────────┤
│ id          │◄────────│ user_id          │◄────────│ meal_plan_id   │
│ name        │         │ id               │         │ recipe_id      │
│ email       │         │ name             │         │ date           │
└─────────────┘         │ start_date       │         │ meal_type      │
                        │ end_date         │         │ serving_mult   │
                        │ description      │         └────────────────┘
                        └────────┬─────────┘
                                 │
                                 │ hasMany
                                 ▼
                        ┌──────────────────┐
                        │  MealPlanNote    │  ← NEW
                        ├──────────────────┤
                        │ id               │
                        │ meal_plan_id     │
                        │ date             │
                        │ meal_type        │
                        │ title            │
                        │ details          │
                        └──────────────────┘
```

---

## Casts

```php
protected function casts(): array
{
    return [
        'date' => 'date',
        'meal_type' => MealType::class,
    ];
}
```

---

## Model Attributes

### Fillable

```php
protected $fillable = [
    'meal_plan_id',
    'date',
    'meal_type',
    'title',
    'details',
];
```

---

## Factory Definition

```php
public function definition(): array
{
    return [
        'meal_plan_id' => MealPlan::factory(),
        'date' => fake()->dateTimeBetween('now', '+7 days'),
        'meal_type' => fake()->randomElement(MealType::cases()),
        'title' => fake()->sentence(3),
        'details' => fake()->optional(0.7)->paragraph(),
    ];
}
```

---

## Comparison: MealPlanNote vs MealAssignment

| Aspect | MealPlanNote | MealAssignment |
|--------|--------------|----------------|
| Purpose | Free-form text notes | Recipe assignments |
| Required FK | meal_plan_id only | meal_plan_id + recipe_id |
| Content | title, details | serving_multiplier, notes |
| Grocery List | Excluded | Included |
| Visual Style | Distinct (amber/document icon) | Standard (recipe card) |

Both share: `meal_plan_id`, `date`, `meal_type`, `created_at`, `updated_at`
