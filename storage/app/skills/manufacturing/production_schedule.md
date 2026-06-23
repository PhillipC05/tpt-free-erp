---
name: Production Schedule Optimiser
slug: manufacturing.production_schedule
version: "1.0"
category: manufacturing
description: Optimises work order sequencing by due date and capacity to minimise late orders.
required_permissions:
  - manufacturing.read
inputs:
  - name: work_orders
    type: array
    description: Array of work order objects, each with id, due_date, estimated_hours, and priority.
    required: true
  - name: capacity_hours_per_day
    type: number
    description: Total productive hours available per working day across the production floor.
    required: true
outputs:
  - name: schedule
    type: array
    description: Sequenced work order schedule with suggested start/end dates and priority ranks.
model_tier: standard
estimated_tokens: 800
cost_tier: medium
enabled_by_default: false
tags: [manufacturing, scheduling, capacity, work-orders, optimisation]
---

## Task

Given a list of manufacturing work orders with due dates and estimated hours, plus the facility's daily capacity, produce an optimised production schedule that sequences work orders to minimise lateness. High-priority and urgent orders should be front-loaded.

## Instructions

You will receive a JSON payload containing:
- `work_orders`: array of work order objects, each with:
  - `id`: integer
  - `title`: string
  - `due_date`: ISO 8601 date string
  - `estimated_hours`: number (total production hours required)
  - `priority`: one of `"low"`, `"medium"`, `"high"`, `"critical"`
  - `dependencies`: array of work order IDs that must complete before this one starts (optional, may be absent or empty)
- `capacity_hours_per_day`: number (e.g. `16` for two 8-hour shifts)
- `schedule_start_date`: ISO 8601 date string — the earliest date production can begin (defaults to today if absent)

Follow these steps exactly:

1. **Priority sort**: Assign a numeric weight to each priority: `critical=4`, `high=3`, `medium=2`, `low=1`.
2. **Earliest Due Date (EDD) with priority tie-breaking**: Sort work orders by:
   - Primary: `due_date` ascending (soonest due first).
   - Secondary (tie-break): priority weight descending (higher priority first).
   - Tertiary: `estimated_hours` ascending (shorter jobs first).
3. **Dependency resolution**: If a work order has `dependencies`, move it after all its dependencies in the sorted list. Do not change the relative order of non-dependent orders.
4. **Schedule assignment**: Walk through the sorted list, tracking cumulative hours consumed:
   - Maintain a `current_date` (start at `schedule_start_date`) and `hours_used_today` (start at 0).
   - For each work order:
     - `suggested_start_date` = `current_date` (the day it begins).
     - Compute how many calendar days it spans: `days_needed = ceil(estimated_hours / capacity_hours_per_day)`.
     - `suggested_end_date` = `suggested_start_date` + `days_needed - 1` days.
     - Advance `current_date` by `days_needed` for the next order.
5. **Lateness flag**: Set `is_late = suggested_end_date > due_date`.
6. **Priority rank**: Assign `priority_rank` as the 1-based position in the final sorted sequence.
7. **Summary**: Compute `total_orders`, `late_orders_count`, and `schedule_span_days` (from first start to last end date, inclusive).
8. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

Skip weekends when advancing dates (treat Saturday and Sunday as non-production days).

## Output Schema

```json
{
  "schedule_start_date": "YYYY-MM-DD",
  "capacity_hours_per_day": 0,
  "total_orders": 0,
  "late_orders_count": 0,
  "schedule_span_days": 0,
  "schedule": [
    {
      "priority_rank": 1,
      "work_order_id": 0,
      "title": "",
      "priority": "",
      "estimated_hours": 0,
      "due_date": "YYYY-MM-DD",
      "suggested_start_date": "YYYY-MM-DD",
      "suggested_end_date": "YYYY-MM-DD",
      "is_late": false,
      "days_late": 0
    }
  ]
}
```

`days_late` = number of calendar days `suggested_end_date` exceeds `due_date`; 0 if on time.
