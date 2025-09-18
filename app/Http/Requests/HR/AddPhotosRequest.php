<?php 
namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
// app/Http/Requests/Task/AddPhotosRequest.php
class AddPhotosRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return ['files.*' => 'required|file|image|max:5120']; // 5MB
  }
}
