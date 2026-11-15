<?php
declare(strict_types=1);
namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\Service;
use Ksfraser\FrontAccounting\HRM\PayrollCalc\DTO\PayrollDTO;
use Ksfraser\FrontAccounting\HRM\PayrollCalc\Repository\PayrollRepository;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
/**
 * @BABOK Related: BR-PC-001
 * @BABOK Related: BR-PC-002
 * @BABOK Related: BR-PC-003
 */
final class PayrollCalcService {
    private $repo;
    /**
     * @BABOK Related: BR-PC-001
     */
    public function __construct(?PayrollRepository $repo = null) { $this->repo = $repo ?? new PayrollRepository(); }
    /**
     * @BABOK Related: BR-PC-002
     */
    public function resolveById(int $id, ?RelatedRecordDto $dto = null): array {
        $row = $this->repo->findById($id);
        if ($row === null) {
            return array('dto' => new RelatedRecordDto('payroll', '0', 'payroll', '0', null), 'fields' => $this->fieldMap(), 'labels' => $this->labelMap(), 'url' => '#', 'label' => _('Payroll #') . (string) $id);
        }
        $p = PayrollDTO::fromArray($row);
        $dto = $dto ?? new RelatedRecordDto('payroll', (string) $p->id, 'payroll', (string) $p->id, null);
        return array('dto' => $dto, 'fields' => array('employeeId' => $p->employeeName, 'periodStart' => $p->periodStart, 'periodEnd' => $p->periodEnd, 'grossPay' => $p->grossPay, 'netPay' => $p->netPay, 'status' => $p->status), 'labels' => $this->labelMap(), 'url' => '/modules/ksf_FA_HRM_PayrollCalc/pages/payroll_form.php?action=edit&payroll_id=' . $p->id, 'label' => $p->getDisplayLabel());
    }
    /**
     * @BABOK Related: BR-PC-002
     */
    public function listAll(): array {
        $rows = $this->repo->findAll();
        $results = array();
        foreach ($rows as $row) {
            $p = PayrollDTO::fromArray($row);
            $results[] = array('id' => $p->id, 'dto' => new RelatedRecordDto('payroll', (string) $p->id, 'payroll', (string) $p->id, null), 'fields' => array('employeeId' => $p->employeeName, 'periodStart' => $p->periodStart, 'periodEnd' => $p->periodEnd, 'grossPay' => $p->grossPay, 'netPay' => $p->netPay, 'status' => $p->status), 'labels' => $this->labelMap(), 'url' => '/modules/ksf_FA_HRM_PayrollCalc/pages/payroll_form.php?action=edit&payroll_id=' . $p->id, 'label' => $p->getDisplayLabel());
        }
        return $results;
    }
    /**
     * Calculate payroll for an employee over a period.
     *
     * Pulls current pay rate (grade × percentage) from PayRates service via
     * RELATED_TO_FIELDS/RELATE pattern, pulls time entries from TimeTracking,
     * applies event code multipliers, returns gross/net breakdown.
     *
     * @param string $employeeId Employee ref code
     * @param string $periodStart Period start date
     * @param string $periodEnd Period end date
     * @return array Payroll calculation result
     */
    /**
     * @BABOK Related: BR-PC-001
     * @BABOK Related: BR-PC-003
     */
    public function calculatePayroll(string $employeeId, string $periodStart, string $periodEnd): array {
        // Pull current pay rate via RELATED_TO_FIELDS pattern
        $payRateData = array(
            'module' => 'ksf_FA_HRM_PayrollCalc',
            'type' => 'employee_pay_rate',
            'fields' => array(),
        );
        if (function_exists('hook_invoke_all')) {
            hook_invoke_all('ksf_FA_HRM_PayRates', 'RELATED_TO_FIELDS', $payRateData);
        }

        // Pull current pay rate for employee
        $rateRepo = new \Ksfraser\FrontAccounting\HRM\PayRates\Repository\PayRateRepository();
        $currentRate = $rateRepo->findCurrentByEmployee($employeeId);

        $baseRate = $currentRate !== null ? (float) ($currentRate['calculated_rate'] ?? 0) : 0.0;
        $percentage = $currentRate !== null ? (float) ($currentRate['percentage'] ?? 100) : 100.0;
        $gradeCode = $currentRate !== null ? (string) ($currentRate['grade_code'] ?? '') : '';

        // Pull time entries for the period
        $timeRepo = new \Ksfraser\FrontAccounting\HRM\TimeTracking\Repository\TimeEntryRepository();
        $timeEntries = $timeRepo->findByEmployee($employeeId, $periodStart, $periodEnd);

        // Pull event codes for multiplier lookup
        $eventRepo = new \Ksfraser\FrontAccounting\HRM\TimeTracking\Repository\EventCodeRepository();
        $eventCodes = array();
        foreach ($eventRepo->findAll() as $ec) {
            $eventCodes[$ec['event_code']] = array(
                'multiplier' => (float) ($ec['impact_multiplier'] ?? 1.00),
                'pay_type' => (string) ($ec['pay_type'] ?? 'hourly'),
            );
        }

        // Calculate gross pay by event code
        $grossPay = 0.0;
        $breakdown = array();
        foreach ($timeEntries as $te) {
            $ec = $eventCodes[$te['event_code']] ?? array('multiplier' => 1.00, 'pay_type' => 'hourly');
            $hours = (float) ($te['hours'] ?? 0);
            $impactedHours = $hours * $ec['multiplier'];
            $amount = $impactedHours * $baseRate;
            $grossPay += $amount;
            $breakdown[] = array(
                'event_code' => $te['event_code'],
                'hours' => $hours,
                'multiplier' => $ec['multiplier'],
                'impacted_hours' => $impactedHours,
                'amount' => $amount,
                'pay_type' => $ec['pay_type'],
                'work_date' => $te['work_date'],
            );
        }

        $netPay = $grossPay; // No deductions yet — deduction engine is separate

        return array(
            'employee_id' => $employeeId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'grade_code' => $gradeCode,
            'percentage' => $percentage,
            'base_rate' => $baseRate,
            'gross_pay' => $grossPay,
            'total_deductions' => 0.0,
            'net_pay' => $netPay,
            'breakdown' => $breakdown,
            'time_entry_count' => count($timeEntries),
        );
    }
    /**
     * @BABOK Related: BR-PC-002
     */
    public function fieldMap(): array { return $this->labelMap(); }
    /**
     * @BABOK Related: BR-PC-002
     */
    public function labelMap(): array {
        return array(
            'employeeId' => _('Employee'),
            'periodStart' => _('Period Start'),
            'periodEnd' => _('Period End'),
            'grossPay' => _('Gross Pay'),
            'netPay' => _('Net Pay'),
            'status' => _('Status'),
        );
    }
}
