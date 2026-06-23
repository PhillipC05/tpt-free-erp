---
name: Financial Scenario Planning
slug: finance.scenario_planning
version: "1.0"
category: finance
description: Models P&L under different growth/cost assumptions (optimistic, base, pessimistic).
required_permissions:
  - finance.read
inputs:
  - name: base_revenue
    type: number
    description: Current baseline annual revenue figure.
    required: true
  - name: base_costs
    type: number
    description: Current baseline annual cost figure (COGS + operating expenses).
    required: true
  - name: growth_scenarios
    type: array
    description: Optional array of scenario objects, each with label, revenue_growth_pct, and cost_growth_pct. Defaults to standard optimistic/base/pessimistic scenarios if omitted.
    required: false
outputs:
  - name: scenarios
    type: array
    description: Array of modelled P&L outcomes, one per scenario, with revenue, costs, gross profit, margin, and net profit.
model_tier: powerful
estimated_tokens: 800
cost_tier: high
enabled_by_default: false
tags: [finance, scenario-planning, forecasting, p-and-l, strategy, budgeting]
---

## Task

Model a set of Profit & Loss scenarios by applying growth and cost assumptions to a baseline revenue and cost figure. For each scenario, compute projected P&L metrics and surface the key strategic implications of each outcome.

## Instructions

You will receive a JSON payload containing:
- `base_revenue`: number — current annual revenue
- `base_costs`: number — current annual total costs
- `growth_scenarios`: optional array of scenario objects, each with:
  - `label`: string (e.g. `"Optimistic"`, `"Base Case"`, `"Pessimistic"`)
  - `revenue_growth_pct`: number (e.g. `15` = 15% growth, `-10` = 10% decline)
  - `cost_growth_pct`: number (e.g. `5` = 5% cost increase)
  - `notes`: string (optional context for this scenario)
- `tax_rate`: optional number (0–1, default `0.28`) — applied to net profit before tax
- `interest_expense`: optional number — annual debt servicing cost deducted from gross profit

If `growth_scenarios` is absent or empty, use these three defaults:
- `{"label": "Optimistic", "revenue_growth_pct": 20, "cost_growth_pct": 5}`
- `{"label": "Base Case", "revenue_growth_pct": 8, "cost_growth_pct": 6}`
- `{"label": "Pessimistic", "revenue_growth_pct": -5, "cost_growth_pct": 10}`

Follow these steps exactly for each scenario:

1. **Projected revenue**: `projected_revenue = base_revenue * (1 + revenue_growth_pct / 100)`.
2. **Projected costs**: `projected_costs = base_costs * (1 + cost_growth_pct / 100)`.
3. **Gross profit**: `gross_profit = projected_revenue - projected_costs`.
4. **EBIT** (Earnings Before Interest and Tax): `ebit = gross_profit - interest_expense` (use `0` if `interest_expense` absent).
5. **Tax**: `tax_amount = max(0, ebit * tax_rate)`.
6. **Net profit**: `net_profit = ebit - tax_amount`.
7. **Gross margin**: `gross_margin_pct = (gross_profit / projected_revenue) * 100`, rounded to 2 decimal places.
8. **Net margin**: `net_margin_pct = (net_profit / projected_revenue) * 100`, rounded to 2 decimal places.
9. **Break-even analysis**: `breakeven_revenue = projected_costs` (the revenue required to cover all costs at 0% profit).
10. **Strategic implication**: Write one concise sentence interpreting this scenario's outcome for leadership (e.g. whether it is viable, what it signals about the business model).
11. **Scenario comparison summary**: After all scenarios, identify which has the highest `net_profit` (best case), which has the lowest (worst case), and the `net_profit` delta between them.
12. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

Round all monetary values to 2 decimal places; all percentage values to 2 decimal places.

## Output Schema

```json
{
  "base_revenue": 0.00,
  "base_costs": 0.00,
  "tax_rate": 0.28,
  "scenarios": [
    {
      "label": "",
      "revenue_growth_pct": 0.0,
      "cost_growth_pct": 0.0,
      "projected_revenue": 0.00,
      "projected_costs": 0.00,
      "gross_profit": 0.00,
      "gross_margin_pct": 0.00,
      "ebit": 0.00,
      "tax_amount": 0.00,
      "net_profit": 0.00,
      "net_margin_pct": 0.00,
      "breakeven_revenue": 0.00,
      "strategic_implication": ""
    }
  ],
  "comparison": {
    "best_case_label": "",
    "worst_case_label": "",
    "net_profit_delta": 0.00
  }
}
```
