---
name: Audit Trail Summariser
slug: finance.audit_trail_summary
version: "1.0"
category: finance
description: Summarise all changes made to a financial record for auditor review.
required_permissions:
  - finance.view
affected_modules:
  - finance
inputs:
  - name: record_type
    type: string
    description: Type of record being audited (e.g. invoice, transaction, account)
    required: true
  - name: record_id
    type: integer
    description: ID of the record
    required: true
  - name: audit_log
    type: array
    description: Array of audit events with timestamp, user, action, field_changed, old_value, new_value
    required: true
  - name: review_period
    type: object
    description: Optional period filter with start_date and end_date
    required: false
outputs:
  - name: summary
    type: string
    description: Plain-English narrative of all changes
  - name: risk_flags
    type: array
    description: Suspicious patterns flagged for auditor attention
  - name: change_count
    type: integer
    description: Total number of changes in the period
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [finance, audit, compliance]
---

## Task

You are an auditor's assistant. Summarise the change history for this financial record.

## Instructions

1. Count total changes in the log
2. Group changes by user and action type
3. Write a chronological narrative of significant changes
4. Flag suspicious patterns:
   - Same field changed multiple times in short succession
   - Amount changes > 10% of original value
   - Changes made outside business hours
   - Changes by unexpected users (admin changes to user-owned records)
   - Deleted/voided records
5. Keep the narrative professional and factual

## Output

Return JSON with: summary (string), risk_flags (array of {description, severity: low/medium/high}), change_count (int)
