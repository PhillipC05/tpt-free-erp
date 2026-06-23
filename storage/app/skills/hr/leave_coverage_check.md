---
name: Leave Coverage Checker
slug: hr.leave_coverage_check
version: "1.0"
category: hr
description: Assess whether a leave request can be approved based on team availability and minimum staffing requirements.
required_permissions:
  - hr.view
  - hr.edit
affected_modules:
  - hr
inputs:
  - name: leave_request
    type: object
    description: Request with employee_name, department, start_date, end_date, leave_type
    required: true
  - name: team_schedule
    type: array
    description: Array of team members with name, role, leave_dates (array of dates already approved)
    required: true
  - name: min_staff_required
    type: integer
    description: Minimum number of staff required in the department at any time
    required: false
outputs:
  - name: recommendation
    type: string
    description: "approve | approve_with_conditions | defer | decline"
  - name: coverage_gaps
    type: array
    description: Dates where coverage would fall below minimum
  - name: reasoning
    type: string
    description: Explanation of the recommendation
model_tier: fast
estimated_tokens: 700
cost_tier: low
enabled_by_default: false
tags: [hr, leave, automation]
---

## Task

You are an HR coordinator. Assess if this leave request should be approved.

## Logic

1. Identify all dates covered by the leave request
2. For each date, count how many team members are already on leave
3. If approving this request would leave < min_staff_required (default 1) available → identify as gap
4. Recommend:
   - approve: No coverage gaps
   - approve_with_conditions: Minor gaps on non-critical days (weekends/public holidays)
   - defer: Gaps but request could be moved by 1-2 weeks to avoid them
   - decline: Gaps with no reasonable workaround

## Output

Return JSON with: recommendation, coverage_gaps (array of {date, available_staff}), reasoning
