<?php
declare(strict_types=1);
require_once __DIR__ . '/ComposerDependencies.php';
\Ksfraser\FrontAccounting\HRM\PayrollCalc\Utils\ComposerDependencies::ensure(__DIR__);
$autoload_path = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($autoload_path)) { require_once $autoload_path; }
require_once __DIR__ . '/includes/common.inc';
require_once __DIR__ . '/includes/events.inc';
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
define('SS_ksf_FA_HRM_PayrollCalc', 170 << 8);
if (!defined('SA_PAYROLL_VIEW')) {
    define('SA_PAYROLL_VIEW', SS_ksf_FA_HRM_PayrollCalc | 1);
    define('SA_PAYROLL_MANAGE', SS_ksf_FA_HRM_PayrollCalc | 2);
}
class hooks_ksf_FA_HRM_PayrollCalc extends hooks {
    const RESPONDERS = array('RELATED_TO_FIELDS', 'PAYROLL_RESOLVE', 'PAYROLL_FIELDS', 'PAYROLL_CALC');
    var $module_name = 'ksf_FA_HRM_PayrollCalc';
    var $version = '1.0.0';
    function install_tabs($app) { }
    function install_options($app) { }
    function install_access() {
        $security_sections[SS_ksf_FA_HRM_PayrollCalc] = _("Payroll Calculation");
        $security_areas['SA_PAYROLL_VIEW'] = array(SA_PAYROLL_VIEW, _("View payroll"));
        $security_areas['SA_PAYROLL_MANAGE'] = array(SA_PAYROLL_MANAGE, _("Manage payroll"));
        return array($security_areas, $security_sections);
    }
    function activate_extension($company, $check_only = true) {
        if ($check_only) { return true; }
        \Ksfraser\FrontAccounting\HRM\PayrollCalc\Utils\ComposerDependencies::ensure(__DIR__);
        $this->update_databases($company, array(
            '0_hrm_payroll_runs.sql' => array('hrm_payroll_runs'),
            '0_hrm_payroll_entries.sql' => array('hrm_payroll_entries'),
        ), false);
        return true;
    }
    private function ensure_composer_dependencies() {
        \Ksfraser\FrontAccounting\HRM\PayrollCalc\Utils\ComposerDependencies::ensure(__DIR__);
    }
    public function getModuleConstants(&$data, $opts = null) {
        $data['constants'] = array('SS_ksf_FA_HRM_PayrollCalc' => SS_ksf_FA_HRM_PayrollCalc, 'SA_PAYROLL_VIEW' => SA_PAYROLL_VIEW, 'SA_PAYROLL_MANAGE' => SA_PAYROLL_MANAGE);
        return true;
    }
    public function getModuleCapabilities(&$data, $opts = null) {
        $data['capabilities'] = self::RESPONDERS;
        return true;
    }
    public function hasCapability(&$data, $opts) {
        $capability = is_array($opts) && isset($opts['capability']) ? $opts['capability'] : null;
        if ($capability === null && isset($data['capability'])) { $capability = $data['capability']; }
        $data['has_capability'] = in_array($capability, self::RESPONDERS, true);
        return $data['has_capability'];
    }
    public function respondToCapabilityRequest(&$data, $opts = null) {
        $request = is_array($opts) && isset($opts['request']) ? $opts['request'] : (isset($data['request']) ? $data['request'] : 'capabilities');
        $data['request'] = $request;
        $data['module'] = $this->module_name;
        if (strpos($request, 'has:') === 0) { return $this->hasCapability($data, array('capability' => substr($request, 4))); }
        switch ($request) {
            case 'capabilities': return $this->getModuleCapabilities($data, $opts);
            case 'constants': return $this->getModuleConstants($data, $opts);
            default: $data['error'] = 'Unknown request type: ' . $request; return null;
        }
    }
    public function RELATED_TO_FIELDS(&$data, $opts = null) {
        if (!isset($data['module']) || !is_string($data['module'])) { $data['error'] = 'RELATED_TO_FIELDS requires data[module]'; return null; }
        if (!isset($data['type']) || !is_string($data['type'])) { $data['error'] = 'RELATED_TO_FIELDS requires data[type]'; return null; }
        if ($data['type'] !== 'payroll') { return null; }
        if (!isset($data['fields']) || !is_array($data['fields'])) { $data['fields'] = array(); }
        $data['fields'] = array_merge($data['fields'], array(
            'employeeId' => _('Employee'),
            'periodStart' => _('Period Start'),
            'periodEnd' => _('Period End'),
            'grossPay' => _('Gross Pay'),
            'netPay' => _('Net Pay'),
            'status' => _('Status'),
        ));
        return null;
    }
    public function PAYROLL_RESOLVE(&$data, $opts = null) {
        if (!isset($data['payroll_id']) || !is_numeric($data['payroll_id'])) { $data['error'] = 'PAYROLL_RESOLVE requires data[payroll_id]'; return null; }
        if (!isset($data['dto']) || !$data['dto'] instanceof RelatedRecordDto) { $data['error'] = 'PAYROLL_RESOLVE requires data[dto] as RelatedRecordDto'; return null; }
        $service = new \Ksfraser\FrontAccounting\HRM\PayrollCalc\Service\PayrollCalcService();
        $data['result'] = $service->resolveById((int) $data['payroll_id'], $data['dto']);
        return null;
    }
    public function PAYROLL_FIELDS(&$data, $opts = null) {
        $data['fields'] = array(
            'employeeId' => _('Employee'),
            'periodStart' => _('Period Start'),
            'periodEnd' => _('Period End'),
            'grossPay' => _('Gross Pay'),
            'netPay' => _('Net Pay'),
            'status' => _('Status'),
        );
        return null;
    }
    public function PAYROLL_CALC(&$data, $opts = null) {
        if (!isset($data['employee_id']) || !isset($data['period_start']) || !isset($data['period_end'])) {
            $data['error'] = 'PAYROLL_CALC requires data[employee_id], data[period_start], data[period_end]';
            return null;
        }
        $service = new \Ksfraser\FrontAccounting\HRM\PayrollCalc\Service\PayrollCalcService();
        $data['result'] = $service->calculatePayroll(
            $data['employee_id'],
            $data['period_start'],
            $data['period_end']
        );
        return null;
    }
}
