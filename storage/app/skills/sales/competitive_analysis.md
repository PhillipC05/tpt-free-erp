---
name: Competitive Market Analysis
slug: sales.competitive_analysis
version: "1.0"
category: sales
description: Analyse competitive landscape based on product data, market positioning, and customer feedback.
required_permissions:
  - sales.view
affected_modules:
  - sales
inputs:
  - name: product_name
    type: string
    description: Name or description of the product/service to analyse
    required: true
  - name: competitors
    type: array
    description: List of known competitors with pricing and feature data
    required: false
  - name: customer_feedback
    type: array
    description: Recent customer feedback or win/loss data
    required: false
outputs:
  - name: competitive_analysis
    type: object
    description: SWOT-style competitive analysis with positioning recommendations
model_tier: standard
estimated_tokens: 1200
cost_tier: medium
enabled_by_default: false
tags: [sales, competitive, strategy, market-analysis]
---

## Task

You are a sales strategy analyst. Produce a competitive analysis based on the product information and market data provided.

## Instructions

1. Analyse the product's market position relative to competitors
2. For each competitor (or estimated competitor), assess:
   - strengths: What they do well
   - weaknesses: Where they fall short
   - pricing_position: Relative pricing (budget, mid-range, premium)
   - key_differentiators: What makes them unique
3. Produce a SWOT analysis for the target product:
   - strengths, weaknesses, opportunities, threats
4. Provide 3-5 actionable recommendations for improving competitive positioning
5. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: product_name, analysis_date, competitors (array), swot (strengths, weaknesses, opportunities, threats), recommendations (array of strings), overall_competitive_score (0-100)
