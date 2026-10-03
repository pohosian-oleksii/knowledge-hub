<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ContextEntryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BulkStoreContextEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.type' => ['required', Rule::in(ContextEntryType::values())],
            '*.title' => ['required', 'string', 'max:255'],
            '*.content' => ['required', 'string'],
            '*.tags' => ['nullable', 'array'],
            '*.tags.*' => ['string', 'max:255'],
            '*.source_agent' => ['nullable', 'string', 'max:255'],
            '*.importance' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
