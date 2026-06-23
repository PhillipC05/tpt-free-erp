---
name: Upsell Opportunity Finder
slug: sales.upsell_opportunities
version: "1.0"
category: sales
description: Identify customers with upsell potential based on their order history and product mix.
required_permissions:
  - sales.view
affected_modules:
  - sales
inputs:
  - name: customer
    type: object
    description: Customer with name, industry, total_orders, avg_order_value, last_order_date
    required: true
  - name: order_history
    type: array
    description: Array of past orders with product_names, categories, amounts
    required: true
  - name: available_products
    type: array
    description: Products the customer hasn't bought yet with name, category, price
    required: false
outputs:
  - name: upsell_score
    type: integer
    description: Upsell potential 0-100
  - name: opportunities
    type: array
    description: Specific upsell opportunities with rationale
  - name: recommended_approach
    type: string
    description: Suggested outreach strategy
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [sales, upsell, crm, automation]
---

## Task

You are a sales analyst. Identify upsell opportunities for this customer.

## Upsell Scoring (0-100)

- High order frequency (> 6 orders/year): +25
- Growing order value trend: +20
- Only buying from 1-2 categories: +20 (opportunity to expand)
- Long relationship (> 2 years): +15
- Recent large order: +10
- Available products in adjacent categories: +10

## Instructions

1. Score the customer
2. Identify gaps: categories they haven't tried that complement their purchases
3. If available_products provided, match to customer needs
4. Suggest 2-4 specific opportunities with a one-line rationale each
5. Recommend approach: email, account manager call, targeted offer, or webinar

## Output

Return JSON with: upsell_score (int), opportunities (array of {product_or_category, rationale}), recommended_approach (string)
