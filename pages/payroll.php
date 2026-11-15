<?php
declare(strict_types=1);
use Ksfraser\FrontAccounting\HRM\PayrollCalc\Service\PayrollCalcService;
use ksfraser\RelatedTo\DTO\RelatedQueryDto;
use ksfraser\RelatedTo\DTO\RelatedRecordDto;
use ksfraser\RelatedTo\UI\DefaultRelatedRecordsRenderer;
$path_to_root = '../../..';
include_once($path_to_root . '/includes/session.inc');
add_access_extensions();
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) { display_error(_('Payroll Calc module dependencies are not installed')); exit; }
require_once $autoload;
$service = new PayrollCalcService();
$action = isset($_GET['action']) ? (string) $_GET['action'] : 'list';
if ($action === 'list') {
    page(_('Payroll Runs'));
    $runs = $service->listAll();
    echo '<p>' . _('Payroll Runs') . '</p>';
    echo '<table class="selection">';
    echo '<thead><tr>' . '<th>' . _('Employee') . '</th>' . '<th>' . _('Period Start') . '</th>' . '<th>' . _('Period End') . '</th>' . '<th>' . _('Gross Pay') . '</th>' . '<th>' . _('Net Pay') . '</th>' . '<th>' . _('Status') . '</th>' . '<th>' . _('Actions') . '</th>' . '</tr></thead><tbody>';
    foreach ($runs as $r) {
        echo '<tr>' . '<td>' . htmlspecialchars($r['fields']['employeeId'], ENT_QUOTES, 'UTF-8') . '</td>' . '<td>' . htmlspecialchars($r['fields']['periodStart'], ENT_QUOTES, 'UTF-8') . '</td>' . '<td>' . htmlspecialchars($r['fields']['periodEnd'], ENT_QUOTES, 'UTF-8') . '</td>' . '<td>' . htmlspecialchars($r['fields']['grossPay'], ENT_QUOTES, 'UTF-8') . '</td>' . '<td>' . htmlspecialchars($r['fields']['netPay'], ENT_QUOTES, 'UTF-8') . '</td>' . '<td>' . htmlspecialchars($r['fields']['status'], ENT_QUOTES, 'UTF-8') . '</td>' . '<td><a href="' . htmlspecialchars($r['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank">' . _('Edit') . '</a></td>' . '</tr>';
    }
    echo '</tbody></table>';

    // Related records rendered via RelatedTo hook
    $lhsId = isset($_GET['id']) ? (string) $_GET['id'] : '';
    if (function_exists('hook_invoke') && class_exists(DefaultRelatedRecordsRenderer::class) && $lhsId !== '') {
        $queryDto = new RelatedQueryDto('payroll', $lhsId);
        $queryResult = $queryDto;
        hook_invoke('ksf_FA_RelatedTo', 'RELATED_TO', $queryResult, null);
        if (is_array($queryResult) && isset($queryResult['related']) && is_array($queryResult['related'])) {
            $renderer = new DefaultRelatedRecordsRenderer(array(
                'label_resolver' => function ($type, $id) { return (string) $id; },
                'url_resolver'   => function ($type, $id) { return '#'; },
                'type_labels'    => array('payroll' => _('Payroll Runs')),
            ));
            echo $renderer->renderRelatedRecords('payroll', $lhsId, $queryResult['related'], array(
                'css_class' => 'related-records',
                'header_class' => 'related-header',
                'table_class' => 'related-table',
                'table_border' => true,
            ));
        }
    }

    end_page();
    exit;
}
display_error(_('Unknown action'));
exit;
