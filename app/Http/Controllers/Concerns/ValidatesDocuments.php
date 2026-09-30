<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ValidatesDocuments
{
    /** @param  array<int, string>  $types */
    protected function validatedDocument(Request $request, array $types): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', $types)],
            'title' => ['nullable', 'string', 'max:160'],
            'number' => ['nullable', 'string', 'max:60'],
            'provider' => ['nullable', 'string', 'max:160'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['expiry_date.after_or_equal' => __('تاريخ الانتهاء قبل تاريخ الإصدار.')]);

        return array_map(fn ($v) => $v === '' ? null : $v, $data);
    }
}
