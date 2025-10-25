<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
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
            'pharma_id' => 'sometimes|exists:pharmas,id',
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:branches,phone,' . $this->branch,
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'opening_hours' => 'nullable|date_format:H:i',
            'closing_hours' => 'nullable|date_format:H:i',
        ];
    }
}
