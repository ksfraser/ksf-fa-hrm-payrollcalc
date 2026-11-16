<?php
declare(strict_types=1);
namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\Tests\Unit;
use Ksfraser\FrontAccounting\HRM\PayrollCalc\DTO\PayrollDTO;
use PHPUnit\Framework\TestCase;
class PayrollDtoTest extends TestCase {
    public function testGetDisplayLabel(): void {
        $dto = new PayrollDTO(7, 'E123', 'Alice Smith', '2026-10-01', '2026-10-15', '2026-10-20', '3000', '0', '3000', 'Draft', false);
        $this->assertSame('Alice Smith — 2026-10-01 to 2026-10-15', $dto->getDisplayLabel());
    }
    public function testGetDisplayLabelMinimal(): void {
        $dto = new PayrollDTO(3, '', '', '', '', '', '', '', '', '', false);
        $this->assertSame('Payroll #3', $dto->getDisplayLabel());
    }
    public function testToArrayShape(): void {
        $dto = new PayrollDTO(7, 'E123', 'Alice Smith', '2026-10-01', '2026-10-15', '2026-10-20', '3000', '0', '3000', 'Draft', false);
        $array = $dto->toArray();
        $this->assertSame(7, $array['id']);
        $this->assertSame('E123', $array['employee_id']);
        $this->assertSame('3000', $array['gross_pay']);
    }
    public function testFromArrayReconstructs(): void {
        $dto = PayrollDTO::fromArray(array(
            'id' => 9, 'employee_id' => 'E456', 'employee_name' => 'Bob Jones',
            'period_start' => '2026-11-01', 'period_end' => '2026-11-15', 'pay_date' => '2026-11-20',
            'gross_pay' => '2500', 'total_deductions' => '200', 'net_pay' => '2300', 'status' => 'Paid', 'gl_posted' => true,
        ));
        $this->assertSame(9, $dto->id);
        $this->assertSame('E456', $dto->employeeId);
        $this->assertSame('Bob Jones', $dto->employeeName);
        $this->assertSame('Paid', $dto->status);
        $this->assertTrue($dto->glPosted);
    }
}
