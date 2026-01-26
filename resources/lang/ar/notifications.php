<?php
return [
    'welcome_employee'                                                                => 'مرحباً موظفنا العزيز',
    'the_attendance_has_been_recorded'                                                => 'تم تسجيل الحضور',
    'the_departure_has_been_recorded'                                                 => 'تم تسجيل الانصراف',
    'please_wait_for_a'                                                               => 'يرجى الانتظار لمدة ',
    'minutue'                                                                         => 'دقيقة',
    'second'                                                                          => 'ثانية',
    'attendance_time_is_greater_than_current_period_end_time'                         => 'وقت الحضور يتجاوز وقت نهاية الفترة الحالية',
    'no_valid_period_found_for_the_specified_time'                                    => 'لم يتم العثور على فترة صالحة للوقت المحدد.',
    'you_dont_have_periods_today'                                                     => 'ليس لديك فترات اليوم.',
    'sorry_no_working_hours_have_been_added_to_you_please_contact_the_administration' => 'عذرًا، لم يتم إضافة ساعات العمل الخاصة بك، يرجى التواصل مع الإدارة!',
    'there_is_no_employee_at_this_number'                                             => 'لا يوجد موظف بهذا الرقم',
    'notify'                                                                          => 'تنبيه',
    'you_cannot_attendance_before'                                                    => 'لا تستطيع تسجيل الحضور قبل',
    'hours'                                                                           => 'ساعات',
    'cannot_check_in_because_adjust'                                                  => 'لا يمكنك تسجيل الحضور في الوقت الحالي. يرجى التواصل مع مديرك لتعديل الشيفت.',
    'attendance_out_of_range_before_period'                                           => 'لا يمكنك تسجيل الحضور في هذا الوقت، أنت خارج الفترة المسموح بها قبل بداية الدوام. يرجى المحاولة في الوقت المسموح قبل بدء الدوام',
    'attendance_success'                                                              => 'تم بنجاح',

    /*
    |--------------------------------------------------------------------------
    | تنبيهات نظام الحضور والانصراف
    |--------------------------------------------------------------------------
    */

    // رسالة عند محاولة البصمة في يوم مكتمل (دخول + خروج)
    'attendance_already_completed_for_date' => 'عفواً، لقد تم استكمال سجل الحضور والانصراف لهذا اليوم (:date) مسبقاً.',

    // رسالة عند محاولة عمل "دخول" وهو موجود بالفعل داخل العمل
    'you_are_already_checked_in' => 'عفواً، لديك عملية دخول مسجلة مسبقاً ومفتوحة حالياً.',

    // رسالة عند محاولة عمل "خروج" بدون وجود سجل "دخول"
    'cannot_checkout_without_checkin' => 'عفواً، لا يمكن تسجيل انصراف لعدم وجود سجل دخول لهذا اليوم.',

    // رسائل نجاح العملية (للاستخدام العام)
    'check_in_success' => 'تم تسجيل الدخول بنجاح.',
    'check_out_success' => 'تم تسجيل الانصراف بنجاح.',

    // رسائل عامة
    'you_dont_have_periods_today' => 'عفواً، لا توجد لك فترات عمل مجدولة لهذا اليوم.',

    // رسالة عند محاولة تسجيل دخول تلقائي قرب نهاية الشيفت بدون سجلات
    'cannot_auto_checkin_near_shift_end' => 'عفواً، لا يمكن تسجيل الدخول التلقائي قرب نهاية الدوام. يرجى تحديد نوع العملية (دخول/خروج) يدوياً.',

    // رسالة عند محاولة التسجيل في نفس اللحظة بالضبط
    'duplicate_timestamp_not_allowed' => 'يرجى الانتظار :seconds قبل التسجيل مرة أخرى.',

    // رسائل الورديات المتعددة
    'multiple_shifts_available' => 'يوجد أكثر من وردية متاحة. يرجى اختيار الوردية المطلوبة.',
    'shift' => 'الوردية',
    'starts_in_minutes' => 'تبدأ بعد :minutes دقيقة',
    'ended_minutes_ago' => 'انتهت منذ :minutes دقيقة',
    'currently_active' => 'جارية حالياً',

    // رسائل تعارض الورديات
    'shift_conflict_detected' => 'يوجد تعارض بين الورديات. يرجى اختيار العملية المطلوبة.',
    'checkout_from_shift' => 'تسجيل انصراف من :shift',
    'checkin_to_shift' => 'تسجيل دخول إلى :shift',

];
