<?php
declare(strict_types=1);
namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\Tests\Unit;
use Ksfraser\FrontAccounting\HRM\PayrollCalc\Repository\PayrollRepository;
use Ksfraser\FrontAccounting\HRM\PayrollCalc\Service\PayrollCalcService;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../../ksf_FA_HRM_PayRates/src/Ksfraser/FrontAccounting/HRM/PayRates/Repository/PayRateRepository.php';
require_once __DIR__ . '/../../../ksf_FA_HRM_TimeTracking/src/Ksfraser/FrontAccounting/HRM/TimeTracking/Repository/TimeEntryRepository.php';
require_once __DIR__ . '/../../../ksf_FA_HRM_TimeTracking/src/Ksfraser/FrontAccounting/HRM/TimeTracking/Repository/EventCodeRepository.php';
class PayrollCalcServiceTest extends TestCase {
    protected function setUp(): void { \DbStub::reset(); }
    private function payrollRow($id = 4, $name = 'Alice') {
        return ['id' => $id, 'employee_id' => 'E1', 'employee_name' => $name, 'period_start' => '2026-01-01',
            'period_end' => '2026-01-15', 'pay_date' => '2026-01-20', 'gross_pay' => '1000.00',
            'total_deductions' => '100.00', 'net_pay' => '900.00', 'status' => 'Posted', 'gl_posted' => '1'];
    }
    public function testResolveByIdFound(): void {
        \DbStub::setRows([$this->payrollRow(4, 'Alice')]);
        $service = new PayrollCalcService();
        $result = $service->resolveById(4);
        $this->assertSame('Alice', $result['fields']['employeeId']);
        $this->assertSame('2026-01-01', $result['fields']['periodStart']);
        $this->assertSame('2026-01-15', $result['fields']['periodEnd']);
        $this->assertSame('1000.00', $result['fields']['grossPay']);
        $this->assertSame('900.00', $result['fields']['netPay']);
        $this->assertSame('Posted', $result['fields']['status']);
        $this->assertSame('Alice — 2026-01-01 to 2026-01-15', $result['label']);
        $this->assertSame('/modules/ksf_FA_HRM_PayrollCalc/pages/payroll_form.php?action=edit&payroll_id=4', $result['url']);
        $this->assertInstanceOf(RelatedRecordDto::class, $result['dto']);
        $this->assertSame('4', $result['dto']->rhsId);
    }
    public function testResolveByIdNotFound(): void {
        \DbStub::setRows([]);
        $service = new PayrollCalcService();
        $result = $service->resolveById(99);
        $this->assertSame('Payroll #99', $result['label']);
        $this->assertSame('#', $result['url']);
        $this->assertInstanceOf(RelatedRecordDto::class, $result['dto']);
    }
    public function testListAll(): void {
        \DbStub::setRows([$this->payrollRow(1, 'Alice'), $this->payrollRow(2, 'Bob')]);
        $service = new PayrollCalcService();
        $results = $service->listAll();
        $this->assertCount(2, $results);
        $this->assertSame(1, $results[0]['id']);
        $this->assertSame('Alice', $results[0]['fields']['employeeId']);
        $this->assertSame(2, $results[1]['id']);
        $this->assertSame('Bob', $results[1]['fields']['employeeId']);
    }
    public function testListAllEmpty(): void {
        \DbStub::setRows([]);
        $service = new PayrollCalcService();
        $this->assertSame([], $service->listAll());
    }
    public function testCalculatePayrollComputesGrossAndNet(): void {
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
        $service = new PayrollCalcService();
        $result = $service->calculatePayroll('E1', '2026-01-01', '2026-01-15');
        $this->assertSame('E1', $result['employee_id']);
        $this->assertSame('2026-01-01', $result['period_start']);
        $this->assertSame('2026-01-15', $result['period_end']);
        $this->assertSame('G1', $result['grade_code']);
        $this->assertSame(100.0, $result['percentage']);
        $this->assertSame(25.0, $result['base_rate']);
        $this->assertSame(275.0, $result['gross_pay']);
        $this->assertSame(0.0, $result['total_deductions']);
        $this->assertSame(275.0, $result['net_pay']);
        $this->assertSame(2, $result['time_entry_count']);
        $this->assertCount(2, $result['breakdown']);
        $this->assertSame('REG', $result['breakdown'][0]['event_code']);
        $this->assertSame(8.0, $result['breakdown'][0]['hours']);
        $this->assertSame(1.00, $result['breakdown'][0]['multiplier']);
        $this->assertSame(200.0, $result['breakdown'][0]['amount']);
        $this->assertSame('OT', $result['breakdown'][1]['event_code']);
        $this->assertSame(2.0, $result['breakdown'][1]['hours']);
        $this->assertSame(1.50, $result['breakdown'][1]['multiplier']);
        $this->assertSame(75.0, $result['breakdown'][1]['amount']);
    }
    public function testCalculatePayrollNoRateReturnsZero(): void {
        \DbStub::setQueryResultSets([[], [], []]);
        $service = new PayrollCalcService();
        $result = $service->calculatePayroll('E1', '2026-01-01', '2026-01-15');
        $this->assertSame(0.0, $result['base_rate']);
        $this->assertSame(100.0, $result['percentage']);
        $this->assertSame('', $result['grade_code']);
        $this->assertSame(0.0, $result['gross_pay']);
        $this->assertSame(0.0, $result['net_pay']);
        $this->assertSame(0, $result['time_entry_count']);
    }
    public function testFieldMapEqualsLabelMap(): void {
        $service = new PayrollCalcService();
        $this->assertSame($service->labelMap(), $service->fieldMap());
    }
    public function testLabelMapKeysAndValues(): void {
        $service = new PayrollCalcService();
        $this->assertSame([
            'employeeId' => 'Employee',
            'periodStart' => 'Period Start',
            'periodEnd' => 'Period End',
            'grossPay' => 'Gross Pay',
            'netPay' => 'Net Pay',
            'status' => 'Status',
        ], $service->labelMap());
    }
}
