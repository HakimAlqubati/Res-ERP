<?php 
namespace App\Traits\HR\Payroll;
trait ResetsState
{
    protected function applyDefaults(array $defaults): void
    {
        foreach ($defaults as $prop => $value) {
            $this->$prop = $value;
        }
    }
}
