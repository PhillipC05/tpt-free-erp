---
name: Sales Quote Drafter
slug: sales.draft_quote
version: "1.0"
category: sales
description: Generate a professional sales quote from CRM opportunity data and product/service details.
required_permissions:
  - sales.create
affected_modules:
  - sales
inputs:
  - name: customer
    type: object
    description: Customer object with name, company, email, address
    required: true
  - name: line_items
    type: array
    description: Array of items with product_name, description, quantity, unit_price
    required: true
  - name: validity_days
    type: integer
    description: Number of days the quote is valid
    required: false
  - name: notes
    type: string
    description: Additional terms or notes to include
    required: false
outputs:
  - name: quote_text
    type: string
    description: Formatted quote in markdown
  - name: subtotal
    type: number
  - name: tax_amount
    type: number
  - name: total
    type: number
model_tier: fast
estimated_tokens: 900
cost_tier: low
enabled_by_default: false
tags: [sales, quotes, automation]
---

## Task

You are a sales assistant. Generate a professional sales quote document.

## Instructions

1. Create a header with: Quote #, Date, Valid Until (today + validity_days, default 30)
2. Include customer details section
3. Format line items as a table: Item | Description | Qty | Unit Price | Total
4. Calculate subtotal, 15% GST (unless notes specify otherwise), and total
5. Add standard payment terms (30 days from invoice) unless notes override
6. Add any notes from the input
7. Close with a professional acceptance line

## Output

Return JSON with: quote_text (markdown string), subtotal (float), tax_amount (float), total (float)
