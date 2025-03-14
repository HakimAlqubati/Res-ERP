<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TaskCard extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'hr_task_cards';

    protected $fillable = [
        'task_id',
        'type',
        'employee_id',
        'active',
    ];
    protected $auditInclude = [
        'task_id',
        'type',
        'employee_id',
        'active',
    ];

    // Constants for types
    const TYPE_RED = 'red';
    const TYPE_YELLOW = 'yellow';

    // Constants for type colors
    const TYPE_COLORS = [
        self::TYPE_RED => 'danger', // Example color for red card
        self::TYPE_YELLOW => 'warning', // Example color for yellow card
    ];

    // Relationships

    /**
     * Task relationship
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Employee relationship
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
