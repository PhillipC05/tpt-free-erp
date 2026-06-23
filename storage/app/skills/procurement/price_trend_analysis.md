---
name: Procurement Price Trend Analysis
slug: procurement.price_trend_analysis
version: "1.0"
category: procurement
description: Analyses vendor price movement over time to flag inflation, volatility, and better-value alternatives.
required_permissions:
  - procurement.read
inputs:
  - name: purchase_orders
    type: array
    description: Array of purchase order line objects, each with vendor_id, vendor_name, item_code, unit_price, and order_date.
    required: true
  - name: lookback_months
    type: integer
    description: Number of months of history to analyse. Defaults to 12 if omitted.
    required: false
outputs:
  - name: analysis
    type: object
    description: Item-level price trends, volatility scores, cheapest vendor per item, and a flagged items list for urgent review.
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [procurement, pricing, vendors, cost-control, analytics]
---

## Task

Analyse historical purchase order data to identify how vendor prices have moved over time for each item. Flag items with significant price inflation or high price volatility, and identify which vendor currently offers the best value for each item.

## Instructions

You will receive a JSON payload containing:
- `purchase_orders`: array of PO line objects, each with:
  - `id`: integer
  - `vendor_id`: integer
  - `vendor_name`: string
  - `item_code`: string
  - `item_name`: string (optional)
  - `unit_price`: number
  - `quantity`: number (optional)
  - `order_date`: ISO 8601 date string
- `lookback_months`: optional integer, default `12`

Follow these steps exactly:

1. **Determine analysis window**: Find the most recent `order_date` in the dataset. The window starts `lookback_months` months before that date.
2. **Filter by window**: Retain only records within the analysis window.
3. **Group by `item_code`**: For each item, collect all PO lines across all vendors.
4. **Chronological sort**: Within each item group, sort records by `order_date` ascending.
5. **Per-item trend analysis**:
   a. **First and last price**: `first_price` = unit_price of the oldest record; `last_price` = unit_price of the most recent record (regardless of vendor — use overall most recent).
   b. **Avg price change %**: `avg_price_change_pct = ((last_price - first_price) / first_price) * 100`, rounded to 2 decimal places. Positive = inflation, negative = price reduction.
   c. **Volatility score**: Compute the standard deviation of all unit prices for this item across the window. Normalise: `volatility_score = (std_dev / mean_price) * 100` (coefficient of variation as a percentage), rounded to 2 decimal places.
   d. **Cheapest vendor**: Among records in the most recent month of the window, find the vendor with the lowest `unit_price`. If no records in the last month, use the last 3 months.
   e. **Order count**: Total number of PO lines for this item in the window.
6. **Flag items for review** if any of:
   - `avg_price_change_pct >= 10` (significant inflation)
   - `avg_price_change_pct <= -10` (unexpected price drop — verify quality)
   - `volatility_score >= 15` (high price instability)
7. **Summary statistics**:
   - `total_items_analysed`: count of unique item codes.
   - `flagged_items_count`: count of flagged items.
   - `avg_inflation_across_items`: mean of all `avg_price_change_pct` values, rounded to 2 decimal places.
8. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

If `purchase_orders` is empty or no records fall in the window, return empty arrays and zeroed summary stats.

## Output Schema

```json
{
  "analysis": {
    "lookback_months": 12,
    "analysis_window_start": "YYYY-MM-DD",
    "analysis_window_end": "YYYY-MM-DD",
    "total_items_analysed": 0,
    "flagged_items_count": 0,
    "avg_inflation_across_items": 0.00,
    "trends": [
      {
        "item_code": "",
        "item_name": "",
        "order_count": 0,
        "first_price": 0.00,
        "last_price": 0.00,
        "avg_price_change_pct": 0.00,
        "volatility_score": 0.00,
        "cheapest_vendor": {
          "vendor_id": 0,
          "vendor_name": "",
          "unit_price": 0.00
        },
        "flagged": false,
        "flag_reasons": []
      }
    ],
    "flagged_items": [
      {
        "item_code": "",
        "item_name": "",
        "avg_price_change_pct": 0.00,
        "volatility_score": 0.00,
        "flag_reasons": [""],
        "recommended_action": ""
      }
    ]
  }
}
```

`flag_reasons` is an array of strings such as `"Inflation >= 10%"`, `"High volatility"`, `"Unexpected price drop"`. `recommended_action` should be one concise sentence.
