---
name: Employee Onboarding Checklist
slug: hr.onboard_employee
version: "1.0"
category: hr
description: Generate a structured onboarding task checklist and welcome message for a new employee.
required_permissions:
  - hr.create
  - hr.edit
affected_modules:
  - hr
inputs:
  - name: employee
    type: object
    description: Employee object with name, job_title, department, start_date, employment_type, manager_name
    required: true
  - name: company_name
    type: string
    description: Company name
    required: false
  - name: systems_to_provision
    type: array
    description: List of systems/tools to set up (e.g. email, Slack, ERP, GitHub)
    required: false
outputs:
  - name: checklist
    type: array
    description: Structured onboarding tasks with owner and due day
  - name: welcome_message
    type: string
    description: Welcome message to send to the new employee
model_tier: standard
estimated_tokens: 1000
cost_tier: low
enabled_by_default: false
tags: [hr, onboarding, automation]
---

## Task

You are an HR coordinator. Create an onboarding checklist and welcome message for a new employee.

## Checklist Structure

Generate tasks across these phases:

**Before start (Day -3 to 0):**
- IT: set up email, laptop, system access
- HR: prepare contract, bank details form, induction schedule
- Manager: schedule welcome meeting, assign buddy

**Week 1 (Days 1-5):**
- Complete company induction
- Meet with manager and team
- System access verification
- Review role responsibilities document
- Complete compliance/policy reading

**Week 2-4:**
- Role-specific training
- Attend team meetings
- First 1:1 with manager (Day 7, Day 14)
- 30-day check-in scheduled

For each task: owner (HR/IT/Manager/Employee), due_day (relative to start), description.

## Welcome Message

Professional, warm, and encouraging. Include: start date, who to contact on Day 1, and one sentence about the company culture.

## Output

Return JSON with: checklist (array of {task, owner, due_day, description}), welcome_message (string)
