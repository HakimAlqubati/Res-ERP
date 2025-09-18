<?php 
namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;
// app/Http/Requests/Task/AddCommentRequest.php
class AddCommentRequest extends FormRequest {
  public function authorize(): bool { return true; }
  public function rules(): array {
    return ['comment'=>'required|string|max:5000'];
  }
}
