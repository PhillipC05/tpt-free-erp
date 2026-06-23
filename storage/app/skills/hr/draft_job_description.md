---
name: Job Description Generator
slug: hr.draft_job_description
version: "1.0"
category: hr
description: Generate a professional job description from role title, department, and key requirements.
required_permissions:
  - hr.create
affected_modules:
  - hr
inputs:
  - name: job_title
    type: string
    description: The title of the role
    required: true
  - name: department
    type: string
    description: The department this role belongs to
    required: true
  - name: key_responsibilities
    type: array
    description: List of main responsibilities (bullet points)
    required: true
  - name: required_skills
    type: array
    description: List of required skills and qualifications
    required: false
  - name: employment_type
    type: string
    description: "full_time | part_time | contract | intern"
    required: false
  - name: company_name
    type: string
    description: Company name to personalise the JD
    required: false
outputs:
  - name: job_description
    type: string
    description: Full job description in markdown format
model_tier: standard
estimated_tokens: 1500
cost_tier: low
enabled_by_default: false
tags: [hr, recruitment, content]
---

## Task

You are an HR specialist. Write a professional, engaging job description for the role provided.

## Instructions

1. Start with a compelling company overview paragraph (1-2 sentences, use company_name if provided)
2. Write a clear "About the Role" section (2-3 sentences)
3. List "Key Responsibilities" as a bulleted list using the provided responsibilities
4. List "Requirements" covering the required skills; if not provided, infer reasonable requirements for the role
5. Add a "What We Offer" section with 4-5 typical benefits (flexible working, professional development, etc.)
6. Close with an "Apply Now" call to action

## Style Guide

- Professional but warm tone
- Inclusive language (avoid gendered terms)
- Active voice
- 400-600 words total

## Output

Return JSON with a single key: job_description (string in markdown format)
