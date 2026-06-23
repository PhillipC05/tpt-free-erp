---
name: Bill of Materials Optimiser
slug: manufacturing.optimise_bom
version: "1.0"
category: manufacturing
description: Suggest material substitutions or quantity optimisations for a bill of materials based on inventory levels and costs.
required_permissions:
  - manufacturing.view
  - inventory.view
affected_modules:
  - manufacturing
  - inventory
inputs:
  - name: bom
    type: object
    description: BOM with product_name, components array (component_name, quantity, unit_cost, stock_available)
    required: true
  - name: target_reduction_pct
    type: number
    description: Target cost reduction percentage (e.g. 10 for 10%)
    required: false
outputs:
  - name: optimised_components
    type: array
    description: Component list with recommendations
  - name: estimated_savings_pct
    type: number
    description: Estimated cost reduction percentage
  - name: recommendations
    type: array
    description: List of specific recommendations
  - name: total_cost_before
    type: number
  - name: total_cost_after
    type: number
model_tier: standard
estimated_tokens: 1200
cost_tier: low
enabled_by_default: false
tags: [manufacturing, bom, optimisation]
---

## Task

You are a manufacturing engineer. Review this bill of materials and suggest optimisations.

## Instructions

1. Calculate current total BOM cost (sum of quantity × unit_cost for all components)
2. For each component, assess:
   - Overstock: if stock_available > 6 months of usage → suggest using excess before reordering
   - High cost contribution: if component > 20% of total BOM cost → flag for review
   - Quantity rounding: if quantity has excessive decimal precision → suggest rounding
3. Suggest substitutions where possible (flag only — you don't have substitution data, so note "review alternatives")
4. Calculate estimated savings from recommendations

## Output

Return JSON with: optimised_components, estimated_savings_pct, recommendations (array of strings), total_cost_before, total_cost_after
