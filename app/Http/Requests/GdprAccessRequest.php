<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest per GDPR Art. 15 — Diritto di accesso.
 *
 * Autorizzazione: solo utenti non-staff (lo staff non ha profilo cliente).
 * Validazione: categoria obbligatoria, modalità consegna obbligatoria.
 */
class GdprAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && !\App\Support\AccessContext::for($user)->isStaff();
    }

    public function rules(): array
    {
        return [
            'object_category' => 'required|in:ordini,studio,tutto,altro',
            'delivery_mode'   => 'required|in:email,portale',
            'motivation'      => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'object_category.required' => 'Seleziona la categoria di dati oggetto della richiesta.',
            'object_category.in'       => 'Categoria non valida.',
            'delivery_mode.required'   => 'Indica la modalità di consegna preferita.',
            'delivery_mode.in'         => 'Modalità di consegna non valida.',
            'motivation.max'           => 'La motivazione non può superare i 2000 caratteri.',
        ];
    }
}
