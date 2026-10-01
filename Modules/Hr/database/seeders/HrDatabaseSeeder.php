<?php

declare(strict_types=1);

namespace Modules\Hr\Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentSequence;
use Illuminate\Database\Seeder;
use Modules\Hr\Models\Attendance;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeavePolicy;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\PayrollRun;
use Modules\Hr\Models\SalaryStructure;

class HrDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentSequence::query()->firstOrCreate(
            ['key' => 'employee'],
            ['prefix' => 'EMP', 'pattern' => '{prefix}-{year}-{number}', 'padding' => 6, 'reset_yearly' => true],
        );

        $department = Department::query()->firstOrCreate(
            ['code' => 'HR'],
            ['name' => 'Human Resources', 'status' => 'active'],
        );
        $designation = Designation::query()->firstOrCreate(
            ['code' => 'HR-EXEC'],
            ['name' => 'HR Executive', 'department_id' => $department->id, 'level' => 1, 'status' => 'active'],
        );
        $salaryStructure = SalaryStructure::query()->firstOrCreate(
            ['code' => 'HR-STD'],
            ['name' => 'HR Standard Salary', 'basic_salary' => '75000.0000', 'allowances' => '10000.0000', 'deductions' => '0.0000', 'is_active' => true],
        );
        $leavePolicy = LeavePolicy::query()->firstOrCreate(
            ['code' => 'HR-ANNUAL'],
            ['name' => 'Annual Leave', 'annual_entitlement_days' => '20.00', 'is_active' => true],
        );

        $names = [['Ayesha', 'Khan'], ['Bilal', 'Ahmed'], ['Hina', 'Malik'], ['Usman', 'Ali'], ['Sara', 'Iqbal'], ['Farhan', 'Raza'], ['Mariam', 'Noor'], ['Zain', 'Shah'], ['Komal', 'Aslam'], ['Hamza', 'Siddiq']];
        $employees = collect();
        foreach ($names as $index => [$firstName, $lastName]) {
            $number = sprintf('EMP-DEMO-%03d', $index + 1);
            $employee = Employee::query()->updateOrCreate(
                ['number' => $number],
                ['first_name' => $firstName, 'last_name' => $lastName, 'email' => 'demo.employee'.($index + 1).'@example.test', 'phone' => '030000000'.($index + 1), 'department_id' => $department->id, 'designation_id' => $designation->id, 'salary_structure_id' => $salaryStructure->id, 'leave_policy_id' => $leavePolicy->id, 'joining_date' => now()->subMonths($index + 1)->toDateString(), 'attendance_enabled' => true, 'status' => 'active'],
            );
            $employee->contracts()->updateOrCreate(['employee_id' => $employee->id, 'is_current' => true], ['type' => 'permanent', 'start_date' => $employee->joining_date, 'monthly_salary' => '75000.0000']);
            Attendance::query()->updateOrCreate(['employee_id' => $employee->id, 'attendance_date' => now()->toDateString()], ['status' => 'present', 'checked_in_at' => now()->setTime(9, 0), 'checked_out_at' => now()->setTime(17, 0)]);
            if ($index < 3) {
                LeaveRequest::query()->updateOrCreate(['employee_id' => $employee->id, 'start_date' => now()->addDays($index + 1)->toDateString()], ['end_date' => now()->addDays($index + 2)->toDateString(), 'days' => '2.00', 'reason' => 'Demo annual leave', 'status' => 'pending']);
            }
            $employees->push($employee);
        }

        $payrollRun = PayrollRun::query()->firstOrCreate(
            ['number' => 'PAY-DEMO-001'],
            ['period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString(), 'status' => 'draft'],
        );
        foreach ($employees as $employee) {
            $contract = $employee->contracts()->where('is_current', true)->firstOrFail();
            $payrollRun->lines()->updateOrCreate(
                ['employee_id' => $employee->id],
                ['basic_salary' => $contract->monthly_salary, 'allowances' => '10000.0000', 'deductions' => '2500.0000', 'net_pay' => bcadd(bcsub((string) $contract->monthly_salary, '2500.0000', 4), '10000.0000', 4)],
            );
        }
        $payrollRun->refresh()->load('lines');
        $payrollRun->update([
            'gross_total' => $payrollRun->lines->sum('basic_salary'),
            'deduction_total' => $payrollRun->lines->sum('deductions'),
            'net_total' => $payrollRun->lines->sum('net_pay'),
        ]);
        DocumentSequence::query()->firstOrCreate(
            ['key' => 'payroll'],
            ['prefix' => 'PAY', 'pattern' => '{prefix}-{year}-{number}', 'padding' => 6, 'reset_yearly' => true],
        );
    }
}
