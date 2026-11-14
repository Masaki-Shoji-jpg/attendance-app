<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
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
        'new_break_in.*'     => 'nullable|required_with:new_break_out.*|date_format:H:i|after:new_clock_in|before:new_clock_out',
        'new_break_out'      => 'nullable|array',
        'new_break_out.*'    => 'nullable|required_with:new_break_in.*|date_format:H:i|after:new_break_in.*|before:new_clock_out',
    ];
}

    public function messages(): array
    {
        return [
            'new_clock_in.required'       => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_in.date_format'    => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.required'      => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.date_format'   => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.after'         => '出勤時間が不適切な値です',
            'comment.required'            => '備考を記入してください',

            'new_break_in.*.date_format'    => '休憩時間が不適切な値です',
            'new_break_in.*.after'          => '休憩時間が不適切な値です',
            'new_break_in.*.before'         => '休憩時間が不適切な値です',
            'new_break_in.*.required_with'  => '休憩時間が不適切な値です',

            'new_break_out.*.date_format'   => '休憩時間が不適切な値です',
            'new_break_out.*.after'         => '休憩時間が不適切な値です',
            'new_break_out.*.before'        => '休憩時間もしくは退勤時間が不適切な値です',
            'new_break_out.*.required_with' => '休憩時間が不適切な値です',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $errors = $validator->errors();
            $breakIn = $this->input('new_break_in', []);
            $clockIn = $this->input('new_clock_in',[]);

            if ($errors->has("new_clock_in") && $errors->has("new_clock_out")){
                $allErrors = $errors->getMessages();
                unset($allErrors["new_clock_out"]);

                $reflection = new \ReflectionClass($errors);
                $property = $reflection->getProperty('messages');
                $property->setAccessible(true);
                $property->setValue($errors, []);

                foreach ($allErrors as $key => $messages) {
                    foreach ($messages as $message) {
                        $errors->add($key, $message);
                    }
                }
            }

            foreach ($breakIn as $index => $value) {
                if ($errors->has("new_break_in.{$index}") && $errors->has("new_break_out.{$index}")) {
                    $allErrors = $errors->getMessages();
                    unset($allErrors["new_break_out.{$index}"]);

                    $reflection = new \ReflectionClass($errors);
                    $property = $reflection->getProperty('messages');
                    $property->setAccessible(true);
                    $property->setValue($errors, []);

                    foreach ($allErrors as $key => $messages) {
                        foreach ($messages as $message) {
                            $errors->add($key, $message);
                        }
                    }
                }
            }
        });
    }
}
