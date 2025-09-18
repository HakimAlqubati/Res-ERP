<?php 
namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

// app/Http/Requests/TaskStep/ToggleStepRequest.php
class ToggleStepRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array { return []; }
}
