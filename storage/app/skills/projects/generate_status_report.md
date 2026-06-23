---
name: Project Status Report Generator
slug: projects.generate_status_report
version: "1.0"
category: projects
description: Generate a narrative project status update from task completion, time entries, and milestone data.
required_permissions:
  - projects.view
affected_modules:
  - projects
inputs:
  - name: project
    type: object
    description: Project object with name, status, start_date, end_date, priority
    required: true
  - name: task_summary
    type: object
    description: Object with total_tasks, completed, in_progress, overdue, cancelled
    required: true
  - name: time_summary
    type: object
    description: Object with hours_logged, hours_budgeted, hours_remaining
    required: false
  - name: milestones
    type: array
    description: Array of milestone objects with name, due_date, status
    required: false
  - name: blockers
    type: string
    description: Any current blockers or risks
    required: false
outputs:
  - name: report
    type: string
    description: Full status report in markdown
  - name: rag_status
    type: string
    description: "red | amber | green — overall project health"
model_tier: standard
estimated_tokens: 1200
cost_tier: low
enabled_by_default: false
tags: [projects, reporting, automation]
---

## Task

You are a project manager. Write a concise project status report for stakeholders.

## Instructions

1. Open with project name, current status, and RAG rating (Red/Amber/Green)
2. Write a 2-3 sentence executive summary
3. Summarise task progress (completed/total, overdue count)
4. If time data provided, note budget vs actual hours
5. List upcoming milestones and their status
6. Note any blockers or risks
7. End with a brief "Next Steps" section (2-3 bullet points)

## RAG Determination

- Green: < 5% overdue tasks, on time, no blockers
- Amber: 5-20% overdue, or minor time overrun, or blockers being managed
- Red: > 20% overdue, significant time overrun, or unresolved critical blockers

## Output

Return JSON with: report (markdown string), rag_status ("red" | "amber" | "green")
