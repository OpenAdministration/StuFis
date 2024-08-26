<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'description' => ['required', 'string'],
        ];
    }

    public function prepareForValidation(){
        $this->merge([
            'name' => $this->project_name,
            'start_date' => $this->project_start_date,
            'end_date' => $this->project_end_date,
            'description' => $this->about,
        ]);
    }
}
