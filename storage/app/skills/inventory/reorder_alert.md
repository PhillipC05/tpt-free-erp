---
name: Inventory Reorder Alert
slug: inventory.reorder_alert
version: "1.0"
category: inventory
description: Identify products below reorder point and generate a draft purchase order recommendation.
required_permissions:
  - inventory.view
  - procurement.create
affected_modules:
  - inventory
  - procurement
inputs:
  - name: products
    type: array
    description: Array of product objects with id, name, sku, stock_quantity, reorder_point, reorder_quantity, preferred_vendor_id, unit_cost
    required: true
  - name: lead_time_days
    type: integer
    description: Average lead time in days for orders
    required: false
outputs:
  - name: reorder_items
    type: array
    description: Products that need reordering
  - name: draft_po_lines
    type: array
    description: Suggested PO line items grouped by vendor
  - name: summary
    type: string
    description: Brief summary of the reorder situation
model_tier: fast
estimated_tokens: 800
cost_tier: low
enabled_by_default: false
tags: [inventory, procurement, automation]
---

## Task

You are an inventory analyst. Review current stock levels and generate reorder recommendations.

## Instructions

1. Filter products where stock_quantity <= reorder_point
2. For each under-stock product, calculate:
   - Quantity to order: reorder_quantity (or reorder_point * 2 if not set)
   - Estimated cost: quantity * unit_cost
3. Group recommendations by preferred_vendor_id
4. Note any critical stock situations (stock_quantity <= 0)
5. Write a brief summary of the situation

## Output

Return JSON with:
- reorder_items: array of {product_id, name, sku, current_stock, reorder_point, quantity_to_order, vendor_id, unit_cost, line_total}
- draft_po_lines: array of {vendor_id, items[], total_value}
- summary: string
