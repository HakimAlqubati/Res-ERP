<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ResetAllUsersPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset-pass
                            {--password=hakim@123 : The new plain-text password to assign to all users}
                            {--force : Force the operation without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Update the password field for all users in the current database to 'hakim@123' (or a specified password).";

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $password = (string) ($this->option('password') ?: 'hakim@123');

        $totalUsers = DB::table('users')->count();

        if ($totalUsers === 0) {
            $this->warn('No users found in the current database.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                "Are you sure you want to update the password for all {$totalUsers} user(s) to '{$password}'?",
                true
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled by user.');
                return self::SUCCESS;
            }
        }

        $this->info("🔄 Updating passwords for {$totalUsers} user(s)...");

        DB::beginTransaction();

        try {
            $hashedPassword = Hash::make($password);

            $updatedRows = DB::table('users')->update([
                'password'   => $hashedPassword,
                'updated_at' => now(),
            ]);

            DB::commit();

            $this->newLine();
            $this->info("✔ Successfully updated password for {$updatedRows} user(s) to '{$password}'.");
            $this->line("🔑 Hashed password generated using Hash::make().");

            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();

            $this->error("❌ Failed to update user passwords: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
