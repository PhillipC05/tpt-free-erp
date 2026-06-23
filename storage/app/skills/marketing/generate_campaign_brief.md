---
name: Marketing Campaign Brief Generator
slug: marketing.generate_campaign_brief
version: "1.0"
category: marketing
description: Generate a structured marketing campaign brief from product details, target audience, and business goals.
required_permissions:
  - marketing.create
affected_modules:
  - marketing
inputs:
  - name: campaign_name
    type: string
    description: Working name for the campaign
    required: true
  - name: product_or_service
    type: string
    description: What is being promoted
    required: true
  - name: target_audience
    type: string
    description: Description of the target audience
    required: true
  - name: campaign_goal
    type: string
    description: Primary goal — lead_gen, brand_awareness, retention, upsell
    required: true
  - name: budget
    type: number
    description: Campaign budget in base currency
    required: false
  - name: duration_weeks
    type: integer
    description: Campaign duration in weeks
    required: false
outputs:
  - name: brief
    type: string
    description: Full campaign brief in markdown
  - name: key_messages
    type: array
    description: 3-5 core messages for the campaign
  - name: suggested_channels
    type: array
    description: Recommended channels based on goal and audience
model_tier: standard
estimated_tokens: 1200
cost_tier: low
enabled_by_default: false
tags: [marketing, campaigns, automation]
---

## Task

You are a marketing strategist. Write a campaign brief that a creative team can execute from.

## Brief Structure

1. **Campaign Overview** — name, objective, duration, budget
2. **Target Audience** — description, pain points, motivations
3. **Key Messages** — 3-5 core messages ranked by priority
4. **Channels** — recommended channels with rationale (email, LinkedIn, Google Ads, etc.)
5. **Success Metrics** — KPIs to track (leads, impressions, conversions, CAC)
6. **Creative Direction** — tone, visual style, content formats to use

## Channel Selection Logic

- lead_gen + B2B: LinkedIn, email, search
- brand_awareness: social, content, display
- retention: email, in-app, loyalty
- upsell: email, account manager outreach, webinar

## Output

Return JSON with: brief (markdown), key_messages (array of strings), suggested_channels (array of strings)
