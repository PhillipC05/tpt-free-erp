---
name: Win/Loss Analysis
slug: sales.win_loss_analysis
version: "1.0"
category: sales
description: Analyses patterns in won vs lost CRM deals to surface actionable insights for the sales team.
required_permissions:
  - sales.read
inputs:
  - name: deals
    type: array
    description: Array of CRM deal objects, each with status, value, industry, competitor, and reason_lost.
    required: true
outputs:
  - name: analysis
    type: object
    description: Win rate, top loss reasons, winning patterns, and prioritised recommendations.
model_tier: standard
estimated_tokens: 800
cost_tier: medium
enabled_by_default: false
tags: [sales, crm, analytics, win-loss, strategy]
---

## Task

Analyse a dataset of CRM deals to calculate win/loss rates, identify recurring loss reasons, surface the characteristics associated with won deals, and generate prioritised recommendations to improve future win rates.

## Instructions

You will receive a JSON payload containing:
- `deals`: array of deal objects, each with:
  - `id`: string or integer
  - `status`: `"won"` or `"lost"`
  - `value`: number (deal value in local currency)
  - `industry`: string (e.g. `"manufacturing"`, `"retail"`)
  - `competitor`: string or null (who was selected instead, if lost)
  - `reason_lost`: string or null (free-text or category, only meaningful when `status === "lost"`)
  - `sales_cycle_days`: number (optional — days from first contact to close)

Follow these steps exactly:

1. **Partition deals**: Split into `won_deals` and `lost_deals` arrays.
2. **Win rate**: `win_rate = won_deals.length / deals.length`, expressed as a percentage rounded to 1 decimal place.
3. **Value metrics**:
   - `avg_won_value` = mean of `value` across won deals.
   - `avg_lost_value` = mean of `value` across lost deals.
4. **Top loss reasons**: Group `lost_deals` by `reason_lost` (case-insensitive, trim whitespace). Rank by frequency descending. Return the top 5. For each include `reason`, `count`, and `pct_of_losses` (percentage of total lost deals).
5. **Competitor analysis**: Group lost deals by `competitor`. Return the top 3 by frequency with `competitor_name`, `losses_to`, and `avg_deal_value_lost`.
6. **Winning patterns**: Analyse won deals to find:
   - Top 3 industries by win count.
   - Whether higher-value or lower-value deals win more often (compare `avg_won_value` vs `avg_lost_value`).
   - Average `sales_cycle_days` for won vs lost deals (omit if field is absent from all records).
7. **Recommendations**: Generate 3–5 concise, actionable recommendations. Each must directly reference a finding from the analysis (e.g. "Focus on [industry] — highest win rate at X%"). Order by expected impact descending.
8. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

If `deals` is empty, return zeroed/empty fields with a `recommendations` entry: `"Insufficient data — no deals provided."`.

## Output Schema

```json
{
  "analysis": {
    "total_deals": 0,
    "won_count": 0,
    "lost_count": 0,
    "win_rate": 0.0,
    "avg_won_value": 0.00,
    "avg_lost_value": 0.00,
    "top_loss_reasons": [
      {
        "reason": "",
        "count": 0,
        "pct_of_losses": 0.0
      }
    ],
    "top_competitors": [
      {
        "competitor_name": "",
        "losses_to": 0,
        "avg_deal_value_lost": 0.00
      }
    ],
    "winning_patterns": {
      "top_industries": [""],
      "value_insight": "",
      "avg_sales_cycle_won_days": null,
      "avg_sales_cycle_lost_days": null
    },
    "recommendations": [""]
  }
}
```
