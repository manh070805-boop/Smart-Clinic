<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'cccd' => [
                'nullable',
                'string',
                'digits:12',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10,11}$/',
            ],
            'dob' => [
                'nullable',
                'date',
                'before:today',
            ],
        ];
    }

    /**
     * Tùy biến thông báo lỗi tiếng Việt thân thiện.
     */
    public function messages(): array
    {
        return [
            'cccd.digits' => 'Số Căn cước công dân (CCCD) phải bao gồm đúng 12 chữ số.',
            'cccd.unique' => 'Số Căn cước công dân (CCCD) này đã được đăng ký bởi người dùng khác.',
            'phone.regex' => 'Số điện thoại không hợp lệ (phải gồm 10 đến 11 chữ số).',
            'dob.date' => 'Ngày sinh không đúng định dạng ngày tháng.',
            'dob.before' => 'Ngày sinh phải là một ngày trong quá khứ.',
        ];
    }
}
