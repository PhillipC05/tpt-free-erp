---
name: Contract Terms Reviewer
slug: contracts.review_terms
version: "1.0"
category: contracts
description: Flag unusual, missing, or potentially unfavourable clauses in a contract against standard terms.
required_permissions:
  - contracts.view
affected_modules:
  - contracts
inputs:
  - name: contract_text
    type: string
    description: Full or partial contract text to review
    required: true
  - name: contract_type
    type: string
    description: "nda | service_agreement | supply_agreement | employment | lease | other"
    required: false
  - name: our_role
    type: string
    description: "buyer | seller | employer | employee | landlord | tenant"
    required: false
outputs:
  - name: risk_level
    type: string
    description: "low | medium | high"
  - name: flags
    type: array
    description: Flagged clauses or missing provisions
  - name: missing_standard_clauses
    type: array
    description: Clauses expected but not found
  - name: summary
    type: string
    description: Brief review summary
model_tier: powerful
estimated_tokens: 2000
cost_tier: medium
enabled_by_default: false
tags: [contracts, legal, review]
---

## Task

You are a contract reviewer. Identify potential issues and missing provisions in this contract text.

## Standard Clauses to Check (by type)

**All contracts**: termination clause, governing law, dispute resolution, liability limitation, confidentiality

**Service agreement**: SLA/deliverables, payment terms, IP ownership, change control

**Supply agreement**: delivery terms, inspection rights, warranty, price escalation

**NDA**: definition of confidential information, exclusions, return of materials, term

## Red Flags to Look For

- Unlimited liability on our side
- Auto-renewal without notice requirement
- Unilateral amendment rights by other party
- Unusually short payment terms for us (< 14 days)
- No dispute resolution mechanism
- Overly broad IP assignment
- Missing force majeure clause

## Output

Return JSON with: risk_level, flags (array of {clause_or_issue, risk, our_role_impact}), missing_standard_clauses (array), summary (string)
