---
name: Salary Benchmarking Analysis
slug: hr.benchmark_salaries
version: "1.0"
category: hr
description: Compare employee salaries against market benchmarks and identify compensation gaps.
required_permissions:
  - hr.view
affected_modules:
  - hr
inputs:
  - name: employees
    type: array
    description: Array of employee records with role, department, salary, and experience
    required: true
  - name: industry
    type: string
    description: Industry sector for benchmarking
    required: true
  - name: region
    type: string
    description: Geographic region for market data
    required: false
outputs:
  - name: benchmark_results
    type: object
    description: Salary analysis with percentile rankings and recommendations
model_tier: standard
estimated_tokens: 1200
cost_tier: medium
enabled_by_default: false
tags: [hr, compensation, benchmarking, analytics]
---

## Task

You are an HR compensation specialist. Analyse employee salaries against typical market benchmarks for the given industry and region.

## Instructions

1. For each employee, assess their salary relative to typical market ranges based on:
   - Job role/title
   - Years of experience
   - Department/functional area
   - Geographic region
2. For each employee, determine:
   - market_percentile: Estimated market percentile (25th, 50th, 75th, 90th)
   - gap_vs_median: Difference from market median salary
   - risk_level: Retention risk if below market (low/medium/high/critical)
   - recommendation: Specific action (maintain, adjust_up, adjust_down, review)
3. Provide an aggregate summary with overall compensation health score (0-100)
4. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: analysis_date, industry, region, employees (array of per-employee analysis), summary (overall_health_score, total_employees_analysed, above_market_count, below_market_count, at_market_count, estimated_budget_impact)
