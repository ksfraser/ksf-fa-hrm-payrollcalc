<?php
declare(strict_types=1);
namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\Tests\Unit;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../../ksf_FA_HRM_PayRates/src/Ksfraser/FrontAccounting/HRM/PayRates/Repository/PayRateRepository.php';
require_once __DIR__ . '/../../../ksf_FA_HRM_TimeTracking/src/Ksfraser/FrontAccounting/HRM/TimeTracking/Repository/TimeEntryRepository.php';
require_once __DIR__ . '/../../../ksf_FA_HRM_TimeTracking/src/Ksfraser/FrontAccounting/HRM/TimeTracking/Repository/EventCodeRepository.php';
require_once __DIR__ . '/../../hooks.php';
class PayrollCalcHooksTest extends TestCase {
    protected function setUp(): void { \DbStub::reset(); }
    private function hooks(): \hooks_ksf_FA_HRM_PayrollCalc {
        return new \hooks_ksf_FA_HRM_PayrollCalc();
    }
    public function testRespondersConstant(): void {
        $responders = \hooks_ksf_FA_HRM_PayrollCalc::RESPONDERS;
        $this->assertTrue(in_array('RELATED_TO_FIELDS', $responders, true));
        $this->assertTrue(in_array('PAYROLL_RESOLVE', $responders, true));
        $this->assertTrue(in_array('PAYROLL_FIELDS', $responders, true));
        $this->assertTrue(in_array('PAYROLL_CALC', $responders, true));
    }
    public function testRelatedToFieldsPayrollType(): void {
        $data = ['module' => 'ksf_FA_HRM_PayrollCalc', 'type' => 'payroll', 'fields' => []];
        $this->hooks()->RELATED_TO_FIELDS($data);
        $this->assertSame('Gross Pay', $data['fields']['grossPay']);
        $this->assertSame('Net Pay', $data['fields']['netPay']);
    }
    public function testRelatedToFieldsNonPayrollTypeNoOp(): void {
        $data = ['module' => 'x', 'type' => 'time_entry', 'fields' => ['a' => 1]];
        $this->hooks()->RELATED_TO_FIELDS($data);
        $this->assertSame(['a' => 1], $data['fields']);
    }
    public function testRelatedToFieldsMissingModuleError(): void {
        $data = ['type' => 'payroll'];
        $this->hooks()->RELATED_TO_FIELDS($data);
        $this->assertArrayHasKey('error', $data);
    }
    public function testPayrollResolve(): void {
        \DbStub::setRows([['id' => 7, 'employee_id' => 'E1', 'employee_name' => 'Dana', 'period_start' => '2026-01-01', 'period_end' => '2026-01-15', 'pay_date' => '2026-01-20', 'gross_pay' => '500.00', 'total_deductions' => '50.00', 'net_pay' => '450.00', 'status' => 'Draft', 'gl_posted' => '0']]);
        $dto = new RelatedRecordDto('payroll', '0', 'payroll', '0', null);
        $data = ['payroll_id' => 7, 'dto' => $dto];
        $this->hooks()->PAYROLL_RESOLVE($data);
        $this->assertArrayHasKey('result', $data);
        $this->assertSame('Dana', $data['result']['fields']['employeeId']);
        $this->assertSame('500.00', $data['result']['fields']['grossPay']);
        $this->assertSame('Dana — 2026-01-01 to 2026-01-15', $data['result']['label']);
        $this->assertSame($dto, $data['result']['dto']);
    }
    public function testPayrollResolveMissingPayrollIdError(): void {
        $data = ['dto' => new RelatedRecordDto('payroll', '0', 'payroll', '0', null)];
        $this->hooks()->PAYROLL_RESOLVE($data);
        $this->assertArrayHasKey('error', $data);
    }
    public function testPayrollResolveMissingDtoError(): void {
        $data = ['payroll_id' => 7];
        $this->hooks()->PAYROLL_RESOLVE($data);
        $this->assertArrayHasKey('error', $data);
    }
    public function testPayrollResolveNonNumericError(): void {
        $data = ['payroll_id' => 'abc', 'dto' => new RelatedRecordDto('payroll', '0', 'payroll', '0', null)];
        $this->hooks()->PAYROLL_RESOLVE($data);
        $this->assertArrayHasKey('error', $data);
    }
    public function testPayrollFields(): void {
        $data = ['fields' => []];
        $this->hooks()->PAYROLL_FIELDS($data);
        $this->assertSame('Gross Pay', $data['fields']['grossPay']);
        $this->assertSame('Net Pay', $data['fields']['netPay']);
    }
    public function testPayrollCalcDelegatesToService(): void {
        \DbStub::setQueryResultSets([
            [['id' => 1, 'employee_id' => 'E1', 'grade_code' => 'G1', 'percentage' => '100',
                'effective_date' => '2026-01-01', 'end_date' => '', 'calculated_rate' => '25.00', 'is_current' => '1']],
            [
                ['id' => 1, 'employee_id' => 'E1', 'event_code' => 'REG', 'hours' => '8', 'work_date' => '2026-01-02', 'pay_impact' => '', 'calculated_amount' => '0'],
                ['id' => 2, 'employee_id' => 'E1', 'event_code' => 'OT', 'hours' => '2', 'work_date' => '2026-01-03', 'pay_impact' => '', 'calculated_amount' => '0'],
            ],
            [
                ['id' => 1, 'event_code' => 'REG', 'event_name' => 'Regular', 'impact_multiplier' => '1.00', 'pay_type' => 'hourly', 'description' => '', 'is_active' => '1'],
                ['id' => 2, 'event_code' => 'OT', 'event_name' => 'Overtime', 'impact_multiplier' => '1.50', 'pay_type' => 'hourly', 'description' => '', 'is_active' => '1'],
            ],
        ]);
        $data = ['employee_id' => 'E1', 'period_start' => '2026-01-01', 'period_end' => '2026-01-15'];
        $this->hooks()->PAYROLL_CALC($data);
        $this->assertArrayHasKey('result', $data);
        $this->assertSame(275.0, $data['result']['gross_pay']);
        $this->assertSame(275.0, $data['result']['net_pay']);
        $this->assertSame(2, $data['result']['time_entry_count']);
        // RELATED_TO_FIELDS to PayRates is invoked as part of the calc path
        $this->assertCount(1, \DbStub::$hookCalls);
        $this->assertSame('ksf_FA_HRM_PayRates', \DbStub::$hookCalls[0][0]);
        $this->assertSame('RELATED_TO_FIELDS', \DbStub::$hookCalls[0][1]);
    }
    public function testPayrollCalcMissingEmployeeIdError(): void {
        $data = ['period_start' => '2026-01-01', 'period_end' => '2026-01-15'];
        $this->hooks()->PAYROLL_CALC($data);
        $this->assertArrayHasKey('error', $data);
    }
    public function testPayrollCalcMissingPeriodsError(): void {
        $data = ['employee_id' => 'E1'];
        $this->hooks()->PAYROLL_CALC($data);
        $this->assertArrayHasKey('error', $data);
    }
}
