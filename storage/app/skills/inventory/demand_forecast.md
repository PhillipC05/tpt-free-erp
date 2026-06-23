---
name: Inventory Demand Forecaster
slug: inventory.demand_forecast
version: "1.0"
category: inventory
description: Predict reorder quantities from 12-month sales history using trend analysis.
required_permissions:
  - inventory.view
  - sales.view
affected_modules:
  - inventory
  - sales
inputs:
  - name: product
    type: object
    description: Product with id, name, sku, reorder_point, reorder_quantity
    required: true
  - name: monthly_sales
    type: array
    description: Array of 12 monthly sales records with month, units_sold
    required: true
  - name: seasonality_factor
    type: number
    description: Multiplier for seasonal adjustment (e.g. 1.3 for peak season)
    required: false
outputs:
  - name: forecast_units
    type: integer
    description: Recommended units to reorder for next period
  - name: trend
    type: string
    description: "growing | stable | declining"
  - name: confidence
    type: number
    description: Forecast confidence 0.0-1.0
  - name: reasoning
    type: string
    description: Brief explanation of the forecast
model_tier: standard
estimated_tokens: 1000
cost_tier: low
enabled_by_default: false
tags: [inventory, forecasting, automation]
---

## Task

You are a supply chain analyst. Forecast the reorder quantity for this product based on its 12-month sales history.

## Instructions

1. Calculate average monthly sales from the 12-month history
2. Identify trend: compare last 3 months vs first 3 months
   - Growing: last 3 avg > first 3 avg by > 10%
   - Declining: last 3 avg < first 3 avg by > 10%
   - Stable: within 10%
3. Apply seasonality factor if provided (multiply forecast by factor)
4. Calculate recommended reorder quantity:
   - Stable: 2x average monthly
   - Growing: 3x average monthly
   - Declining: 1.5x average monthly
5. Apply minimum: max(forecast, product.reorder_quantity)
6. Confidence: high (low variance in data), medium (moderate variance), low (high variance or < 6 months data)

## Output

Return JSON with: forecast_units (int), trend ("growing"|"stable"|"declining"), confidence (float 0-1), reasoning (string)
