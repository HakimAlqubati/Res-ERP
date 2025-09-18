<?php 
namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
// app/Http/Requests/Task/RejectTaskRequest.php
class RejectTaskRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return [
      'reject_reason' => 'required|string|max:2000',
    ];
  }
}
