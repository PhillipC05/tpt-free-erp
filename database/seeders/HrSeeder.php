<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HrSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Departments (manager_id set to null initially; update after employees are created if needed)
        $deptIds = [];
        $departments = [
            ['code' => 'DEPT-ENG',  'name' => 'Engineering',    'description' => 'Product development and software engineering'],
            ['code' => 'DEPT-SALES', 'name' => 'Sales',           'description' => 'Sales, business development, and CRM'],
            ['code' => 'DEPT-HR',   'name' => 'Human Resources', 'description' => 'People operations and talent management'],
            ['code' => 'DEPT-FIN',  'name' => 'Finance',         'description' => 'Accounting, bookkeeping, and financial planning'],
            ['code' => 'DEPT-OPS',  'name' => 'Operations',      'description' => 'Warehouse, logistics, and facilities management'],
        ];

        foreach ($departments as $dept) {
            $id = DB::table('hr_departments')->insertGetId([
                'code' => $dept['code'],
                'name' => $dept['name'],
                'description' => $dept['description'],
                'manager_id' => null,
                'parent_id' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $deptIds[$dept['code']] = $id;
        }

        // Employees
        $employees = [
            [
                'employee_code' => 'EMP-001',
                'first_name' => 'Sarah',
                'last_name' => 'Mitchell',
                'email' => 'sarah.mitchell@company.example',
                'position' => 'VP of Engineering',
                'department_code' => 'DEPT-ENG',
                'employment_type' => 'full_time',
                'hire_date' => '2021-03-15',
                'salary' => 145000.00,
            ],
            [
                'employee_code' => 'EMP-002',
                'first_name' => 'James',
                'last_name' => 'Thornton',
                'email' => 'james.thornton@company.example',
                'position' => 'Senior Software Engineer',
                'department_code' => 'DEPT-ENG',
                'employment_type' => 'full_time',
                'hire_date' => '2022-06-01',
                'salary' => 115000.00,
            ],
            [
                'employee_code' => 'EMP-003',
                'first_name' => 'Maria',
                'last_name' => 'Sanchez',
                'email' => 'maria.sanchez@company.example',
                'position' => 'Sales Manager',
                'department_code' => 'DEPT-SALES',
                'employment_type' => 'full_time',
                'hire_date' => '2020-09-10',
                'salary' => 95000.00,
            ],
            [
                'employee_code' => 'EMP-004',
                'first_name' => 'David',
                'last_name' => 'Chen',
                'email' => 'david.chen@company.example',
                'position' => 'Account Executive',
                'department_code' => 'DEPT-SALES',
                'employment_type' => 'full_time',
                'hire_date' => '2023-01-16',
                'salary' => 72000.00,
            ],
            [
                'employee_code' => 'EMP-005',
                'first_name' => 'Lisa',
                'last_name' => 'Park',
                'email' => 'lisa.park@company.example',
                'position' => 'HR Business Partner',
                'department_code' => 'DEPT-HR',
                'employment_type' => 'full_time',
                'hire_date' => '2021-11-08',
                'salary' => 80000.00,
            ],
            [
                'employee_code' => 'EMP-006',
                'first_name' => 'Robert',
                'last_name' => 'Nguyen',
                'email' => 'robert.nguyen@company.example',
                'position' => 'Financial Controller',
                'department_code' => 'DEPT-FIN',
                'employment_type' => 'full_time',
                'hire_date' => '2019-07-22',
                'salary' => 110000.00,
            ],
            [
                'employee_code' => 'EMP-007',
                'first_name' => 'Emma',
                'last_name' => 'Williams',
                'email' => 'emma.williams@company.example',
                'position' => 'Accountant',
                'department_code' => 'DEPT-FIN',
                'employment_type' => 'full_time',
                'hire_date' => '2022-04-11',
                'salary' => 68000.00,
            ],
            [
                'employee_code' => 'EMP-008',
                'first_name' => 'Carlos',
                'last_name' => 'Rivera',
                'email' => 'carlos.rivera@company.example',
                'position' => 'Warehouse Supervisor',
                'department_code' => 'DEPT-OPS',
                'employment_type' => 'full_time',
                'hire_date' => '2020-02-03',
                'salary' => 62000.00,
            ],
        ];

        foreach ($employees as $emp) {
            DB::table('hr_employees')->insert([
                'employee_code' => $emp['employee_code'],
                'user_id' => null,
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'email' => $emp['email'],
                'position' => $emp['position'],
                'department_id' => $deptIds[$emp['department_code']] ?? null,
                'manager_id' => null,
                'hire_date' => $emp['hire_date'],
                'employment_type' => $emp['employment_type'],
                'status' => 'active',
                'salary' => $emp['salary'],
                'currency' => 'USD',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
