<?php 
namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

// app/Http/Requests/Task/RateTaskRequest.php
class RateTaskRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return [
      'rating_value' => 'required|integer|min:1|max:10',
      'comment'      => 'nullable|string|max:2000',
    ];
  }
}
