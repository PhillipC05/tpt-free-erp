---
name: Project Retrospective Summary
slug: projects.retrospective_summary
version: "1.0"
category: projects
description: Generate a structured retrospective summary from project tasks, time entries, and team feedback.
required_permissions:
  - projects.view
affected_modules:
  - projects
inputs:
  - name: project_name
    type: string
    description: Name of the project
    required: true
  - name: tasks
    type: array
    description: Completed and cancelled tasks with time spent and status history
    required: true
  - name: time_entries
    type: array
    description: Time tracking data for the project
    required: false
  - name: feedback
    type: array
    description: Team member feedback or notes
    required: false
outputs:
  - name: retrospective
    type: object
    description: Structured retro summary with what went well, improvements, and action items
model_tier: standard
estimated_tokens: 1000
cost_tier: medium
enabled_by_default: false
tags: [projects, retrospective, agile, team]
---

## Task

You are a project management facilitator. Generate a comprehensive retrospective summary for the completed project phase.

## Instructions

1. Analyse the project data to identify:
   - What went well (achievements, smooth processes, team strengths)
   - What didn't go well (blockers, delays, communication issues, scope creep)
   - What can be improved (process changes, tool improvements, team practices)
2. Calculate key metrics:
   - On-time completion rate (% of tasks completed by deadline)
   - Estimated vs actual effort variance
   - Scope change frequency
   - Average cycle time per task
3. Generate 3-5 actionable improvement items with owners and target dates
4. Identify reusable patterns for future projects
5. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: project_name, retrospective_date, metrics (on_time_rate, effort_variance, scope_changes, avg_cycle_time), went_well (array), needs_improvement (array), action_items (array with description, owner, target_date), reusable_patterns (array)
