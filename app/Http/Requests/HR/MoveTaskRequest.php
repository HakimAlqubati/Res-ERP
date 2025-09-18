<?php 
namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
// app/Http/Requests/Task/MoveTaskRequest.php
class MoveTaskRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return [
      'to' => 'required|in:new,in_progress,closed,rejected', // مع أن reject له مسار مخصص
    ];
  }
}
