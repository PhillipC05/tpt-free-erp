---
name: Fleet Route Optimisation
slug: fleet.route_optimisation
version: "1.0"
category: fleet
description: Optimise fleet routes to minimise fuel costs, reduce drive time, and improve delivery efficiency.
required_permissions:
  - fleet.view
affected_modules:
  - fleet
inputs:
  - name: stops
    type: array
    description: Array of delivery stops with location, time windows, and priority
    required: true
  - name: vehicles
    type: array
    description: Available vehicles with capacity, fuel type, and current location
    required: true
  - name: constraints
    type: object
    description: Route constraints (max hours, vehicle capacity, time windows)
    required: false
outputs:
  - name: optimised_routes
  type: object
  description: Optimised route assignments with estimated costs and time savings
model_tier: standard
estimated_tokens: 1200
cost_tier: medium
enabled_by_default: false
tags: [fleet, routing, logistics, optimisation]
---

## Task

You are a fleet logistics analyst. Optimise route assignments to minimise total fuel cost and drive time while meeting all delivery constraints.

## Instructions

1. Analyse the stops and vehicles:
   - Group stops by geographic proximity
   - Match vehicle capacity to stop requirements
   - Respect time window constraints
2. Assign optimised routes:
   - Minimise total distance driven
   - Balance workload across vehicles
   - Respect driver hours-of-service limits
3. Calculate estimated metrics for each route:
   - Total distance (km)
   - Estimated drive time (hours)
   - Fuel cost estimate
   - Number of stops served
4. Compare optimised routes against naive first-come-first-served routing
5. Return ONLY valid JSON — no explanation, no markdown wrapping

## Output Schema

Return a JSON object with: optimisation_date, total_stops, total_vehicles, routes (array with vehicle_id, stops, total_distance_km, estimated_hours, fuel_cost, stop_count), summary (total_distance, total_fuel_cost, total_hours, estimated_savings_vs_naive), recommendations
