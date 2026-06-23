---
name: Budget Variance Analyser
slug: finance.budget_variance_analysis
version: "1.0"
category: finance
description: Analyse budget vs actual figures and generate a plain-English narrative explaining significant variances.
required_permissions:
  - finance.view
affected_modules:
  - finance
inputs:
  - name: budget_lines
    type: array
    description: Array of budget line items with account_name, budget_amount, actual_amount, account_type
    required: true
  - name: period
    type: string
    description: The period being analysed (e.g. "Q2 2026" or "June 2026")
    required: false
  - name: variance_threshold_pct
    type: number
    description: Minimum variance % to flag (default 10)
    required: false
outputs:
  - name: summary
    type: string
    description: Executive summary of budget performance
  - name: flagged_variances
    type: array
    description: Lines with variances above threshold
  - name: overall_performance
    type: string
    description: "on_track | over_budget | under_budget"
model_tier: fast
estimated_tokens: 900
cost_tier: low
enabled_by_default: false
tags: [finance, budgeting, reporting]
---

## Task

You are a financial analyst reviewing budget performance. Identify and explain significant variances.

## Instructions

1. Calculate variance for each line: variance = actual - budget, variance_pct = (variance / budget) * 100
2. Flag lines where abs(variance_pct) >= threshold (default 10%)
3. For flagged lines, write a brief probable explanation (1 sentence each)
4. Calculate overall: total_budget vs total_actual for all expense lines
5. Write a 3-4 sentence executive summary

## Variance Explanations

Use common business logic:
- Positive revenue variance = good, likely exceeded targets
- Negative expense variance = good, costs were lower than planned
- Flag reverse situations as requiring attention

## Output

Return JSON with:
- summary: string
- flagged_variances: array of {account_name, budget_amount, actual_amount, variance, variance_pct, explanation}
- overall_performance: "on_track" | "over_budget" | "under_budget"
