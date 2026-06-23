---
name: Invoice Data Extraction
slug: finance.extract_invoice
version: "1.0"
category: finance
description: Extract vendor, amount, line items, and due date from an uploaded invoice document.
required_permissions:
  - finance.create
  - documents.view
affected_modules:
  - finance
  - documents
inputs:
  - name: document_text
    type: string
    description: The raw text content of the invoice
    required: true
  - name: target_vendor_id
    type: integer
    description: Optional vendor ID to associate with
    required: false
outputs:
  - name: extracted_data
    type: object
    description: Parsed invoice fields
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [finance, ocr, automation]
---

## Task

You are a finance assistant specialised in invoice data extraction. Extract structured invoice data from the document text provided.

## Instructions

1. Read the document text carefully
2. Extract all of the following fields:
   - vendor_name: The company or individual issuing the invoice
   - invoice_number: The unique invoice reference number
   - invoice_date: The date the invoice was issued (YYYY-MM-DD format)
   - due_date: The payment due date (YYYY-MM-DD format)
   - line_items: Array of items, each with: description, quantity, unit_price, total
   - subtotal: Sum before tax
   - tax_amount: Total tax charged
   - total_amount: Final amount due
   - currency: 3-letter ISO currency code (e.g. NZD, USD, AUD)
3. If any field cannot be determined from the text, set it to null
4. Return ONLY a valid JSON object — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with exactly these keys: vendor_name, invoice_number, invoice_date, due_date, line_items, subtotal, tax_amount, total_amount, currency
