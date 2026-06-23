---
name: Lead Nurture Sequence Generator
slug: marketing.lead_nurture_sequence
version: "1.0"
category: marketing
description: Generate a multi-touch email nurture sequence tailored to a lead's profile and stage.
required_permissions:
  - marketing.create
  - sales.view
affected_modules:
  - marketing
  - sales
inputs:
  - name: lead
    type: object
    description: Lead with name, company, industry, pain_points, current_stage
    required: true
  - name: product_or_service
    type: string
    description: What you are selling
    required: true
  - name: sequence_length
    type: integer
    description: Number of touches in the sequence (default 5)
    required: false
  - name: sending_cadence_days
    type: integer
    description: Days between emails (default 3)
    required: false
outputs:
  - name: sequence
    type: array
    description: Email sequence with subject, body, and send timing
  - name: sequence_goal
    type: string
    description: What this sequence aims to achieve
model_tier: standard
estimated_tokens: 2000
cost_tier: medium
enabled_by_default: false
tags: [marketing, email, leads, automation]
---

## Task

You are a marketing specialist. Create a personalised lead nurture email sequence.

## Sequence Framework

Use the AIDA progression: Awareness → Interest → Desire → Action

**Email 1 (Day 0)**: Pain point acknowledgement — show you understand their challenge
**Email 2 (Day 3)**: Educational value — share insight relevant to their industry
**Email 3 (Day 6)**: Social proof — relevant case study or testimonial
**Email 4 (Day 9)**: Product/solution fit — how you specifically solve their pain
**Email 5 (Day 12)**: Call to action — demo, trial, or consultation offer

## Personalisation Rules

- Use lead's industry to choose relevant examples
- Reference their pain_points in each email
- Keep emails 100-150 words each
- Professional but conversational tone
- Include one clear CTA per email

## Output

Return JSON with: sequence (array of {email_number, send_day, subject, body, cta}), sequence_goal (string)
