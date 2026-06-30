<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Categories
        $categoryIds = [];
        $categories = [
            ['name' => 'Electronics',     'description' => 'Electronic components and devices'],
            ['name' => 'Furniture',        'description' => 'Office and facility furniture'],
            ['name' => 'Office Supplies',  'description' => 'Consumable office and administrative supplies'],
            ['name' => 'Raw Materials',    'description' => 'Unprocessed materials for production'],
            ['name' => 'Finished Goods',   'description' => 'Completed products ready for sale'],
            ['name' => 'Consumables',      'description' => 'Items consumed in day-to-day operations'],
        ];

        foreach ($categories as $cat) {
            $id = DB::table('inventory_categories')->insertGetId([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => $cat['description'],
                'parent_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryIds[$cat['name']] = $id;
        }

        // Warehouses
        $warehouseIds = [];
        $warehouses = [
            ['code' => 'WH-MAIN', 'name' => 'Main Warehouse',      'address' => '100 Industrial Blvd', 'city' => 'Austin',  'country' => 'US'],
            ['code' => 'WH-SEC',  'name' => 'Secondary Warehouse',  'address' => '200 Storage Lane',    'city' => 'Dallas',  'country' => 'US'],
        ];

        foreach ($warehouses as $wh) {
            $id = DB::table('inventory_warehouses')->insertGetId([
                'code' => $wh['code'],
                'name' => $wh['name'],
                'address' => $wh['address'],
                'city' => $wh['city'],
                'country' => $wh['country'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $warehouseIds[$wh['code']] = $id;
        }

        // Products
        $products = [
            // Electronics
            ['sku' => 'ELEC-001', 'name' => 'Laptop – 15" Business',       'unit' => 'pcs',  'unit_price' => 1299.00, 'cost_price' => 950.00,  'cat' => 'Electronics',    'valuation_method' => 'fifo'],
            ['sku' => 'ELEC-002', 'name' => 'USB-C Docking Station',        'unit' => 'pcs',  'unit_price' => 149.00, 'cost_price' => 80.00,  'cat' => 'Electronics',    'valuation_method' => 'average'],
            ['sku' => 'ELEC-003', 'name' => 'Wireless Keyboard & Mouse',    'unit' => 'set',  'unit_price' => 79.99, 'cost_price' => 40.00,  'cat' => 'Electronics',    'valuation_method' => 'average'],
            ['sku' => 'ELEC-004', 'name' => '27" Monitor – 4K',             'unit' => 'pcs',  'unit_price' => 499.00, 'cost_price' => 320.00,  'cat' => 'Electronics',    'valuation_method' => 'fifo'],

            // Furniture
            ['sku' => 'FURN-001', 'name' => 'Executive Desk – Standing',    'unit' => 'pcs',  'unit_price' => 899.00, 'cost_price' => 450.00,  'cat' => 'Furniture',      'valuation_method' => 'average'],
            ['sku' => 'FURN-002', 'name' => 'Ergonomic Office Chair',       'unit' => 'pcs',  'unit_price' => 399.00, 'cost_price' => 200.00,  'cat' => 'Furniture',      'valuation_method' => 'average'],
            ['sku' => 'FURN-003', 'name' => 'Filing Cabinet – 4 Drawer',   'unit' => 'pcs',  'unit_price' => 249.00, 'cost_price' => 120.00,  'cat' => 'Furniture',      'valuation_method' => 'average'],

            // Office Supplies
            ['sku' => 'OFFC-001', 'name' => 'Printer Paper A4 – Box 500',  'unit' => 'box',  'unit_price' => 12.99, 'cost_price' => 7.50,  'cat' => 'Office Supplies', 'valuation_method' => 'average'],
            ['sku' => 'OFFC-002', 'name' => 'Ballpoint Pen – Box 50',      'unit' => 'box',  'unit_price' => 9.99, 'cost_price' => 4.00,  'cat' => 'Office Supplies', 'valuation_method' => 'average'],
            ['sku' => 'OFFC-003', 'name' => 'Sticky Notes – Pack of 12',   'unit' => 'pack', 'unit_price' => 6.49, 'cost_price' => 2.50,  'cat' => 'Office Supplies', 'valuation_method' => 'average'],

            // Raw Materials
            ['sku' => 'RAW-001',  'name' => 'Aluminium Sheet 1mm – 1m²',   'unit' => 'sheet', 'unit_price' => 24.50, 'cost_price' => 18.00,  'cat' => 'Raw Materials',   'valuation_method' => 'fifo'],
            ['sku' => 'RAW-002',  'name' => 'Stainless Steel Rod 10mm – 1m', 'unit' => 'm',   'unit_price' => 8.75, 'cost_price' => 5.50,  'cat' => 'Raw Materials',   'valuation_method' => 'fifo'],
            ['sku' => 'RAW-003',  'name' => 'PVC Granules – 25kg Bag',      'unit' => 'bag',  'unit_price' => 45.00, 'cost_price' => 30.00,  'cat' => 'Raw Materials',   'valuation_method' => 'fifo'],

            // Finished Goods
            ['sku' => 'FG-001',   'name' => 'Widget Pro – Model A',         'unit' => 'pcs',  'unit_price' => 199.00, 'cost_price' => 85.00,  'cat' => 'Finished Goods',  'valuation_method' => 'fifo'],
            ['sku' => 'FG-002',   'name' => 'Widget Pro – Model B',         'unit' => 'pcs',  'unit_price' => 299.00, 'cost_price' => 130.00,  'cat' => 'Finished Goods',  'valuation_method' => 'fifo'],
        ];

        foreach ($products as $prod) {
            DB::table('inventory_products')->insert([
                'sku' => $prod['sku'],
                'name' => $prod['name'],
                'unit' => $prod['unit'],
                'unit_price' => $prod['unit_price'],
                'cost_price' => $prod['cost_price'],
                'category_id' => $categoryIds[$prod['cat']] ?? null,
                'valuation_method' => $prod['valuation_method'],
                'min_stock_level' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
