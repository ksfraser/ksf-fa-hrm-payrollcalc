-- ksf_FA_HRM_PayrollCalc: payroll runs

CREATE TABLE IF NOT EXISTS `0_hrm_payroll_runs` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` VARCHAR(50)  NOT NULL DEFAULT '',
  `period_start` DATE        NOT NULL,
  `period_end`   DATE        NOT NULL,
  `pay_date`     DATE        NOT NULL,
  `gross_pay`    DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total_deductions` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `net_pay`      DECIMAL(15,2) NOT NULL DEFAULT 0,
  `status`       VARCHAR(20) NOT NULL DEFAULT 'Draft',
  `gl_posted`    TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_period` (`period_start`, `period_end`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `0_hrm_payroll_entries` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `payroll_id`   INT(11) NOT NULL,
  `event_code`   VARCHAR(20) NOT NULL DEFAULT '',
  `hours`        DECIMAL(6,2) NOT NULL DEFAULT 0,
  `multiplier`   DECIMAL(5,4) NOT NULL DEFAULT 1.0000,
  `amount`       DECIMAL(15,2) NOT NULL DEFAULT 0,
  `work_date`    DATE,
  PRIMARY KEY (`id`),
  KEY `idx_payroll` (`payroll_id`),
  KEY `idx_event_code` (`event_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
