<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest per GDPR Art. 16 — Diritto di rettifica.
 *
 * Autorizzazione: solo utenti non-staff.
 * Nota MDR: dati di ordini is_locked=true non sono modificabili; la rettifica
 * risulterà in una nota allegata, non in modifica del dato originale.
 */
class GdprRectificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && !\App\Support\AccessContext::for($user)->isStaff();
    }

    public function rules(): array
    {
        return [
            'field'           => 'required|string|max:100',
            'current_value'   => 'nullable|string|max:500',
            'requested_value' => 'required|string|max:500',
            'motivation'      => 'required|string|min:20|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'field.required'           => 'Indica il campo da rettificare.',
            'field.max'                => 'Nome campo troppo lungo (max 100 caratteri).',
            'requested_value.required' => 'Indica il valore corretto richiesto.',
            'requested_value.max'      => 'Il valore richiesto non può superare i 500 caratteri.',
            'motivation.required'      => 'La motivazione è obbligatoria.',
            'motivation.min'           => 'La motivazione deve contenere almeno 20 caratteri.',
            'motivation.max'           => 'La motivazione non può superare i 2000 caratteri.',
        ];
    }
}
