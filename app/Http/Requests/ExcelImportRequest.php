<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExcelImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Update this according to your authorization logic
        // Return true if the request is authorized
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'excel_file' => 'required|file|mimes:xlsx', // Validating for Excel file type (xlsx)
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'excel_file.required' => 'An Excel file is required.',
            'excel_file.file' => 'The uploaded file must be a file.',
            'excel_file.mimes' => 'The file must be a valid Excel file with xlsx extension.',
        ];
    }
}
