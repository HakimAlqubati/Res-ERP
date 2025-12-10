<?php
return [
    'welcome_employee'                                                                => 'Welcome',
    'the_attendance_has_been_recorded'                                                => 'The attendance has been recorded',
    'the_departure_has_been_recorded'                                                 => 'The departure has been recorded',
    'please_wait_for_a'                                                               => 'Please wait for ',
    'minutue'                                                                         => 'minutues',
    'second'                                                                          => 'second',
    'attendance_time_is_greater_than_current_period_end_time'                         => 'Attendance time is greater than current period end time',
    'no_valid_period_found_for_the_specified_time'                                    => 'No valid period found for the specified time.',
    'you_dont_have_periods_today'                                                     => 'You don t have periods today.',
    'sorry_no_working_hours_have_been_added_to_you_please_contact_the_administration' => 'Sorry, no working hours have been added to you, please contact the administration!',
    'there_is_no_employee_at_this_number'                                             => 'There is no employee at this number',
    'notify'                                                                          => 'Notify',
    'you_cannot_attendance_before'                                                    => 'You can not checkin before',
    'hours'                                                                           => 'Hours',
    'cannot_check_in_because_adjust'                                                  => 'You cannot check in right now. Please contact your manager to adjust your shift.',
    'attendance_out_of_range_before_period' =>
    'You cannot check in at this time. You are outside the allowed period before your shift starts. Please try again during the permitted time before your shift.',
    'attendance_success' => 'Attendance success'
    /*
    |--------------------------------------------------------------------------
    | Attendance Notifications
    |--------------------------------------------------------------------------
    */,
    'attendance_already_completed_for_date' => 'Attendance for this date (:date) is already completed.',
    'you_are_already_checked_in'            => 'You are already checked in.',
    'cannot_checkout_without_checkin'       => 'Cannot check out without a prior check-in record.',

    'check_in_success'  => 'Checked in successfully.',
    'check_out_success' => 'Checked out successfully.',

    'you_dont_have_periods_today' => 'You do not have any work shifts scheduled for today.',

    // Cannot auto-checkin near shift end
    'cannot_auto_checkin_near_shift_end' => 'Cannot auto-checkin near shift end without prior records. Please manually select the operation type (checkin/checkout).',

];
