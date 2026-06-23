---
name: Non-Conformance Root Cause Analyser
slug: quality.analyse_nonconformance
version: "1.0"
category: quality
description: Classify the root cause of a quality non-conformance and suggest corrective actions.
required_permissions:
  - quality.view
  - quality.edit
affected_modules:
  - quality
inputs:
  - name: nonconformance
    type: object
    description: NC record with title, description, detected_at, product_name, quantity_affected, severity
    required: true
  - name: similar_ncs
    type: array
    description: Array of recent similar NCs (optional — for pattern detection)
    required: false
outputs:
  - name: root_cause_category
    type: string
    description: "material | process | human_error | equipment | measurement | environment"
  - name: root_cause_description
    type: string
    description: Detailed root cause analysis
  - name: corrective_actions
    type: array
    description: Recommended corrective actions
  - name: recurrence_risk
    type: string
    description: "low | medium | high"
model_tier: standard
estimated_tokens: 900
cost_tier: low
enabled_by_default: false
tags: [quality, nonconformance, automation]
---

## Task

You are a quality engineer. Analyse this non-conformance and determine its root cause.

## Root Cause Categories

- **material**: Defective input materials, wrong specifications, supplier issue
- **process**: Procedure not followed, incorrect process parameters, sequence error
- **human_error**: Training gap, fatigue, misunderstanding of requirements
- **equipment**: Machine malfunction, calibration drift, worn tooling
- **measurement**: Incorrect measurement method, gauge error, sampling issue
- **environment**: Temperature, humidity, contamination, workspace condition

## Instructions

1. Read the NC description carefully
2. Identify the most likely root cause category
3. Write a specific root cause description (2-3 sentences)
4. List 3-5 corrective actions ordered by priority
5. If similar_ncs provided, check for patterns (recurring category = high recurrence risk)
6. Assess recurrence risk based on category and history

## Output

Return JSON with: root_cause_category, root_cause_description, corrective_actions (array of strings), recurrence_risk
