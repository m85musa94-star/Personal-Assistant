<?php

namespace App\Models\Concerns;

/** وثيقة لها تاريخ انتهاء: الأيام المتبقية ودرجة الإلحاح (الأحمر منتهية، البرتقالي ≤ 30 يومًا، الأصفر ≤ 60). */
trait TracksExpiry
{
    public const WARN_DAYS = 60;

    public function daysLeft(): ?int
    {
        return $this->expiry_date ? (int) today()->diffInDays($this->expiry_date, false) : null;
    }

    /** red | amb | yel | '' (لا تنبيه) */
    public function tone(): string
    {
        $d = $this->daysLeft();

        return match (true) {
            $d === null => '',
            $d < 0 => 'red',
            $d <= 30 => 'amb',
            $d <= self::WARN_DAYS => 'yel',
            default => '',
        };
    }

    public function scopeExpiringWithin($query, int $days = self::WARN_DAYS)
    {
        return $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', today()->addDays($days));
    }
}
