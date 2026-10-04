<?php

namespace App\Support;

/** قائمة الجنسيات (تُخزَّن بالعربية، وتُعرض بلغة الواجهة). */
class Nationalities
{
    private const LIST = [
        'سعودي' => 'Saudi', 'مصري' => 'Egyptian', 'سوداني' => 'Sudanese', 'يمني' => 'Yemeni', 'سوري' => 'Syrian', 'أردني' => 'Jordanian',
        'لبناني' => 'Lebanese', 'فلسطيني' => 'Palestinian', 'عراقي' => 'Iraqi', 'إماراتي' => 'Emirati', 'كويتي' => 'Kuwaiti', 'قطري' => 'Qatari',
        'بحريني' => 'Bahraini', 'عماني' => 'Omani', 'ليبي' => 'Libyan', 'تونسي' => 'Tunisian', 'جزائري' => 'Algerian', 'مغربي' => 'Moroccan',
        'موريتاني' => 'Mauritanian', 'صومالي' => 'Somali', 'جيبوتي' => 'Djiboutian', 'جزر القمر' => 'Comorian',
        'هندي' => 'Indian', 'باكستاني' => 'Pakistani', 'بنغلاديشي' => 'Bangladeshi', 'سريلانكي' => 'Sri Lankan', 'نيبالي' => 'Nepali',
        'أفغاني' => 'Afghan', 'إيراني' => 'Iranian', 'تركي' => 'Turkish', 'فلبيني' => 'Filipino', 'إندونيسي' => 'Indonesian', 'ماليزي' => 'Malaysian',
        'تايلندي' => 'Thai', 'فيتنامي' => 'Vietnamese', 'صيني' => 'Chinese', 'ياباني' => 'Japanese', 'كوري' => 'Korean', 'ميانماري' => 'Myanmar',
        'إثيوبي' => 'Ethiopian', 'إريتري' => 'Eritrean', 'كيني' => 'Kenyan', 'أوغندي' => 'Ugandan', 'تنزاني' => 'Tanzanian', 'نيجيري' => 'Nigerian',
        'غاني' => 'Ghanaian', 'سنغالي' => 'Senegalese', 'مالي' => 'Malian', 'تشادي' => 'Chadian', 'جنوب أفريقي' => 'South African',
        'أمريكي' => 'American', 'كندي' => 'Canadian', 'بريطاني' => 'British', 'أيرلندي' => 'Irish', 'فرنسي' => 'French', 'ألماني' => 'German',
        'إيطالي' => 'Italian', 'إسباني' => 'Spanish', 'برتغالي' => 'Portuguese', 'هولندي' => 'Dutch', 'بلجيكي' => 'Belgian', 'سويسري' => 'Swiss',
        'سويدي' => 'Swedish', 'نرويجي' => 'Norwegian', 'دنماركي' => 'Danish', 'فنلندي' => 'Finnish', 'يوناني' => 'Greek', 'روسي' => 'Russian',
        'أوكراني' => 'Ukrainian', 'بولندي' => 'Polish', 'روماني' => 'Romanian', 'أسترالي' => 'Australian', 'نيوزيلندي' => 'New Zealander',
        'برازيلي' => 'Brazilian', 'أرجنتيني' => 'Argentine', 'مكسيكي' => 'Mexican', 'كازاخستاني' => 'Kazakh', 'أوزبكي' => 'Uzbek', 'أذربيجاني' => 'Azerbaijani',
    ];

    /** @return array<string,string> قيمة مخزّنة => اسم معروض بلغة الواجهة */
    public static function options(?string $current = null): array
    {
        $en = app()->getLocale() === 'en';
        $out = [];
        foreach (self::LIST as $ar => $e) {
            $out[$ar] = $en ? $e : $ar;
        }
        $en ? asort($out) : uasort($out, fn ($a, $b) => strcmp($a, $b));
        if ($current && ! isset($out[$current])) {
            $out = [$current => $current] + $out;
        }

        return $out;
    }

    public static function label(?string $ar): ?string
    {
        if (! $ar) {
            return $ar;
        }

        return app()->getLocale() === 'en' ? (self::LIST[$ar] ?? $ar) : $ar;
    }
}
