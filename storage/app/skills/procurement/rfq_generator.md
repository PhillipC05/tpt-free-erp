---
name: Request for Quote Generator
slug: procurement.rfq_generator
version: "1.0"
category: procurement
description: Draft a professional Request for Quote (RFQ) document from required materials, quantities, and delivery requirements.
required_permissions:
  - procurement.create
affected_modules:
  - procurement
inputs:
  - name: company_name
    type: string
    description: Name of the company issuing the RFQ
    required: true
  - name: items
    type: array
    description: Array of items with description, quantity, unit, specifications
    required: true
  - name: delivery_location
    type: string
    description: Delivery address or location
    required: false
  - name: required_by_date
    type: string
    description: Required delivery date (YYYY-MM-DD)
    required: false
  - name: quote_deadline
    type: string
    description: Deadline for vendor responses (YYYY-MM-DD)
    required: false
  - name: evaluation_criteria
    type: array
    description: Criteria for vendor selection (e.g. price, delivery, quality)
    required: false
outputs:
  - name: rfq_document
    type: string
    description: Full RFQ in markdown format
  - name: line_items_table
    type: array
    description: Structured line items for the RFQ
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [procurement, rfq, automation]
---

## Task

You are a procurement officer. Draft a professional Request for Quote document.

## RFQ Structure

1. **Header** — RFQ number (auto-generate as RFQ-YYYYMMDD-001), company name, issue date, quote deadline
2. **Scope of Supply** — brief description of what is being sourced
3. **Line Items Table** — item no., description, quantity, unit, specifications
4. **Delivery Requirements** — location, date, packaging requirements
5. **Submission Instructions** — how to submit quote, required format, contact person
6. **Evaluation Criteria** — how quotes will be evaluated
7. **Terms & Conditions** — standard: price valid 30 days, payment net 30, company reserves right to reject any quote

## Output

Return JSON with: rfq_document (markdown), line_items_table (array of structured objects)
