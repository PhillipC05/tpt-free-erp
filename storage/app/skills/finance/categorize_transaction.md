---
name: Transaction Categorisation
slug: finance.categorize_transaction
version: "1.0"
category: finance
description: Auto-categorise a bank transaction by matching its description to an appropriate chart of accounts category.
required_permissions:
  - finance.view
  - finance.edit
affected_modules:
  - finance
inputs:
  - name: description
    type: string
    description: The transaction description as it appears on the bank statement
    required: true
  - name: amount
    type: number
    description: Transaction amount (positive = debit, negative = credit)
    required: true
  - name: available_accounts
    type: array
    description: List of account objects with id, code, name, type from the chart of accounts
    required: true
outputs:
  - name: account_id
    type: integer
    description: The best matching account ID
  - name: confidence
    type: number
    description: Confidence score 0.0 to 1.0
  - name: reasoning
    type: string
    description: Brief explanation of the categorisation
model_tier: fast
estimated_tokens: 600
cost_tier: low
enabled_by_default: false
tags: [finance, automation, categorisation]
---

## Task

You are a bookkeeping assistant. Categorise a bank transaction by selecting the most appropriate account from the provided chart of accounts.

## Instructions

1. Read the transaction description and amount carefully
2. Review the available accounts list
3. Select the single best matching account based on:
   - The nature of the transaction (expense, revenue, asset, liability)
   - Keywords in the description that match account names or typical uses
   - The sign of the amount (positive = money out, negative = money in)
4. Assign a confidence score from 0.0 (pure guess) to 1.0 (certain match)
5. Write a brief one-sentence reasoning

## Output

Return a JSON object with: account_id (integer), confidence (float 0-1), reasoning (string)
