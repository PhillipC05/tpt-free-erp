---
name: Vendor Evaluation Scorer
slug: procurement.evaluate_vendor
version: "1.0"
category: procurement
description: Score a vendor on price competitiveness, delivery reliability, and quality history.
required_permissions:
  - procurement.view
affected_modules:
  - procurement
inputs:
  - name: vendor
    type: object
    description: Vendor object with name, category, total_orders
    required: true
  - name: delivery_history
    type: object
    description: Object with on_time_deliveries, late_deliveries, avg_delay_days
    required: true
  - name: quality_history
    type: object
    description: Object with total_received, defect_count, return_count
    required: true
  - name: price_comparison
    type: object
    description: Object with our_avg_unit_cost, market_avg_unit_cost
    required: false
outputs:
  - name: score
    type: integer
    description: Overall vendor score 0-100
  - name: breakdown
    type: object
    description: Score components by category
  - name: recommendation
    type: string
    description: "preferred | acceptable | review | remove"
  - name: notes
    type: string
    description: Brief assessment narrative
model_tier: fast
estimated_tokens: 700
cost_tier: low
enabled_by_default: false
tags: [procurement, vendor, automation]
---

## Task

You are a procurement analyst. Score this vendor based on their delivery, quality, and pricing performance.

## Scoring Rubric (100 points total)

**Delivery (40 points)**
- On-time rate 95%+: 40 pts
- 90-94%: 30 pts
- 80-89%: 20 pts
- Below 80%: 10 pts

**Quality (40 points)**
- Defect rate < 1%: 40 pts
- 1-2%: 30 pts
- 2-5%: 15 pts
- Above 5%: 5 pts

**Price (20 points)**
- Below market average: 20 pts
- At market: 15 pts
- Up to 10% above market: 8 pts
- More than 10% above: 0 pts

**Recommendations:**
- 80+: preferred
- 60-79: acceptable
- 40-59: review
- Below 40: remove

## Output

Return JSON with: score (int), breakdown (object), recommendation (string), notes (string)
