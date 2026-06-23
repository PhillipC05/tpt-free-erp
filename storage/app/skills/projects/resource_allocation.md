---
name: Resource Allocation Suggester
slug: projects.resource_allocation
version: "1.0"
category: projects
description: Suggests team member assignments for project tasks based on skills and availability.
required_permissions:
  - projects.read
  - hr.read
inputs:
  - name: project_id
    type: integer
    description: ID of the project whose unassigned tasks need staffing.
    required: true
  - name: available_staff
    type: array
    description: Array of staff objects, each with id, name, skills (array of strings), current_utilisation_pct (0–100), and role.
    required: true
outputs:
  - name: assignments
    type: array
    description: Array of recommended task assignments, one entry per task, with confidence scores and rationale.
model_tier: standard
estimated_tokens: 800
cost_tier: medium
enabled_by_default: false
tags: [projects, hr, staffing, resource-management, planning]
---

## Task

Analyse the unassigned tasks in a project alongside the available staff pool and produce an optimised set of assignment recommendations that balance skill fit, workload, and availability.

## Instructions

You will receive a JSON payload containing:
- `project_id`: integer identifying the project
- `tasks`: array of task objects, each with:
  - `id`: integer task ID
  - `title`: string
  - `required_skills`: array of strings (skills needed to complete the task)
  - `estimated_hours`: number
  - `priority`: one of `"low"`, `"medium"`, `"high"`, `"critical"`
  - `due_date`: ISO 8601 date string
- `available_staff`: array of staff objects, each with:
  - `id`: integer
  - `name`: string
  - `role`: string
  - `skills`: array of strings
  - `current_utilisation_pct`: number (0–100; 100 = fully booked)
  - `hours_available`: number of hours free this sprint/week

Follow these steps exactly:

1. **Skill matching**: For each task, score every staff member by counting how many of the task's `required_skills` appear in the staff member's `skills` list. Express this as `skill_match_score = matched_skills / total_required_skills` (0.0–1.0). If `required_skills` is empty, set `skill_match_score = 1.0` for all staff.
2. **Availability scoring**: Compute `availability_score = 1 - (current_utilisation_pct / 100)`. Staff at 100% utilisation score 0.0.
3. **Capacity check**: Exclude any staff member whose `hours_available` is less than `estimated_hours * 0.5` (they cannot meaningfully take the task).
4. **Composite score**: `composite = (skill_match_score * 0.6) + (availability_score * 0.4)`.
5. **Select best match**: Pick the staff member with the highest `composite` score for each task. In a tie, prefer lower `current_utilisation_pct`.
6. **Confidence score**: Set `confidence_score` = the winning `composite` score, rounded to 2 decimal places.
7. **Rationale**: Write a one-sentence rationale explaining why this person was chosen (mention skill fit and availability).
8. **Priority ordering**: Sort the output array so `"critical"` tasks appear first, then `"high"`, `"medium"`, `"low"`.
9. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

If no suitable staff member exists for a task (all excluded by capacity check), set `recommended_assignee` to `null` and `confidence_score` to `0.00`, with rationale `"No available staff with sufficient capacity."`.

## Output Schema

Return a single JSON object:

```json
{
  "project_id": 0,
  "assignments": [
    {
      "task_id": 0,
      "task_title": "",
      "recommended_assignee": {
        "id": 0,
        "name": "",
        "role": ""
      },
      "confidence_score": 0.00,
      "skill_match_score": 0.00,
      "availability_score": 0.00,
      "rationale": ""
    }
  ],
  "unassignable_task_count": 0
}
```
