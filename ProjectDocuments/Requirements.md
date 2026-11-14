# Business Requirements — ksf_FA_HRM_PayrollCalc

**Package:** `ksfraser/ksf-fa-hrm-payrollcalc`
**Version:** `1.0.0`
**Namespace:** `Ksfraser\FrontAccounting\HRM\PayrollCalc\*`
**Module sequence:** PC (Payroll Calc)

---

## BR-PC-001 — Payroll calculation engine

**Status:** IMPLEMENTED

The system SHALL provide `ksf_FA_HRM_PayrollCalc` as the owning module for
payroll calculation. Uses ref-code cross-referencing to pay rates and time
entries.

1. Payroll calculation pulls current pay rate (grade × percentage) from PayRates.
2. Payroll calculation pulls time entries for the period from TimeTracking.
3. Gross pay = sum of (hours × event_code_multiplier × base_rate) per entry.
4. Net pay = gross pay − deductions (deduction engine separate).

---

## BR-PC-002 — Resolved DTO for RelatedTo

**Status:** IMPLEMENTED

Payroll Calc SHALL build resolved display data for RelatedTo display.

1. Resolved data wraps a `RelatedRecordDto` with resolved fields,
   overridable labels, and a URL.
2. `fieldMap()` returns the field name => display label mapping.
3. `listAll()` returns resolved payroll data for summary table display.

---

## BR-PC-003 — PAYROLL_CALC hook

**Status:** IMPLEMENTED

The `PAYROLL_CALC` hook calculates payroll for an employee over a period.

1. Payload: `['employee_id', 'period_start', 'period_end']`
2. Returns: `['gross_pay', 'net_pay', 'breakdown' => [...], 'base_rate', 'percentage', 'grade_code']`
3. Breakdown entries include event_code, hours, multiplier, impacted_hours, amount, pay_type, work_date.

