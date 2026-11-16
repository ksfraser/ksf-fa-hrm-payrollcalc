<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\Repository;

final class PayrollRepository {
    private string $table = 'hrm_payroll_runs';

    public function findAll(): array
    {
        if (!function_exists('db_query') || !defined('TB_PREF')) { return array(); }
        $sql = "SELECT * FROM " . TB_PREF . $this->table . " ORDER BY pay_date DESC, id DESC";
        $result = db_query($sql, 'Could not load payroll runs');
        if ($result === false) { return array(); }
        $rows = array();
        while (($row = db_fetch_assoc($result)) !== false && $row !== null) { $rows[] = $row; }
        return $rows;
    }

    public function findById(int $id): ?array {
        if (!function_exists('db_query') || !defined('TB_PREF')) { return null; }
        $sql = "SELECT * FROM " . TB_PREF . $this->table . " WHERE id = " . (int) $id;
        $result = db_query($sql, 'Could not load payroll run');
        if ($result === false) { return null; }
        $row = db_fetch_assoc($result);
        return $row === false ? null : $row;
    }

    public function save(array $data): int
    {
        if (!function_exists('db_query') || !defined('TB_PREF')) { return 0; }
        $sql = "INSERT INTO " . TB_PREF . $this->table . " (
                employee_id, period_start, period_end, pay_date, gross_pay, total_deductions,
                net_pay, status, gl_posted)
            VALUES ("
            . $this->escape($data['employee_id'] ?? '') . ", "
            . $this->escape($data['period_start'] ?? '') . ", "
            . $this->escape($data['period_end'] ?? '') . ", "
            . $this->escape($data['pay_date'] ?? '') . ", "
            . $this->escape($data['gross_pay'] ?? '0.00') . ", "
            . $this->escape($data['total_deductions'] ?? '0.00') . ", "
            . $this->escape($data['net_pay'] ?? '0.00') . ", "
            . $this->escape($data['status'] ?? 'Draft') . ", "
            . (isset($data['gl_posted']) ? (int)$data['gl_posted'] : 0) . ")";
        db_query($sql, 'Could not save payroll run');
        return db_insert_id();
    }

    public function update(int $id, array $data): void {
        if (!function_exists('db_query') || !defined('TB_PREF')) { return; }
        $sets = [];
        foreach (['employee_id', 'period_start', 'period_end', 'pay_date', 'gross_pay', 'total_deductions', 'net_pay', 'status', 'gl_posted'] as $col) {
            if (array_key_exists($col, $data)) {
                $val = ($col === 'gl_posted') ? (int)$data[$col] : $this->escape($data[$col]);
                $sets[] = "$col = $val";
            }
        }
        if (empty($sets)) { return; }
        $sql = "UPDATE " . TB_PREF . $this->table . " SET " . implode(', ', $sets) . " WHERE id = " . (int) $id;
        db_query($sql, 'Could not update payroll run');
    }

    private function escape($value): string {
        return function_exists('db_escape') ? db_escape($value) : addslashes((string) $value);
    }
}
