<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuários de operação: cadastre-se na página inicial (/) ou use User::factory() nos testes.
        // descomente para criar um usuário fixo em ambientes de demo.
        // \App\Models\User::query()->firstOrCreate(
        //     ['email' => 'test@example.com'],
        //     ['name' => 'Test User', 'password' => 'password'],
        // );
    }
}
