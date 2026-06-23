---
name: Shrinkage Detection
slug: inventory.shrinkage_detection
version: "1.0"
category: inventory
description: Flags unusual stock variance across warehouses that may indicate theft, damage, or data entry errors.
required_permissions:
  - inventory.read
inputs:
  - name: stock_movements
    type: array
    description: Array of stock movement records with product_id, warehouse_id, movement_type, quantity, and recorded_date.
    required: true
  - name: threshold_percent
    type: number
    description: Variance percentage above which a discrepancy is flagged. Defaults to 5 if omitted.
    required: false
outputs:
  - name: anomalies
    type: array
    description: Array of flagged anomalies with product/warehouse details, variance metrics, and risk level.
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [inventory, shrinkage, audit, anomaly-detection, warehouse]
---

## Task

Analyse stock movement records to identify products and warehouses where actual inventory levels deviate unexpectedly from what the movement history predicts, flagging potential shrinkage events by risk level.

## Instructions

You will receive a JSON payload containing:
- `stock_movements`: array of movement objects, each with:
  - `id`: integer
  - `product_id`: integer
  - `warehouse_id`: integer
  - `movement_type`: one of `"receipt"`, `"shipment"`, `"adjustment"`, `"return"`, `"stocktake"`
  - `quantity`: number (positive = stock in, negative = stock out; for shipments this will be negative)
  - `recorded_date`: ISO 8601 date string
  - `actual_qty_on_hand` (optional): recorded physical count at time of stocktake — only present on `"stocktake"` records
- `threshold_percent`: optional number, default `5`

Follow these steps exactly:

1. **Group movements** by `(product_id, warehouse_id)` composite key.
2. **Compute expected quantity** for each group: sum all `quantity` values for movement types `"receipt"`, `"shipment"`, `"adjustment"`, and `"return"`. This is the book stock level.
3. **Identify stocktake records**: For each group, find the most recent `"stocktake"` record that has an `actual_qty_on_hand` value.
4. **Compute variance**: Where a stocktake record exists:
   - `variance_qty = actual_qty_on_hand - expected_qty`
   - `variance_percent = abs(variance_qty / expected_qty) * 100` (if `expected_qty` is 0 and `actual_qty_on_hand` > 0, set variance_percent to 100)
5. **Apply threshold**: Flag the group as an anomaly if `variance_percent >= threshold_percent`.
6. **Assign risk level**:
   - `variance_percent >= 20` → `"high"`
   - `variance_percent >= 10` → `"medium"`
   - `variance_percent >= threshold_percent` → `"low"`
7. **Sort anomalies** by `variance_percent` descending (highest risk first).
8. For groups with no stocktake record, skip — do not flag as anomaly.
9. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

If `stock_movements` is empty or no anomalies are detected, return an empty `anomalies` array with `anomaly_count: 0`.

## Output Schema

```json
{
  "threshold_percent": 5,
  "anomaly_count": 0,
  "anomalies": [
    {
      "product_id": 0,
      "warehouse_id": 0,
      "expected_qty": 0.00,
      "actual_qty": 0.00,
      "variance_qty": 0.00,
      "variance_percent": 0.00,
      "risk_level": "low|medium|high",
      "last_stocktake_date": "YYYY-MM-DD",
      "movement_count": 0
    }
  ]
}
```

All quantity values must be numbers. `variance_percent` rounded to 2 decimal places.
