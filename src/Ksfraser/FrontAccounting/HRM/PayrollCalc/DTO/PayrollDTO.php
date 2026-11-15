<?php
declare(strict_types=1);
namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\DTO;
/**
 * @BABOK Related: BR-PC-001
 * @BABOK Related: BR-PC-002
 */
final class PayrollDTO {
    public $id;
    public $employeeId;
    public $employeeName;
    public $periodStart;
    public $periodEnd;
    public $payDate;
    public $grossPay;
    public $totalDeductions;
    public $netPay;
    public $status;
    public $glPosted;
    /**
     * @BABOK Related: BR-PC-001
     */
    public function __construct(int $id, string $employeeId = '', string $employeeName = '', string $periodStart = '', string $periodEnd = '', string $payDate = '', string $grossPay = '0', string $totalDeductions = '0', string $netPay = '0', string $status = 'Draft', bool $glPosted = false) {
        $this->id = $id; $this->employeeId = $employeeId; $this->employeeName = $employeeName;
        $this->periodStart = $periodStart; $this->periodEnd = $periodEnd; $this->payDate = $payDate;
        $this->grossPay = $grossPay; $this->totalDeductions = $totalDeductions; $this->netPay = $netPay;
        $this->status = $status; $this->glPosted = $glPosted;
    }
    /**
     * @BABOK Related: BR-PC-002
     */
    public function getDisplayLabel(): string {
        $parts = array();
        if ($this->employeeName !== '') { $parts[] = $this->employeeName; }
        if ($this->periodStart !== '') { $parts[] = $this->periodStart . ' to ' . $this->periodEnd; }
        return $parts === [] ? _('Payroll #') . (string) $this->id : implode(' — ', $parts);
    }
    /**
     * @BABOK Related: BR-PC-001
     */
    public function toArray(): array {
        return array('id' => $this->id, 'employee_id' => $this->employeeId, 'employee_name' => $this->employeeName, 'period_start' => $this->periodStart, 'period_end' => $this->periodEnd, 'pay_date' => $this->payDate, 'gross_pay' => $this->grossPay, 'total_deductions' => $this->totalDeductions, 'net_pay' => $this->netPay, 'status' => $this->status, 'gl_posted' => $this->glPosted);
    }
    /**
     * @BABOK Related: BR-PC-001
     */
    public static function fromArray(array $data): self {
        return new self((int) ($data['id'] ?? 0), (string) ($data['employee_id'] ?? ''), (string) ($data['employee_name'] ?? ''), (string) ($data['period_start'] ?? ''), (string) ($data['period_end'] ?? ''), (string) ($data['pay_date'] ?? ''), (string) ($data['gross_pay'] ?? '0'), (string) ($data['total_deductions'] ?? '0'), (string) ($data['net_pay'] ?? '0'), (string) ($data['status'] ?? 'Draft'), !empty($data['gl_posted']));
    }
}
