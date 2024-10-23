<?php 

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbsentEmployeesMail extends Mailable
{
    use Queueable, SerializesModels;

    public $absentEmployees;
    public $date;

    /**
     * Create a new message instance.
     *
     * @param array $absentEmployees
     * @param string $date
     */
    public function __construct($absentEmployees, $date)
    {
        $this->absentEmployees = $absentEmployees;
        $this->date = $date;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // $this->absentEmployees = Employee::select('name','id')->get();
        // dd($this->absentEmployees);
        return $this->subject(subject: 'List of Absent Employees on ' . $this->date)
                    ->view('emails.absent-employees')
                    ->with([
                        'absentEmployees' => $this->absentEmployees,
                        'date' => $this->date,
                    ]);
    }
}
