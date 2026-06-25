---
name: Test Skill
slug: test.test_skill
version: "1.0"
category: test
description: A test skill for unit testing.
required_permissions:
  - finance.view
affected_modules:
  - finance
model_tier: fast
estimated_tokens: 500
cost_tier: low
enabled_by_default: false
tags:
  - test
---

## Task

Return JSON: {"result": "ok"}
