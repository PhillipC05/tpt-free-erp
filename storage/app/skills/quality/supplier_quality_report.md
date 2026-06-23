---
name: Supplier Quality Report
slug: quality.supplier_quality_report
version: "1.0"
category: quality
description: Aggregates defect rates and non-conformance trends by vendor/supplier.
required_permissions:
  - quality.read
  - procurement.read
inputs:
  - name: non_conformances
    type: array
    description: Array of non-conformance records, each with vendor_id, vendor_name, severity, date, and resolved flag.
    required: true
  - name: period_days
    type: integer
    description: Number of days to look back from the most recent record date. Defaults to 90 if omitted.
    required: false
outputs:
  - name: report
    type: object
    description: Vendor quality rankings with defect rates, NC totals, resolution rates, risk ratings, and an overall quality score.
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [quality, suppliers, non-conformance, procurement, reporting]
---

## Task

Aggregate non-conformance (NC) records by vendor to produce a ranked supplier quality report. Identify which suppliers are driving the most quality issues, how severe those issues are, and how well they are resolving them.

## Instructions

You will receive a JSON payload containing:
- `non_conformances`: array of NC objects, each with:
  - `id`: integer
  - `vendor_id`: integer
  - `vendor_name`: string
  - `severity`: one of `"minor"`, `"major"`, `"critical"`
  - `date`: ISO 8601 date string of when the NC was raised
  - `resolved`: boolean — true if the NC has been closed/resolved
  - `resolution_days`: integer or null — days taken to resolve (null if unresolved)
- `period_days`: optional integer, default `90`

Follow these steps exactly:

1. **Determine analysis window**: Find the most recent `date` across all records. The analysis window is from `(most_recent_date - period_days)` to `most_recent_date`, inclusive.
2. **Filter by period**: Retain only NCs where `date` falls within the analysis window.
3. **Group by vendor**: For each unique `vendor_id`, collect all their NCs from the filtered set.
4. **Per-vendor metrics**:
   - `total_ncs` = count of all NCs for this vendor.
   - `critical_count`, `major_count`, `minor_count` = counts by severity.
   - `resolved_pct` = `(resolved NCs / total_ncs) * 100`, rounded to 1 decimal place.
   - `avg_resolution_days` = mean of `resolution_days` for resolved NCs only (null if none resolved).
   - `weighted_score` = `(critical_count * 3) + (major_count * 2) + (minor_count * 1)`. Higher = worse quality.
5. **Defect rate**: `defect_rate = total_ncs / period_days * 30` — normalised to NCs per 30-day period, rounded to 2 decimal places.
6. **Risk rating**:
   - `critical_count >= 2` OR `weighted_score >= 10` → `"critical"`
   - `major_count >= 3` OR `weighted_score >= 6` → `"high"`
   - `total_ncs >= 3` → `"medium"`
   - Otherwise → `"low"`
7. **Rank vendors**: Sort by `weighted_score` descending (most problematic first). Assign `rank` as 1-based position.
8. **Overall quality score**: `100 - (sum of all weighted_scores / total_ncs_in_period * 10)`, clamped to [0, 100], rounded to 1 decimal place. Higher = better overall supplier quality.
9. Return **only** the JSON object matching the Output Schema — no prose, no markdown fences.

If `non_conformances` is empty or none fall in the period, return an empty `vendor_rankings` array and `overall_quality_score: 100`.

## Output Schema

```json
{
  "report": {
    "period_days": 90,
    "analysis_window_start": "YYYY-MM-DD",
    "analysis_window_end": "YYYY-MM-DD",
    "total_ncs_in_period": 0,
    "overall_quality_score": 100.0,
    "vendor_rankings": [
      {
        "rank": 1,
        "vendor_id": 0,
        "vendor_name": "",
        "total_ncs": 0,
        "critical_count": 0,
        "major_count": 0,
        "minor_count": 0,
        "defect_rate": 0.00,
        "resolved_pct": 0.0,
        "avg_resolution_days": null,
        "weighted_score": 0,
        "risk_rating": "low|medium|high|critical"
      }
    ]
  }
}
```
