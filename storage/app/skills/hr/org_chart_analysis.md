---
name: Org Chart Analysis
slug: hr.org_chart_analysis
version: "1.0"
category: hr
description: Analyses reporting structure for span of control issues, deep chains, and single points of failure.
required_permissions:
  - hr.read
inputs:
  - name: employees
    type: array
    description: Array of employee objects, each with id, manager_id (null for root), department, and title.
    required: true
outputs:
  - name: analysis
    type: object
    description: Span of control stats, deep reporting chains, single points of failure, and recommendations.
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [hr, org-chart, structure, management, analytics]
---

## Task

Traverse the employee reporting hierarchy to identify structural problems: managers with too many or too few direct reports, chains of command that are dangerously long, and individuals whose absence would leave entire sub-trees without management coverage.

## Instructions

You will receive a JSON payload containing:
- `employees`: array of employee objects, each with:
  - `id`: integer
  - `manager_id`: integer or null (null indicates a root/CEO-level employee)
  - `department`: string
  - `title`: string
  - `name`: string

Follow these steps exactly:

1. **Build the tree**: Construct an adjacency map where each employee's `id` maps to a list of their direct report IDs.
2. **Span of control**:
   - For every employee who is a manager (has at least one direct report), count their direct reports.
   - `avg_span_of_control` = mean direct-report count across all managers, rounded to 1 decimal place.
   - Flag **over-spanned managers**: those with more than 8 direct reports.
   - Flag **under-spanned managers**: those with only 1 direct report (potential unnecessary management layer).
3. **Deep chains**: Compute the depth of every employee in the hierarchy (root = depth 1). Flag any employee at depth > 6 as part of a deep chain. Return the top 5 deepest employees with their full chain depth and department.
4. **Single points of failure**: An employee is a single point of failure if:
   - They are a manager (have direct reports), AND
   - Their own `manager_id` is null, OR their removal would leave their direct reports with no other manager in the same department.
   - In practice for this analysis: flag any manager who is the **sole manager in their department**.
5. **Department analysis**: For each department, compute: total headcount, number of managers, and manager-to-staff ratio.
6. **Recommendations**: Generate 3–5 concise recommendations based on findings. Prioritise by severity (over-spanned > deep chains > single points of failure > under-spanned).
7. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

If `employees` is empty, return an empty analysis with zeroes.

## Output Schema

```json
{
  "analysis": {
    "total_employees": 0,
    "total_managers": 0,
    "avg_span_of_control": 0.0,
    "over_spanned_managers": [
      {
        "id": 0,
        "name": "",
        "title": "",
        "department": "",
        "direct_report_count": 0
      }
    ],
    "under_spanned_managers": [
      {
        "id": 0,
        "name": "",
        "title": "",
        "department": "",
        "direct_report_count": 0
      }
    ],
    "deep_chains": [
      {
        "id": 0,
        "name": "",
        "title": "",
        "department": "",
        "depth": 0
      }
    ],
    "single_points_of_failure": [
      {
        "id": 0,
        "name": "",
        "title": "",
        "department": "",
        "reports_at_risk": 0
      }
    ],
    "department_summary": [
      {
        "department": "",
        "headcount": 0,
        "manager_count": 0,
        "manager_to_staff_ratio": ""
      }
    ],
    "recommendations": [""]
  }
}
```
