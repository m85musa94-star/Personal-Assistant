<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * يُنشئ أول مدير من متغيرات البيئة عند أول نشر (لا يفعل شيئًا إن وُجد مستخدمون).
 *   ADMIN_EMAIL / ADMIN_PASSWORD (10 أحرف فأكثر) / ADMIN_NAME
 * ADMIN_RESET=true يعيد تعيين كلمة مرور ذلك الحساب ويفعّله — مخرج الطوارئ إن نُسيت كلمة المرور.
 */
class BootstrapAdmin extends Command
{
    protected $signature = 'markaz:bootstrap-admin';

    protected $description = 'Create or reset the first admin from environment variables';

    public function handle(): int
    {
        $email = Str::lower($this->env('ADMIN_EMAIL'));
        $password = $this->env('ADMIN_PASSWORD');
        $name = $this->env('ADMIN_NAME') ?: 'المدير';
        $reset = filter_var($this->env('ADMIN_RESET'), FILTER_VALIDATE_BOOLEAN);

        if (! $reset && User::query()->exists()) {
            $this->info('Users already exist; nothing to do.');

            return self::SUCCESS;
        }
        if ($email === '' || $password === '') {
            $this->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set; nothing to do.');

            return self::SUCCESS;
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid ADMIN_EMAIL: {$email}");

            return self::FAILURE;
        }
        if (mb_strlen($password) < 10) {
            $this->error('ADMIN_PASSWORD must be at least 10 characters.');

            return self::FAILURE;
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first() ?? new User(['name' => $name]);
        $existed = $user->exists;
        $user->email = $email;
        $user->password = $password;
        $user->is_admin = true;
        $user->is_active = true;
        $user->save();

        $this->info($existed ? "Admin password reset for {$email}." : "Admin created: {$email}.");

        return self::SUCCESS;
    }

    /** قيم لوحات الاستضافة كثيرًا ما تُكتب بين علامتي اقتباس بالخطأ. */
    private function env(string $key): string
    {
        $v = trim((string) getenv($key));
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[-1] === $v[0]) {
            $v = trim(substr($v, 1, -1));
        }

        return $v;
    }
}
