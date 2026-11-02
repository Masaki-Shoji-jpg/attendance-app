<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceApplicationRequest extends FormRequest
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
            'new_clock_in'       => 'required|date_format:H:i',
            'new_clock_out'      => 'required|date_format:H:i|after:new_clock_in',
            'comment'            => 'required|string|max:255',
            'new_break_in'       => 'nullable|array',
            'new_break_in.*'     => 'nullable|required_with:new_break_out.*|date_format:H:i|after:new_clock_in',
            'new_break_out'      => 'nullable|array',
            'new_break_out.*'    => 'nullable|required_with:new_break_in.*|date_format:H:i|after:new_break_in.*|before:new_clock_out',
        ];
    }

    public function messages(): array{
        return [
            'new_clock_in.required'       => '出勤時間は必須です。',
            'new_clock_out.required'      => '退勤時間は必須です。',
            'new_clock_out.after'         => '出勤時間が不適切な値です。',
            'comment.required'            => '備考を記入してください。',

            'new_break_in.*.required_with'  => '休憩終了時間が入力されている場合、休憩開始時間は必須です。',
            'new_break_out.*.required_with' => '休憩開始時間が入力されている場合、休憩終了時間は必須です。',
            'new_break_in.*.after'          => '休憩開始時間は出勤時間より後の時間を入力してください。',
            'new_break_out.*.after'         => '休憩時間が不適切な値です。',
            'new_break_out.*.before'        => '休憩時間もしくは退勤時間が不適切な値です。',
        ];
    }
}
