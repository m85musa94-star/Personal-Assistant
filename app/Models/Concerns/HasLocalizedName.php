<?php

namespace App\Models\Concerns;

/** الاسم بلغة الواجهة: الإنجليزي إن وُجد عند اللغة الإنجليزية، وإلا الأصلي. */
trait HasLocalizedName
{
    public function displayName(): string
    {
        return app()->getLocale() === 'en' && filled($this->name_en) ? $this->name_en : $this->name;
    }
}
