<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Elegant\Sanitizer\Laravel\SanitizesInput;

class LoginRequest extends FormRequest
{
    use SanitizesInput;
    
    public function filters()
    {
        return [
            'email' => 'trim|strip_tags',
            'password' => 'trim|strip_tags',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'type' => 'required|in:admin,user',
            'email' => 'required|exists:'.$this->type.'s,email|max:60',
            'password' => 'required|max:60',
        ];
    }
}
