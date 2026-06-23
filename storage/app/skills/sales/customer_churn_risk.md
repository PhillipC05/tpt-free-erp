---
name: Customer Churn Risk Detector
slug: sales.customer_churn_risk
version: "1.0"
category: sales
description: Flag customers with declining order frequency or value, and recommend retention actions.
required_permissions:
  - sales.view
affected_modules:
  - sales
inputs:
  - name: customers
    type: array
    description: Array of customer objects with id, name, orders_last_90_days, orders_prev_90_days, avg_order_value, last_order_date, total_lifetime_value
    required: true
outputs:
  - name: high_risk
    type: array
    description: Customers with high churn risk
  - name: medium_risk
    type: array
    description: Customers with medium churn risk
  - name: summary
    type: string
    description: Overall churn risk summary
model_tier: fast
estimated_tokens: 900
cost_tier: low
enabled_by_default: false
tags: [sales, crm, churn, automation]
---

## Task

You are a customer success analyst. Identify customers at risk of churning based on their order history.

## Risk Scoring

**High risk** (flag all of these):
- No orders in 60+ days AND had orders in prior period
- Order volume dropped > 50% vs prior 90 days
- Last order value dropped > 40% vs average

**Medium risk**:
- No orders in 30-59 days
- Order volume dropped 25-50%
- High lifetime value + recent drop = escalate

For each at-risk customer, suggest one retention action (outreach call, discount offer, check-in email).

## Output

Return JSON with:
- high_risk: array of {customer_id, name, risk_reason, days_since_last_order, suggested_action}
- medium_risk: array with same structure
- summary: string
