---
name: Performance Review Drafter
slug: hr.draft_performance_review
version: "1.0"
category: hr
description: Generate a structured performance review summary from attendance, project completion, and timesheet data.
required_permissions:
  - hr.view
  - hr.edit
affected_modules:
  - hr
  - projects
inputs:
  - name: employee
    type: object
    description: Employee object with name, job_title, department, start_date
    required: true
  - name: review_period
    type: object
    description: Object with start_date and end_date for the review period
    required: true
  - name: attendance_summary
    type: object
    description: Object with days_present, days_absent, late_arrivals, avg_hours_per_day
    required: false
  - name: project_summary
    type: object
    description: Object with tasks_completed, tasks_overdue, projects_contributed, on_time_rate
    required: false
  - name: additional_notes
    type: string
    description: Manager notes or specific achievements to include
    required: false
outputs:
  - name: review_text
    type: string
    description: Full performance review in markdown
  - name: overall_rating
    type: string
    description: "exceeds_expectations | meets_expectations | needs_improvement"
model_tier: standard
estimated_tokens: 1500
cost_tier: low
enabled_by_default: false
tags: [hr, performance, automation]
---

## Task

You are an HR specialist writing a formal performance review for an employee.

## Instructions

1. Open with employee name, title, department, and review period
2. Write an "Overall Performance" paragraph (3-4 sentences based on the data)
3. If attendance data is provided, write an "Attendance & Reliability" section
4. If project data is provided, write a "Work Quality & Delivery" section with specifics (tasks completed, on-time rate)
5. Include any additional notes from the manager
6. Write "Areas for Development" (1-2 constructive suggestions based on the data)
7. Close with a forward-looking paragraph
8. Determine overall_rating based on: on_time_rate > 85% and attendance > 90% = exceeds/meets; lower = needs_improvement

## Style Guide

- Professional, balanced, and constructive
- Specific and evidence-based (cite the numbers)
- Avoid personal judgements — focus on observable behaviour
- 400-600 words

## Output

Return JSON with: review_text (markdown), overall_rating (string)
