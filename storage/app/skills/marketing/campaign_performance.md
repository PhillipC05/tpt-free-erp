---
name: Email Campaign Performance Analysis
slug: marketing.campaign_performance
version: "1.0"
category: marketing
description: Analyse email marketing campaign metrics and recommend optimisations for open rates, click rates, and conversions.
required_permissions:
  - marketing.view
affected_modules:
  - marketing
inputs:
  - name: campaign_data
    type: array
    description: Array of campaign records with sends, opens, clicks, conversions
    required: true
  - name: benchmarks
    type: object
    description: Industry benchmark rates for comparison
    required: false
outputs:
  - name: performance_analysis
    type: object
    description: Campaign performance metrics, benchmarks comparison, and optimisation recommendations
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [marketing, email, analytics, optimisation]
---

## Task

You are a marketing analytics specialist. Analyse email campaign performance data and provide actionable optimisation recommendations.

## Instructions

1. For each campaign, calculate:
   - open_rate, click_rate, conversion_rate, unsubscribe_rate
   - Revenue per email sent
   - Cost per conversion
2. Compare against industry benchmarks (or defaults if not provided)
3. Identify top-performing and under-performing campaigns with reasons
4. Recommend specific optimisations:
   - Subject line improvements for low open rates
   - Content/call-to-action changes for low click rates
   - Segmentation suggestions for better targeting
   - Send time optimisation based on engagement patterns
5. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: analysis_date, total_campaigns, aggregate_metrics (avg_open_rate, avg_click_rate, avg_conversion_rate), campaign_breakdown (array), top_performers (array), underperformers (array), recommendations (array with priority, category, description)
