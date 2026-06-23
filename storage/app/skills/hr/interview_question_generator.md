---
name: Interview Question Generator
slug: hr.interview_question_generator
version: "1.0"
category: hr
description: Generate role-specific, competency-based interview questions from a job description.
required_permissions:
  - hr.view
affected_modules:
  - hr
inputs:
  - name: job_title
    type: string
    description: The role being hired for
    required: true
  - name: key_competencies
    type: array
    description: Core competencies required (e.g. leadership, analytical, communication)
    required: true
  - name: experience_level
    type: string
    description: "junior | mid | senior | executive"
    required: false
  - name: technical_skills
    type: array
    description: Specific technical skills to assess
    required: false
outputs:
  - name: questions
    type: array
    description: Interview questions grouped by category
  - name: scoring_guide
    type: string
    description: Brief guide on evaluating answers
model_tier: fast
estimated_tokens: 1000
cost_tier: low
enabled_by_default: false
tags: [hr, recruitment, interviews]
---

## Task

You are an HR specialist. Generate a structured interview question set for this role.

## Question Categories

Generate 3-5 questions per category:

1. **Behavioural** (STAR format): "Tell me about a time when..."
2. **Situational**: "What would you do if..."
3. **Technical**: Role-specific knowledge checks (use technical_skills if provided)
4. **Culture/Values**: Alignment with team and company values
5. **Career/Motivation**: Why this role, career goals

## Calibration by Level

- Junior: Focus on potential, learning mindset, foundational skills
- Mid: Focus on independent delivery, past outcomes
- Senior: Focus on leadership, strategic thinking, complexity handled
- Executive: Focus on vision, stakeholder management, organisational impact

## Output

Return JSON with: questions (array of {category, question, what_good_looks_like}), scoring_guide (string)
