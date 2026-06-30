<?php

namespace App\Observers;

use App\Models\AdvanceRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidAdvanceRequestDateRule;

/**
 * Observer for AdvanceRequest model.
 *
 * Validates business rules before creating a new advance request:
 *  - Employee must not already have an advance request in the same month.
 *  - Employee must not have any outstanding (unpaid) installments from previous advances.
 */
class AdvanceRequestObserver
{
    /**
     * @throws ValidationException
     */
    public function creating(AdvanceRequest $advance): void
    {
        $validator = Validator::make(
            ['date' => $advance->date],
            ['date' => [new ValidAdvanceRequestDateRule($advance->employee_id, $advance->id)]]
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'advance_request' => $validator->errors()->first('date'),
            ]);
        }
    }
}
