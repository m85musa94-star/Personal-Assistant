<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** مخرج الطوارئ من تبويب Commands في المنصة: php artisan markaz:set-password البريد كلمة-المرور */
class SetPassword extends Command
{
    protected $signature = 'markaz:set-password {email} {password} {--name=المدير}';

    protected $description = 'Create an admin or reset a user password (emergency recovery)';

    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $password = (string) $this->argument('password');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 10) {
            $this->error('بريد غير صالح أو كلمة المرور أقل من 10 أحرف.');

            return self::FAILURE;
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first() ?? new User(['name' => $this->option('name')]);
        $created = ! $user->exists;
        $user->email = $email;
        $user->password = $password;
        $user->is_active = true;
        if ($created || ! User::where('is_admin', true)->exists()) {
            $user->is_admin = true;
        }
        $user->save();

        $this->info($created ? "Created admin: {$email}" : "Password reset: {$email}");

        return self::SUCCESS;
    }
}
