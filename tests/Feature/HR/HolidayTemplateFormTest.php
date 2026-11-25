<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Pages\CreateHolidayTemplate;
use App\Filament\Clusters\HRAttenanceCluster\Resources\HolidayTemplates\Pages\EditHolidayTemplate;
use App\Models\HolidayTemplate;
use App\Models\User;
use App\Modules\HR\Holidays\Rules\ValidHolidayTemplateDayRule;
use App\Modules\HR\Holidays\Services\HolidayDateResolverService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class HolidayTemplateFormTest extends TestCase
{
    use DatabaseTransactions;

    private function createSuperAdminUser(): User
    {
        $user = User::create([
            'name'     => 'Holiday Admin ' . Str::random(5),
            'email'    => 'holiday_admin_' . Str::random(8) . '@test.local',
            'password' => bcrypt('secret123'),
            'active'   => 1,
        ]);

        if (Role::where('id', 1)->exists()) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id'    => 1,
                'model_type' => User::class,
                'model_id'   => $user->id,
            ]);
        }

        $user->load('roles');

        $panel = Filament::getDefaultPanel();
        if ($panel) {
            Filament::setCurrentPanel($panel);
        }

        return $user;
    }

    public function test_model_calendar_day_helpers_enforce_month_limits_and_preserve_backward_compatibility(): void
    {
        // Backward compatibility: calling dayOptions() with only $type
        $this->assertCount(31, HolidayTemplate::dayOptions(HolidayTemplate::TYPE_FIXED));
        $this->assertCount(30, HolidayTemplate::dayOptions(HolidayTemplate::TYPE_HIJRI));

        // Gregorian month-specific limits
        $this->assertSame(31, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_FIXED, 1));
        $this->assertSame(29, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_FIXED, 2));
        $this->assertSame(30, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_FIXED, 4));
        $this->assertSame(30, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_FIXED, 6));
        $this->assertSame(30, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_FIXED, 9));
        $this->assertSame(30, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_FIXED, 11));
        $this->assertSame(31, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_FIXED, 12));

        $this->assertCount(29, HolidayTemplate::dayOptions(HolidayTemplate::TYPE_FIXED, 2));
        $this->assertCount(30, HolidayTemplate::dayOptions(HolidayTemplate::TYPE_FIXED, 4));

        // Hijri limits (always 30)
        $this->assertSame(30, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_HIJRI, 2));
        $this->assertSame(30, HolidayTemplate::maxDayForMonth(HolidayTemplate::TYPE_HIJRI, 9));

        // Validity checks
        $this->assertFalse(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_FIXED, 2, 30));
        $this->assertFalse(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_FIXED, 2, 31));
        $this->assertTrue(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_FIXED, 2, 29));
        $this->assertFalse(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_FIXED, 4, 31));
        $this->assertTrue(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_FIXED, 4, 30));
        $this->assertFalse(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_HIJRI, 9, 31));
        $this->assertTrue(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_HIJRI, 9, 30));
        $this->assertFalse(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_FIXED, 13, 1));
        $this->assertFalse(HolidayTemplate::isValidDayForMonth(HolidayTemplate::TYPE_FIXED, 1, 0));
    }

    public function test_validation_rule_rejects_impossible_calendar_dates_and_accepts_valid_dates(): void
    {
        $invalidCases = [
            [HolidayTemplate::TYPE_FIXED, 2, 30],
            [HolidayTemplate::TYPE_FIXED, 2, 31],
            [HolidayTemplate::TYPE_FIXED, 4, 31],
            [HolidayTemplate::TYPE_FIXED, 6, 31],
            [HolidayTemplate::TYPE_FIXED, 9, 31],
            [HolidayTemplate::TYPE_FIXED, 11, 31],
            [HolidayTemplate::TYPE_HIJRI, 9, 31],
        ];

        foreach ($invalidCases as [$type, $month, $day]) {
            $validator = Validator::make(
                ['day' => $day],
                ['day' => [new ValidHolidayTemplateDayRule($type, $month)]]
            );

            $this->assertTrue($validator->fails(), "Expected ({$type}, month={$month}, day={$day}) to fail validation.");
        }

        $validCases = [
            [HolidayTemplate::TYPE_FIXED, 2, 29],
            [HolidayTemplate::TYPE_FIXED, 4, 30],
            [HolidayTemplate::TYPE_FIXED, 1, 31],
            [HolidayTemplate::TYPE_HIJRI, 9, 30],
        ];

        foreach ($validCases as [$type, $month, $day]) {
            $validator = Validator::make(
                ['day' => $day],
                ['day' => [new ValidHolidayTemplateDayRule($type, $month)]]
            );

            $this->assertFalse($validator->fails(), "Expected ({$type}, month={$month}, day={$day}) to pass validation.");
        }
    }

    public function test_create_form_resets_month_and_day_when_switching_between_hijri_and_gregorian(): void
    {
        $admin = $this->createSuperAdminUser();
        $this->actingAs($admin);

        $component = Livewire::test(CreateHolidayTemplate::class)
            ->fillForm([
                'name'       => 'Switch Calendar Test ' . Str::random(6),
                'type'       => HolidayTemplate::TYPE_HIJRI,
                'count_days' => 3,
            ])
            ->fillForm([
                'month' => 9,
                'day'   => 15,
            ])
            ->assertFormSet([
                'type'  => HolidayTemplate::TYPE_HIJRI,
                'month' => 9,
                'day'   => 15,
            ]);

        // Switching from Hijri to Gregorian must clear month and day
        $component
            ->set('data.type', HolidayTemplate::TYPE_FIXED)
            ->assertSet('data.month', null)
            ->assertSet('data.day', null);

        // Verify the remounted month select resolves Gregorian options via its dynamic key
        $gregorianOptions = $component->instance()->callSchemaComponentMethod(
            'form.holiday_month_' . HolidayTemplate::TYPE_FIXED,
            'getOptionsForJs'
        );
        $this->assertNotEmpty($gregorianOptions);
        $this->assertSame('1 - January', $gregorianOptions[0]['label'] ?? null);

        // Select a Gregorian month/day then switch back to Hijri -> must clear again
        $component
            ->set('data.month', 2)
            ->set('data.day', 28)
            ->set('data.type', HolidayTemplate::TYPE_HIJRI)
            ->assertSet('data.month', null)
            ->assertSet('data.day', null);

        $hijriOptions = $component->instance()->callSchemaComponentMethod(
            'form.holiday_month_' . HolidayTemplate::TYPE_HIJRI,
            'getOptionsForJs'
        );
        $this->assertNotEmpty($hijriOptions);
        $this->assertSame('1 - Muharram', $hijriOptions[0]['label'] ?? null);
    }

    public function test_create_form_rejects_invalid_calendar_dates_and_resets_overflowing_day_on_month_change(): void
    {
        $admin = $this->createSuperAdminUser();
        $this->actingAs($admin);

        // Selecting Jan 31 then changing month to February must clear day to null;
        // changing from Jan 15 to February must preserve day 15.
        Livewire::test(CreateHolidayTemplate::class)
            ->set('data.type', HolidayTemplate::TYPE_FIXED)
            ->set('data.month', 1)
            ->set('data.day', 31)
            ->set('data.month', 2)
            ->assertSet('data.day', null)
            ->set('data.day', 15)
            ->set('data.month', 4)
            ->assertSet('data.day', 15);

        // Attempting to submit Feb 31 must fail validation and not persist
        $feb31Name = 'Invalid Feb 31 ' . Str::random(6);
        Livewire::test(CreateHolidayTemplate::class)
            ->fillForm([
                'name'         => $feb31Name,
                'type'         => HolidayTemplate::TYPE_FIXED,
                'count_days'   => 1,
                'month'        => 2,
                'day'          => 31,
                'all_branches' => true,
                'active'       => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['day']);

        $this->assertDatabaseMissing('hr_holiday_templates', ['name' => $feb31Name]);

        // Attempting to submit April 31 must also fail validation
        $apr31Name = 'Invalid Apr 31 ' . Str::random(6);
        Livewire::test(CreateHolidayTemplate::class)
            ->fillForm([
                'name'         => $apr31Name,
                'type'         => HolidayTemplate::TYPE_FIXED,
                'count_days'   => 1,
                'month'        => 4,
                'day'          => 31,
                'all_branches' => true,
                'active'       => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['day']);

        $this->assertDatabaseMissing('hr_holiday_templates', ['name' => $apr31Name]);

        // Valid Feb 29 must save cleanly and resolve properly for leap and non-leap years
        $feb29Name = 'Leap Day Holiday ' . Str::random(6);
        Livewire::test(CreateHolidayTemplate::class)
            ->fillForm([
                'name'         => $feb29Name,
                'type'         => HolidayTemplate::TYPE_FIXED,
                'count_days'   => 1,
                'month'        => 2,
                'day'          => 29,
                'all_branches' => true,
                'active'       => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $template = HolidayTemplate::where('name', $feb29Name)->firstOrFail();
        $resolver = app(HolidayDateResolverService::class);
        $this->assertSame('2028-02-29', $resolver->resolvePrimaryForYear($template, 2028)->fromDate);
        $this->assertSame('2026-02-28', $resolver->resolvePrimaryForYear($template, 2026)->fromDate);
    }

    public function test_edit_form_preserves_saved_hijri_month_and_day_when_type_is_not_changed(): void
    {
        $admin = $this->createSuperAdminUser();
        $this->actingAs($admin);

        $template = HolidayTemplate::create([
            'name'         => 'Eid Al-Fitr Test ' . Str::random(6),
            'type'         => HolidayTemplate::TYPE_HIJRI,
            'month'        => 10,
            'day'          => 1,
            'count_days'   => 3,
            'all_branches' => true,
            'active'       => true,
        ]);

        $newName = 'Eid Al-Fitr Updated ' . Str::random(6);

        Livewire::test(EditHolidayTemplate::class, ['record' => $template->getRouteKey()])
            ->assertFormSet([
                'type'  => HolidayTemplate::TYPE_HIJRI,
                'month' => 10,
                'day'   => 1,
            ])
            ->fillForm([
                'name' => $newName,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $template->refresh();
        $this->assertSame($newName, $template->name);
        $this->assertSame(10, $template->month);
        $this->assertSame(1, $template->day);
    }
}
