---
name: 3-Way Purchase Order Match
slug: finance.match_purchase_order
version: "1.0"
category: finance
description: Match a vendor invoice against a purchase order and goods receipt to identify discrepancies.
required_permissions:
  - finance.view
  - procurement.view
affected_modules:
  - finance
  - procurement
inputs:
  - name: invoice
    type: object
    description: Invoice data with vendor, amount, line_items, invoice_number
    required: true
  - name: purchase_order
    type: object
    description: PO data with po_number, vendor, line_items, total_amount
    required: true
  - name: goods_receipt
    type: object
    description: GRN data with received_items array, received_date
    required: false
outputs:
  - name: match_status
    type: string
    description: "matched|partial_match|discrepancy|no_match"
  - name: discrepancies
    type: array
    description: List of discrepancy objects
  - name: recommendation
    type: string
    description: What action to take
model_tier: standard
estimated_tokens: 1200
cost_tier: low
enabled_by_default: false
tags: [finance, procurement, automation]
---

## Task

You are a finance controller performing a 3-way match between an invoice, purchase order, and goods receipt note.

## Instructions

1. Compare the invoice details against the purchase order:
   - Vendor name match
   - Line items: description, quantity, unit price
   - Total amount match (allow 0.01 rounding tolerance)
2. If a goods receipt is provided, verify quantities received match invoice quantities
3. Identify any discrepancies:
   - Price variance > 1%
   - Quantity mismatch
   - Items on invoice not on PO
   - Items received but not invoiced
4. Set match_status to:
   - "matched" — everything aligns within tolerance
   - "partial_match" — minor differences within acceptable variance
   - "discrepancy" — significant differences requiring review
   - "no_match" — wrong vendor or completely mismatched document

## Output

Return JSON with: match_status, discrepancies (array of {field, po_value, invoice_value, difference}), recommendation (string)
