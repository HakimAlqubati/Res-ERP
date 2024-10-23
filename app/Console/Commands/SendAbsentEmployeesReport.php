<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use Illuminate\Support\Facades\Mail;
use App\Mail\AbsentEmployeesMail;
use Carbon\Carbon;

class SendAbsentEmployeesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:send-absent-employees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a report of absent employees to the manager';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Define the date for which to check attendance (today)
        $date = Carbon::now()->format('Y-m-d');
        
        // Get all employees
        $employees = Employee::all();
        $absentEmployees = [];

        // Loop through employees and check if they have attendance for the date
        foreach ($employees as $employee) {
            $attendance = $employee->attendancesByDate($date)->exists();
            if (!$attendance) {
                $absentEmployees[] = $employee;
            }
        }

        // If there are absent employees, send an email to the manager
        if (!empty($absentEmployees)) {
            $managerEmail = 'hakimahmed123321@gmail.com';

            // Send an email with the list of absent employees
            Mail::to($managerEmail)->send(new AbsentEmployeesMail($absentEmployees, $date));

            $this->info('Absent employees report sent to the manager.');
        } else {
            $this->info('No absent employees found.');
        }

        return Command::SUCCESS;
    }
}
