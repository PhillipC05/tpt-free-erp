---
name: Investor Financial Report
slug: finance.investor_report
version: "1.0"
category: finance
description: Generate a structured investor-facing financial summary with KPIs, trends, and projections.
required_permissions:
  - finance.view
affected_modules:
  - finance
inputs:
  - name: financial_data
    type: object
    description: Revenue, expenses, assets, liabilities, and cash flow data
    required: true
  - name: period
    type: string
    description: Reporting period (e.g. Q1 2026, FY2026)
    required: true
  - name: previous_period_data
    type: object
    description: Prior period financials for comparison
    required: false
outputs:
  - name: investor_report
    type: object
    description: Structured financial report with executive summary, KPIs, and outlook
model_tier: powerful
estimated_tokens: 1500
cost_tier: medium
enabled_by_default: false
tags: [finance, investor, reporting, strategic]
---

## Task

You are a financial analyst preparing an investor-ready summary. Transform raw financial data into a clear, professional report highlighting key metrics and trends.

## Instructions

1. Calculate and present key financial KPIs:
   - Revenue (total, growth rate vs prior period)
   - Gross margin and net margin percentages
   - EBITDA if data available
   - Cash burn rate and runway
   - Working capital position
2. Identify significant trends:
   - Revenue growth trajectory
   - Expense structure changes
   - Profitability improvement or decline
   - Cash flow health
3. Generate forward-looking statements:
   - Next quarter projections based on trends
   - Key risks and mitigations
   - Capital allocation recommendations
4. Write a concise executive summary (2-3 paragraphs)
5. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: period, executive_summary, kpis (revenue, gross_margin, net_margin, ebitda, cash_position, burn_rate, runway_months), trends (array of trend descriptions), projections (next_period_revenue, next_period_net_income), risks (array), recommendations (array)
