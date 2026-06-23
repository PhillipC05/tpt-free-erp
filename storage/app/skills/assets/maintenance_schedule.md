---
name: Preventive Maintenance Planner
slug: assets.maintenance_schedule
version: "1.0"
category: assets
description: Generate a preventive maintenance schedule for an asset based on its type, age, and manufacturer recommendations.
required_permissions:
  - assets.view
  - assets.edit
affected_modules:
  - assets
inputs:
  - name: asset
    type: object
    description: Asset with name, type, purchase_date, last_maintenance_date, manufacturer, model, current_status
    required: true
  - name: usage_hours
    type: number
    description: Total usage hours (for equipment)
    required: false
  - name: maintenance_history
    type: array
    description: Array of past maintenance records with date, type, notes
    required: false
outputs:
  - name: schedule
    type: array
    description: Upcoming maintenance tasks with due_date and description
  - name: priority_tasks
    type: array
    description: Tasks that should be done immediately or within 30 days
  - name: annual_maintenance_plan
    type: string
    description: Narrative annual maintenance summary
model_tier: fast
estimated_tokens: 900
cost_tier: low
enabled_by_default: false
tags: [assets, maintenance, automation]
---

## Task

You are a facilities manager. Create a preventive maintenance schedule for this asset.

## Standard Intervals by Asset Type

- **equipment**: Monthly inspection, quarterly service, annual overhaul
- **vehicle**: Every 10,000km or 3 months (oil/filters), annual warrant/registration
- **building**: Monthly safety checks, quarterly HVAC, annual structural inspection
- **it**: Monthly updates, quarterly backup test, annual refresh review
- **furniture**: Annual inspection, replace as needed

## Instructions

1. Determine asset age from purchase_date
2. Check how long since last maintenance
3. Generate schedule for next 12 months based on type and intervals
4. Flag anything overdue (last_maintenance_date + interval < today) as priority
5. Write a 2-3 sentence annual maintenance summary

## Output

Return JSON with: schedule (array of {task, due_date, estimated_hours, priority}), priority_tasks (array), annual_maintenance_plan (string)
