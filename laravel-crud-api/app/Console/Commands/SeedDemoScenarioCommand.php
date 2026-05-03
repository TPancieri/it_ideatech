<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DemoScenarioService;
use Illuminate\Console\Command;

class SeedDemoScenarioCommand extends Command
{
    protected $signature = 'demo:seed-scenario
                            {--user= : E-mail do utilizador responsável pelos processos demo}
                            {--no-purge : Não apagar dados demo anteriores deste utilizador (pode duplicar títulos)}';

    protected $description = 'Cria massa de dados demo (processos [Demo] ..., signatários demo-seed-...) para o utilizador indicado.';

    public function handle(DemoScenarioService $demo): int
    {
        $email = $this->option('user');
        $user = $email
            ? User::query()->where('email', $email)->first()
            : User::query()->orderBy('id')->first();

        if (! $user) {
            $this->error('Nenhum utilizador encontrado. Use --user=email@exemplo.com ou crie um utilizador na web.');

            return self::FAILURE;
        }

        $purge = ! $this->option('no-purge');
        if ($purge) {
            $removed = $demo->purgeForUser($user);
            $this->info('Removidos registos demo anteriores (linhas afectadas, processos+clientes): '.$removed);
        }

        $counts = $demo->seed($user, purgeFirst: false);
        $this->info('Criados: '.$counts['clientes'].' signatários demo, '.$counts['processos'].' processos demo para '.$user->email.'.');

        return self::SUCCESS;
    }
}
