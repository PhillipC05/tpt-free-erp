---
name: Contract Term Extraction
slug: documents.extract_contract_terms
version: "1.0"
category: documents
description: Extract key terms, dates, obligations, and risk clauses from a contract document.
required_permissions:
  - contracts.view
  - documents.view
affected_modules:
  - contracts
  - documents
inputs:
  - name: document_text
    type: string
    description: The raw text content of the contract
    required: true
outputs:
  - name: extracted_terms
    type: object
    description: Structured contract terms and risk assessment
model_tier: powerful
estimated_tokens: 1500
cost_tier: medium
enabled_by_default: false
tags: [contracts, legal, nlp, automation]
---

## Task

You are a legal analysis assistant specialised in contract review. Extract key terms and assess risk from the contract text provided.

## Instructions

1. Read the contract text carefully
2. Extract all of the following:
   - party_a: First contracting party name
   - party_b: Second contracting party name
   - contract_type: Type of agreement (sale, purchase, service, NDA, employment, lease, other)
   - effective_date: Date the contract takes effect (YYYY-MM-DD)
   - expiration_date: Contract end date if specified (YYYY-MM-DD or null)
   - auto_renewal: Whether the contract auto-renews (boolean)
   - total_value: Total contract value if stated (number or null)
   - payment_terms: Payment schedule description
   - termination_clause: How either party can terminate
   - liability_cap: Maximum liability amount if specified
   - governing_law: Jurisdiction that governs the contract
   - key_obligations: Array of major obligations for each party
   - risk_clauses: Array of potentially unfavorable clauses with risk level (low/medium/high)
   - renewal_notice_days: Days notice required before renewal or termination
3. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: party_a, party_b, contract_type, effective_date, expiration_date, auto_renewal, total_value, payment_terms, termination_clause, liability_cap, governing_law, key_obligations, risk_clauses, renewal_notice_days
