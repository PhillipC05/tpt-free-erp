---
name: Account Reconciliation
slug: finance.reconcile_accounts
version: "1.0"
category: finance
description: Flag unmatched transactions between the general ledger and a bank statement.
required_permissions:
  - finance.view
  - finance.edit
affected_modules:
  - finance
inputs:
  - name: gl_transactions
    type: array
    description: GL transactions with id, date, description, amount, type (debit/credit)
    required: true
  - name: bank_statement
    type: array
    description: Bank statement lines with date, description, amount, reference
    required: true
  - name: tolerance
    type: number
    description: Matching tolerance in currency units (default 0.01)
    required: false
outputs:
  - name: matched
    type: array
    description: Successfully matched pairs
  - name: unmatched_gl
    type: array
    description: GL transactions with no bank match
  - name: unmatched_bank
    type: array
    description: Bank lines with no GL match
  - name: summary
    type: string
    description: Reconciliation summary
model_tier: standard
estimated_tokens: 1200
cost_tier: low
enabled_by_default: false
tags: [finance, reconciliation, automation]
---

## Task

You are a bookkeeper performing bank reconciliation. Match GL transactions to bank statement lines.

## Matching Logic

1. Attempt exact match on amount (within tolerance) AND date (same day or within 3 days)
2. If exact fails, attempt fuzzy match on amount + description keyword overlap
3. Mark as matched only when confidence is high
4. Remaining unmatched items go into separate lists

## Output

Return JSON with:
- matched: array of {gl_id, bank_reference, amount, match_confidence}
- unmatched_gl: array of GL transactions not matched
- unmatched_bank: array of bank lines not matched
- summary: string with counts and total unmatched value
