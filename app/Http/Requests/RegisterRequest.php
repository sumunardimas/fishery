<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow all users to register
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'whatsapp' => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'role' => 'required|in:Admin,Kasir,Staff',
            'committee' => 'nullable|string|max:100',
            'institution' => 'required|string|max:255',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Password minimal 6 karakter.',
            'role.in' => 'Peran tidak valid.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'document.mimes' => 'Dokumen harus berupa file PDF, DOC, atau DOCX.',
        ];
    }
}
