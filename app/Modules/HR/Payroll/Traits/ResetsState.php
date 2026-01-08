<?php

namespace App\Modules\HR\Payroll\Traits;

trait ResetsState
{
    protected function applyDefaults(array $defaults): void
    {
        foreach ($defaults as $prop => $value) {
            $this->$prop = $value;
        }
    }
}
