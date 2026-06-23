---
name: Sales Follow-Up Email Drafter
slug: sales.draft_followup_email
version: "1.0"
category: sales
description: Generate a personalised follow-up email from CRM contact history and last interaction notes.
required_permissions:
  - sales.view
  - sales.create
affected_modules:
  - sales
inputs:
  - name: contact
    type: object
    description: Contact with name, company, email, role
    required: true
  - name: last_interaction
    type: object
    description: Object with date, type (call/meeting/email), notes
    required: true
  - name: opportunity
    type: object
    description: Optional — product/service discussed, value, stage
    required: false
  - name: sender_name
    type: string
    description: Name of the sales rep sending the email
    required: false
outputs:
  - name: subject
    type: string
    description: Email subject line
  - name: body
    type: string
    description: Email body in plain text
  - name: suggested_cta
    type: string
    description: Suggested call to action
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [sales, email, crm, automation]
---

## Task

You are a sales assistant. Write a warm, professional follow-up email based on the last interaction with this contact.

## Instructions

1. Open with a personalised reference to the last interaction (call/meeting/email)
2. Briefly recap the value discussed without being pushy
3. Propose a clear next step (demo, call, trial, proposal)
4. Keep the tone warm and professional — not templated
5. End with a light, open-ended question to invite a response
6. Total length: 100-150 words

## Output

Return JSON with: subject (string), body (string in plain text with line breaks), suggested_cta (string)
