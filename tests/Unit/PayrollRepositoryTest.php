<?php
declare(strict_types=1);
namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\Tests\Unit;
use Ksfraser\FrontAccounting\HRM\PayrollCalc\Repository\PayrollRepository;
use PHPUnit\Framework\TestCase;
class PayrollRepositoryTest extends TestCase {
    protected function setUp(): void { \DbStub::reset(); }
    public function testFindAllReturnsRows(): void {
        \DbStub::setRows([[
            'id' => 1, 'employee_id' => 'E1', 'period_start' => '2026-01-01', 'period_end' => '2026-01-15',
            'pay_date' => '2026-01-20', 'gross_pay' => '1000.00', 'total_deductions' => '100.00',
            'net_pay' => '900.00', 'status' => 'Posted', 'gl_posted' => '1',
        ]]);
        $repo = new PayrollRepository();
        $rows = $repo->findAll();
        $this->assertCount(1, $rows);
        $this->assertSame('E1', $rows[0]['employee_id']);
        $this->assertStringContainsString('hrm_payroll_runs', \DbStub::$lastSql);
    }
    public function testFindAllDbFailureReturnsEmpty(): void {
        \DbStub::$queryResult = false;
        $repo = new PayrollRepository();
        $this->assertSame([], $repo->findAll());
    }
    public function testFindByIdReturnsRow(): void {
        \DbStub::setRows([['id' => 6, 'employee_id' => 'E1', 'period_start' => '2026-01-01', 'period_end' => '2026-01-15', 'pay_date' => '2026-01-20', 'gross_pay' => '1000.00', 'total_deductions' => '100.00', 'net_pay' => '900.00', 'status' => 'Posted', 'gl_posted' => '1']]);
        $repo = new PayrollRepository();
        $row = $repo->findById(6);
        $this->assertSame(6, $row['id']);
        $this->assertSame('E1', $row['employee_id']);
        $this->assertStringContainsString('WHERE id = 6', \DbStub::$lastSql);
    }
    public function testFindByIdNotFoundReturnsNull(): void {
        \DbStub::setRows([]);
        $repo = new PayrollRepository();
        $this->assertNull($repo->findById(99));
    }
    public function testSaveReturnsInsertId(): void {
        \DbStub::$insertId = 88;
        $repo = new PayrollRepository();
        $id = $repo->save([
            'employee_id' => 'E1', 'period_start' => '2026-01-01', 'period_end' => '2026-01-15',
            'pay_date' => '2026-01-20', 'gross_pay' => '1000.00', 'total_deductions' => '100.00',
            'net_pay' => '900.00', 'status' => 'Draft', 'gl_posted' => 0,
        ]);
        $this->assertSame(88, $id);
        $this->assertStringContainsString('INSERT INTO', \DbStub::$lastSql);
        $this->assertStringContainsString('hrm_payroll_runs', \DbStub::$lastSql);
        $this->assertTrue(in_array('E1', \DbStub::$escaped, true));
    }
    public function testUpdateCallsDbQuery(): void {
        $repo = new PayrollRepository();
        $repo->update(6, ['status' => 'Posted', 'gl_posted' => 1]);
        $this->assertStringContainsString('UPDATE', \DbStub::$lastSql);
        $this->assertStringContainsString('hrm_payroll_runs', \DbStub::$lastSql);
        $this->assertStringContainsString('WHERE id = 6', \DbStub::$lastSql);
    }
    public function testUpdateNoSetsDoesNothing(): void {
        $repo = new PayrollRepository();
        $repo->update(6, []);
        $this->assertNull(\DbStub::$lastSql);
    }
}
