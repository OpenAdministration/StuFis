<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AntragRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'salutation' => ['required', 'string'],
            'org_name' => ['string'],
            'name' => ['required', 'string'],
            'street_and_number' => ['required', 'string'],
            'zip_and_place' => ['required', 'string'],
            'email' => ['required', 'email:rfc,dns'],
            'phone' => ['required', 'numeric'],
            'iban' => ['required', 'iban'],
            'bic' => ['required', 'bic'],
            'nonprofit',
            'tax-deduction',
            'register-number' => ['numeric'],
            'website' => ['url:http,https'],
            'status-group',
            'project-name' => ['required', 'string'],
            'project-start-date' => ['required', 'date'],
            'project-end-date' => ['required', 'date'],
            'description' => ['required', 'string'],

        ];
    }

    public function prepareForValidation(){
        $this->merge([
            'description' => $this->about,
        ]);
    }
}
