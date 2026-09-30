<?php

namespace Tests\Feature;

use App\Models\EmployeeDocument;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleRecord;
use Tests\TestCase;

/** كل نص عربي يمر عبر __() أو t() له ترجمة إنجليزية، وإلا يظهر عربي في الواجهة الإنجليزية. */
class TranslationCoverageTest extends TestCase
{
    private function files(string $dir, string $ext): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (str_ends_with($f->getFilename(), $ext)) {
                $out[] = $f->getPathname();
            }
        }

        return $out;
    }

    public function test_server_strings_have_english(): void
    {
        $en = json_decode(file_get_contents(base_path('lang/en.json')), true);
        $missing = [];
        foreach (array_merge($this->files('app', '.php'), $this->files('resources/views', '.blade.php')) as $f) {
            preg_match_all('/__\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/u', file_get_contents($f), $m);
            foreach (array_merge($m[1], $m[2]) as $k) {
                $k = str_replace("\\'", "'", $k);
                if ($k !== '' && preg_match('/[\x{0600}-\x{06FF}]/u', $k) && ! isset($en[$k])) {
                    $missing[$k] = basename($f);
                }
            }
        }
        $this->assertSame([], $missing, 'Missing English translations: '.json_encode($missing, JSON_UNESCAPED_UNICODE));
    }

    public function test_js_strings_have_english(): void
    {
        $js = file_get_contents(base_path('public/app/app.js'));
        $i18n = file_get_contents(base_path('public/app/i18n.js'));
        $dict = json_decode(substr($i18n, strpos($i18n, '{'), strrpos($i18n, '}') - strpos($i18n, '{') + 1), true);
        preg_match_all("/'((?:[^'\\\\\\n]|\\\\.)*)'/u", $js, $m);
        $missing = [];
        foreach ($m[1] as $k) {
            $k = str_replace("\\'", "'", $k);
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $k) && ! isset($dict[$k])) {
                $missing[] = $k;
            }
        }
        $this->assertSame([], array_values(array_unique($missing)), 'Missing JS English translations');
    }

    public function test_type_labels_exist_in_both_languages(): void
    {
        $ar = require base_path('lang/ar/types.php');
        $en = require base_path('lang/en/types.php');
        foreach ($ar as $group => $items) {
            $this->assertSame(array_keys($items), array_keys($en[$group]), $group);
        }
        $this->assertSame(array_keys(EmployeeDocument::TYPES ? array_flip(EmployeeDocument::TYPES) : []), array_keys($ar['employee_doc']));
        $this->assertSame(array_keys(array_flip(VehicleDocument::TYPES)), array_keys($ar['vehicle_doc']));
        $this->assertSame(array_keys(array_flip(VehicleRecord::TYPES)), array_keys($ar['vehicle_record']));
        $this->assertSame(array_keys(array_flip(Vehicle::STATUSES)), array_keys($ar['vehicle_status']));
    }
}
