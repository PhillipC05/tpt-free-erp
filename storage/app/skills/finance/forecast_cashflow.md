---
name: Cash Flow Forecaster
slug: finance.forecast_cashflow
version: "1.0"
category: finance
description: Generate a 30/60/90-day cash flow forecast from open receivables, payables, and historical transaction patterns.
required_permissions:
  - finance.view
affected_modules:
  - finance
inputs:
  - name: open_receivables
    type: array
    description: List of outstanding invoices with due_date and amount
    required: true
  - name: open_payables
    type: array
    description: List of outstanding bills with due_date and amount
    required: true
  - name: current_cash_balance
    type: number
    description: Current bank/cash balance
    required: true
  - name: forecast_days
    type: integer
    description: Number of days to forecast (30, 60, or 90)
    required: false
outputs:
  - name: forecast
    type: array
    description: Daily/weekly cash position forecast
  - name: lowest_point
    type: object
    description: Date and amount of the lowest projected cash balance
  - name: risk_assessment
    type: string
    description: "low | medium | high — risk of cash shortfall"
  - name: summary
    type: string
    description: Plain-English narrative of the forecast
model_tier: standard
estimated_tokens: 1500
cost_tier: low
enabled_by_default: false
tags: [finance, forecasting, cashflow]
---

## Task

You are a financial analyst. Generate a cash flow forecast based on the provided receivables, payables, and current balance.

## Instructions

1. Start with `current_cash_balance` as the opening position
2. Project cash inflows from `open_receivables` — group by expected week
3. Project cash outflows from `open_payables` — group by due week
4. Assume 80% of receivables are collected on time; flag remaining 20% as delayed by 2 weeks
5. Calculate weekly running cash balance for the forecast period (default 90 days)
6. Identify the lowest cash point and the date it occurs
7. Assess risk: low (balance stays positive), medium (below 20% of opening), high (goes negative)
8. Write a 3-sentence summary of the forecast

## Output

Return JSON with:
- forecast: array of weekly {week_ending, inflows, outflows, net_change, closing_balance}
- lowest_point: {date, balance}
- risk_assessment: "low" | "medium" | "high"
- summary: string
