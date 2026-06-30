---
name: Sales Territory Planning
slug: sales.territory_planning
version: "1.0"
category: sales
description: Optimise sales territory assignments based on revenue potential, rep capacity, and geographic factors.
required_permissions:
  - sales.view
affected_modules:
  - sales
inputs:
  - name: territories
    type: array
    description: Current territory definitions with assigned reps and customer data
    required: true
  - name: sales_reps
    type: array
    description: Sales rep data including capacity, performance, and specialisations
    required: true
  - name: constraints
    type: object
    description: Business constraints (max accounts per rep, geographic limits, etc.)
    required: false
outputs:
  - name: territory_plan
    type: object
    description: Optimised territory assignments with revenue projections and workload balance
model_tier: standard
estimated_tokens: 1200
cost_tier: medium
enabled_by_default: false
tags: [sales, territory, planning, optimisation]
---

## Task

You are a sales operations analyst. Optimise sales territory assignments to balance workload, maximise revenue potential, and ensure equitable distribution.

## Instructions

1. Analyse current territory data:
   - Revenue per territory
   - Customer density and growth potential
   - Current rep workload and performance
2. Identify imbalances:
   - Overloaded territories (too many accounts, too much revenue for one rep)
   - Under-served territories (missed revenue potential)
   - Geographic inefficiencies (excessive travel, coverage gaps)
3. Propose an optimised assignment plan:
   - Which territories to split, merge, or reassign
   - Expected revenue impact of each change
   - Rep capacity utilisation after reassignment
4. Provide a phased transition plan to minimise disruption
5. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: analysis_date, current_state (total_territories, total_reps, avg_accounts_per_rep, total_revenue), proposed_changes (array), projected_impact (revenue_change, workload_balance_score), transition_plan (array of phases), recommendations
