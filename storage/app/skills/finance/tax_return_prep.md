---
name: Tax Return Preparation
slug: finance.tax_return_prep
version: "1.0"
category: finance
description: Aggregates taxable transactions by period, computes GST/VAT totals, and prepares a structured filing summary.
required_permissions:
  - finance.read
inputs:
  - name: period_start
    type: string
    description: Start date of the tax period in ISO 8601 format (YYYY-MM-DD)
    required: true
  - name: period_end
    type: string
    description: End date of the tax period in ISO 8601 format (YYYY-MM-DD)
    required: true
  - name: tax_rate
    type: number
    description: GST/VAT rate as a decimal (e.g. 0.15 for 15%). Defaults to 0.15 if omitted.
    required: false
outputs:
  - name: filing_summary
    type: object
    description: Structured tax filing summary including sales, purchases, and net payable amounts.
model_tier: standard
estimated_tokens: 800
cost_tier: medium
enabled_by_default: false
tags: [finance, tax, gst, vat, compliance, reporting]
---

## Task

Aggregate all taxable transactions within the given date range, compute GST/VAT collected on sales and paid on purchases, and return a structured filing summary ready for use in a tax return submission.

## Instructions

You will receive a JSON payload containing:
- `period_start`: ISO 8601 date string marking the start of the tax period
- `period_end`: ISO 8601 date string marking the end of the tax period
- `tax_rate` (optional): decimal GST/VAT rate — default to `0.15` if not provided
- `transactions`: array of transaction objects, each containing at minimum:
  - `id`: unique transaction identifier
  - `type`: `"sale"` or `"purchase"`
  - `date`: ISO 8601 date of the transaction
  - `amount_excl_tax`: the pre-tax amount
  - `tax_amount`: the tax component recorded on the transaction
  - `is_taxable`: boolean indicating whether the transaction is subject to tax

Follow these steps exactly:

1. **Filter by period**: Retain only transactions where `date` falls within `[period_start, period_end]` inclusive.
2. **Filter taxable only**: From the filtered set, retain only transactions where `is_taxable` is `true`.
3. **Split by type**:
   - Collect all `type === "sale"` transactions into a sales list.
   - Collect all `type === "purchase"` transactions into a purchases list.
4. **Compute sales totals**:
   - `taxable_sales` = sum of `amount_excl_tax` across all sale transactions.
   - `gst_collected` = sum of `tax_amount` across all sale transactions. Cross-check: this should approximate `taxable_sales * tax_rate` (within rounding tolerance of ±$0.10 per transaction).
5. **Compute purchase totals**:
   - `taxable_purchases` = sum of `amount_excl_tax` across all purchase transactions.
   - `gst_paid` = sum of `tax_amount` across all purchase transactions.
6. **Compute net payable**:
   - `net_payable` = `gst_collected` - `gst_paid`. A positive value means tax is owed to the authority; a negative value means a refund is due.
7. **Round all monetary values** to 2 decimal places.
8. **Build the output object** exactly matching the Output Schema below.
9. Return **only** the JSON object — no prose, no markdown fences, no explanation.

If `transactions` is empty or no transactions fall in the period, return zeroes for all monetary fields and set `transaction_count` to 0.

## Output Schema

Return a single JSON object with the following structure:

```json
{
  "filing_summary": {
    "period_start": "YYYY-MM-DD",
    "period_end": "YYYY-MM-DD",
    "tax_rate": 0.15,
    "taxable_sales": 0.00,
    "taxable_purchases": 0.00,
    "gst_collected": 0.00,
    "gst_paid": 0.00,
    "net_payable": 0.00,
    "transaction_count": 0,
    "sales_transaction_count": 0,
    "purchase_transaction_count": 0
  }
}
```

All monetary values must be numbers (not strings). Do not include currency symbols.
