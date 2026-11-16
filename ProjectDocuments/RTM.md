# RTM — ksf_FA_HRM_PayrollCalc

| BR ID | Requirement | Implementing class/method | Test file | Status |
|-------|-------------|---------------------------|-----------|--------|
| BR-PC-001 | Payroll calculation engine | PayrollCalcService::calculatePayroll(), PayrollRepository::save() | PayrollDtoTest | Implemented |
| BR-PC-002 | Resolved DTOs for RelatedTo | PayrollCalcService::fieldMap(), labelMap(), listAll() | PayrollDtoTest | Implemented |
| BR-PC-003 | PAYROLL_CALC hook | PayrollCalcService::calculatePayroll(), hooks.php PAYROLL_CALC responder | PayrollDtoTest | Implemented |

