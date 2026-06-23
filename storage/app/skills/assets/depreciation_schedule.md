---
name: Asset Depreciation Schedule
slug: assets.depreciation_schedule
version: "1.0"
category: assets
description: Generates a full year-by-year depreciation table for an asset portfolio.
required_permissions:
  - assets.read
inputs:
  - name: assets
    type: array
    description: Array of asset objects, each with id, name, purchase_value, useful_life_years, depreciation_method, and purchase_date.
    required: true
outputs:
  - name: schedule
    type: array
    description: Per-asset depreciation schedule with year-by-year opening value, depreciation amount, closing value, and accumulated depreciation.
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [assets, depreciation, accounting, fixed-assets, finance]
---

## Task

Compute a complete year-by-year depreciation schedule for each asset in the portfolio, supporting three depreciation methods: straight-line, declining balance, and sum-of-years-digits. Return structured tables suitable for financial reporting and audit.

## Instructions

You will receive a JSON payload containing:
- `assets`: array of asset objects, each with:
  - `id`: integer
  - `name`: string
  - `purchase_value`: number (original cost)
  - `salvage_value`: number (residual/scrap value at end of life) — default to `0` if absent
  - `useful_life_years`: integer
  - `depreciation_method`: one of `"straight_line"`, `"declining"`, `"sum_of_years"`
  - `purchase_date`: ISO 8601 date string (YYYY-MM-DD)
  - `declining_rate`: number (0–1, e.g. 0.25 for 25%) — only required when `depreciation_method === "declining"`

Follow these steps exactly for each asset:

1. **Depreciable base**: `depreciable_amount = purchase_value - salvage_value`.
2. **Determine start year**: Extract the year from `purchase_date`.
3. **Compute annual depreciation** for each year from year 1 to `useful_life_years`:

   **Straight-line** (`straight_line`):
   - `annual_depreciation = depreciable_amount / useful_life_years` (constant each year).

   **Declining balance** (`declining`):
   - Year 1: `depreciation = opening_value * declining_rate`
   - Each subsequent year: `depreciation = closing_value_prior_year * declining_rate`
   - In the final year, depreciate the remaining book value down to `salvage_value` exactly.

   **Sum-of-years-digits** (`sum_of_years`):
   - `sum_of_digits = useful_life_years * (useful_life_years + 1) / 2`
   - Year N depreciation = `depreciable_amount * (useful_life_years - N + 1) / sum_of_digits`

4. **Per-year row**:
   - `year_number`: 1, 2, 3 … `useful_life_years`
   - `calendar_year`: `start_year + year_number - 1`
   - `opening_value`: book value at start of year (year 1 = `purchase_value`)
   - `depreciation_amount`: computed above, rounded to 2 decimal places
   - `closing_value`: `opening_value - depreciation_amount` (never below `salvage_value`)
   - `accumulated_depreciation`: cumulative sum of all `depreciation_amount` values up to and including this year

5. **Asset summary**: Include `total_depreciation` (sum of all annual amounts — should equal `depreciable_amount`) and `final_book_value` (should equal `salvage_value`).
6. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

Round all monetary values to 2 decimal places. Do not produce rows beyond `useful_life_years`.

## Output Schema

```json
{
  "schedule": [
    {
      "asset_id": 0,
      "asset_name": "",
      "depreciation_method": "",
      "purchase_value": 0.00,
      "salvage_value": 0.00,
      "useful_life_years": 0,
      "total_depreciation": 0.00,
      "final_book_value": 0.00,
      "years": [
        {
          "year_number": 1,
          "calendar_year": 2024,
          "opening_value": 0.00,
          "depreciation_amount": 0.00,
          "closing_value": 0.00,
          "accumulated_depreciation": 0.00
        }
      ]
    }
  ]
}
```
