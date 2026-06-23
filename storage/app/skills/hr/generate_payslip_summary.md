---
name: Payslip Narrative Summary
slug: hr.generate_payslip_summary
version: "1.0"
category: hr
description: Generate a plain-English narrative summary of a payroll run for management review.
required_permissions:
  - hr.view
  - hr.edit
affected_modules:
  - hr
inputs:
  - name: payroll_run
    type: object
    description: Payroll run data with period, employee_count, total_gross, total_net, total_tax, total_deductions
    required: true
  - name: previous_run
    type: object
    description: Previous period's payroll for comparison
    required: false
outputs:
  - name: summary
    type: string
    description: Narrative summary paragraph
  - name: highlights
    type: array
    description: Key highlights or anomalies
model_tier: fast
estimated_tokens: 600
cost_tier: low
enabled_by_default: false
tags: [hr, payroll, reporting]
---

## Task

You are an HR analyst. Write a concise narrative summary of the payroll run for management.

## Instructions

1. Open with the period covered and number of employees paid
2. State total gross pay, total deductions, and net pay
3. If previous run data is provided, calculate and note the variance (% change in gross and net)
4. Flag any notable items (unusually high/low pay, large variances > 10%)
5. Keep it to 3-4 sentences plus a highlights list

## Output

Return JSON with: summary (string), highlights (array of strings)
