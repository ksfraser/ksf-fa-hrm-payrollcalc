<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\Menu\FAModuleMenu;

class PayrollCalcAppShell extends AbstractAppShell
{
    public function __construct()
    {
        parent::__construct('payrollcalc', 'index.php', 'view', 'payroll');

        $this->add_module(_("Payroll Calculation"));

        $menu = new FAModuleMenu(
            'modules/ksf_FA_HRM_PayrollCalc/index.php',
            'view',
            ''
        );

        $menu->addItem('payroll', _("Payroll Runs"), MENU_INQUIRY)
             ->registerWithApp($this, 'SA_PAYROLL_VIEW');

        $this->add_extensions();
    }
}
