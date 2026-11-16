<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\HRM\PayrollCalc\App;

use ksfraser\FrontAccounting\Common\App\AbstractFrontController;

class PayrollCalcFrontController extends AbstractFrontController
{
    public function __construct(
        PayrollCalcAppShell $appShell,
        string $fallbackSecurityArea,
        string $path_to_root
    ) {
        parent::__construct($appShell, $fallbackSecurityArea, $path_to_root);
    }
}
