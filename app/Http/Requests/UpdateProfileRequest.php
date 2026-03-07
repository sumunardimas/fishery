<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authenticated users can update their profile
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'nama'        => ['required', 'string', 'max:255'],
            'email'       => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'whatsapp'    => ['required', 'string', 'max:20'],
            'gender'      => ['nullable', Rule::in(['0', '1'])], // 1 = laki-laki, 0 = perempuan
            // 'role'        => ['required', Rule::in(['panitia', 'kasir', 'staff'])],
            // 'committee'   => ['nullable', 'string', 'max:100'],
            // 'institusi_id'=> ['required', 'integer', 'exists:institusis,id'],

            // document optional
            'document'    => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],

            // password optional, but rules apply if provided
            'current_password'      => ['nullable', 'required_with:password,password_confirmation', 'current_password'],
            'password'              => ['nullable', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'            => 'Email sudah terdaftar.',
            'password.min'            => 'Password minimal 6 karakter.',
            'password.confirmed'      => 'Konfirmasi password tidak cocok.',
            'role.in'                 => 'Peran tidak valid.',
            'gender.in'               => 'Jenis kelamin tidak valid.',
            'document.mimes'          => 'Dokumen harus berupa file PDF, DOC, atau DOCX.',
            'current_password.required_with' => 'Password saat ini wajib diisi jika ingin mengganti password.',
            'current_password.current_password' => 'Password saat ini salah.',
            'institusi_id.exists'     => 'Institusi tidak valid.',
        ];
    }
}
