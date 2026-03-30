# Project Overview

## Project Name
YesChef

## Description
YesChef is a recipe management and meal planning web application. Users can import recipes, manage ingredients, plan meals, generate grocery lists, and share content with others.

## Tech Stack
- Framework: Laravel 12
- Language: PHP 8.3
- Frontend: Livewire 3, Livewire Volt (single-file components), Livewire Flux 2 (free, UI components)
- Authentication: Laravel Fortify (with 2FA support)
- Styling: Tailwind CSS 4.x
- Build Tool: Vite
- Database: SQLite (dev/test), MariaDB 10.11 (production via DDEV)
- Queue/Cache: Database driver
- Email: Mailgun (symfony/mailgun-mailer)
- PDF: barryvdh/laravel-dompdf
- reCAPTCHA: google/recaptcha

## Project Type
Full-stack web app (Livewire SPA-like experience, no separate API)

## Development Environment
DDEV for local development:
- URL: https://yeschef.ddev.site
- `ddev start` / `ddev stop` / `ddev ssh`

## Core Domain Models
- `User` — authenticated user
- `Recipe` / `RecipeIngredient` — recipe management with ingredient import
- `Ingredient` / `MeasurementUnit` — ingredient catalog
- `GroceryList` / `GroceryItem` — shopping lists with category support
- `MealPlan` / `MealAssignment` / `MealPlanNote` — meal planning
- `ContentShare` — sharing recipes/lists
- `CommonItemTemplate` / `UserItemTemplate` — ingredient category templates
