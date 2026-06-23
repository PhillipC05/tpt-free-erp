---
name: CRM Lead Scorer
slug: sales.score_crm_lead
version: "1.0"
category: sales
description: Score a CRM lead from 0-100 based on profile completeness, company size, industry fit, and engagement signals.
required_permissions:
  - sales.view
affected_modules:
  - sales
inputs:
  - name: lead
    type: object
    description: Lead object with name, company, industry, email, phone, source, notes, created_at
    required: true
  - name: ideal_customer_profile
    type: object
    description: Optional ICP criteria - target_industries, min_company_size, preferred_sources
    required: false
outputs:
  - name: score
    type: integer
    description: Lead score 0-100
  - name: tier
    type: string
    description: "hot (80+) | warm (50-79) | cold (below 50)"
  - name: score_breakdown
    type: object
    description: Score components
  - name: recommended_action
    type: string
    description: Suggested next step
model_tier: fast
estimated_tokens: 700
cost_tier: low
enabled_by_default: false
tags: [sales, crm, automation]
---

## Task

You are a sales analyst. Score this CRM lead and recommend the next best action.

## Scoring Rubric

Assign points in each category:

**Profile Completeness (max 25 points)**
- Has company name: 5
- Has phone number: 5
- Has industry: 5
- Has detailed notes: 5
- Has clear pain point/need mentioned: 5

**Company Fit (max 25 points)**
- Industry matches ICP target industries: 10
- Company appears to be SME or enterprise (not individual): 10
- Has website/LinkedIn: 5

**Engagement Quality (max 25 points)**
- Lead source is inbound (website, referral, event): 15
- Lead source is outbound but qualified: 8
- Cold/unqualified outbound: 2

**Urgency Signals (max 25 points)**
- Notes mention specific project or timeline: 15
- Notes mention budget: 10
- No urgency signals: 0

## Output

Return JSON with: score (int 0-100), tier (hot/warm/cold), score_breakdown (object with component scores), recommended_action (string)
