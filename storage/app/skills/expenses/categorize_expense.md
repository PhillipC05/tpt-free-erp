---
name: Expense Categoriser
slug: expenses.categorize_expense
version: "1.0"
category: expenses
description: Classify an expense item into the correct expense category based on description, merchant, and amount.
required_permissions:
  - expenses.create
affected_modules:
  - expenses
inputs:
  - name: description
    type: string
    description: Expense description or merchant name
    required: true
  - name: amount
    type: number
    description: Expense amount
    required: true
  - name: available_categories
    type: array
    description: List of expense category objects with id, name, description
    required: true
outputs:
  - name: category_id
    type: integer
    description: Best matching category ID
  - name: confidence
    type: number
    description: Confidence score 0.0 to 1.0
  - name: reasoning
    type: string
    description: Brief explanation
  - name: is_reimbursable
    type: boolean
    description: Whether this appears to be a reimbursable business expense
model_tier: fast
estimated_tokens: 500
cost_tier: low
enabled_by_default: false
tags: [expenses, automation, categorisation]
---

## Task

You are an expense management assistant. Classify the expense into the appropriate category.

## Instructions

1. Read the expense description and merchant name
2. Review the available categories
3. Match based on:
   - Common merchant types (e.g. "Uber" = travel, "Countdown" = supplies/meals)
   - Keywords in the description
   - Typical amounts for each category
4. Determine if this is a reimbursable business expense (not personal items)
5. Assign confidence score

## Output

Return JSON with: category_id (int), confidence (float), reasoning (string), is_reimbursable (bool)
