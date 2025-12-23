<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates a BEFORE INSERT trigger on hr_attendances table
     * that prevents checkout records without a linked checkin record
     */
    public function up(): void
    {
        // Drop the trigger if it already exists
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_checkout_without_checkin');

        // Create the trigger
        DB::unprepared("
            CREATE TRIGGER prevent_checkout_without_checkin
            BEFORE INSERT ON hr_attendances
            FOR EACH ROW
            BEGIN
                -- If the record is a checkout and checkinrecord_id is NULL or empty
                IF NEW.check_type = 'checkout' AND (NEW.checkinrecord_id IS NULL OR NEW.checkinrecord_id = 0) THEN
                    -- Raise an error to prevent the insert
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot insert checkout record without a valid checkinrecord_id';
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_checkout_without_checkin');
    }
};
