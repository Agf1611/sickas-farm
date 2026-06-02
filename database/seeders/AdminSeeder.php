<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\SickasFarmPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SickasFarmRoleSeeder::class);

        $name = trim((string) config('sickas.admin.name', 'Admin SICKAS FARM')) ?: 'Admin SICKAS FARM';
        $email = trim((string) config('sickas.admin.email', 'admin@sickas.local')) ?: 'admin@sickas.local';
        $configuredPassword = config('sickas.admin.password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('SICKAS_ADMIN_EMAIL harus berupa alamat email yang valid.');
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $generatedPassword = null;
            $password = filled($configuredPassword) ? (string) $configuredPassword : null;

            if (! $password) {
                $generatedPassword = Str::password(20);
                $password = $generatedPassword;
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->command?->info("User admin awal dibuat: {$email}");

            if ($generatedPassword) {
                $this->command?->warn('Password sementara dibuat otomatis. Simpan sekarang dan ganti setelah login pertama.');
                $this->command?->line("Password sementara: {$generatedPassword}");
            }
        } else {
            $user->forceFill([
                'name' => $name,
            ])->save();

            $this->command?->info("User admin sudah ada, password tidak diubah: {$email}");
        }

        if (! $user->hasRole(SickasFarmPermissions::SUPER_ADMIN)) {
            $user->assignRole(SickasFarmPermissions::SUPER_ADMIN);
        }
    }
}
