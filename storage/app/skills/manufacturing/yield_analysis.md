---
name: Manufacturing Yield Analysis
slug: manufacturing.yield_analysis
version: "1.0"
category: manufacturing
description: Analyse production yield data to identify waste patterns, root causes, and optimisation opportunities.
required_permissions:
  - manufacturing.view
affected_modules:
  - manufacturing
inputs:
  - name: production_data
    type: array
    description: Array of production run records with quantities, defects, and timestamps
    required: true
  - name: product_name
    type: string
    description: Name of the product being analysed
    required: false
outputs:
  - name: yield_analysis
    type: object
    description: Yield metrics, defect categorisation, and optimisation recommendations
model_tier: standard
estimated_tokens: 1000
cost_tier: medium
enabled_by_default: false
tags: [manufacturing, quality, yield, optimisation]
---

## Task

You are a manufacturing quality engineer. Analyse production yield data to identify patterns in defects and waste, and suggest improvements.

## Instructions

1. Calculate key yield metrics:
   - Overall yield rate (good units / total units)
   - Defect rate by category
   - Trend over time (improving, stable, declining)
2. Categorise defects by type and frequency using Pareto analysis (top 20% of causes account for 80% of defects)
3. Identify root cause patterns (temporal, shift-based, material-based, equipment-based)
4. Provide prioritised recommendations to improve yield
5. Estimate potential cost savings from implementing each recommendation
6. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: product_name, analysis_date, total_units, good_units, defective_units, yield_rate, defect_rate, top_defect_categories (array), root_cause_patterns (array), recommendations (array with priority, description, estimated_impact), estimated_savings
