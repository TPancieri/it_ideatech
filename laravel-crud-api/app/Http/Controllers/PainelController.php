<?php

namespace App\Http\Controllers;

use App\Services\DemoScenarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function index(): View
    {
        return view('painel.index');
    }

    public function seedDemo(Request $request, DemoScenarioService $demo): RedirectResponse
    {
        abort_unless(in_array(app()->environment(), ['local', 'testing'], true), 403);

        $user = $request->user();
        $demo->purgeForUser($user);
        $counts = $demo->seed($user, purgeFirst: false);

        return redirect()
            ->route('painel')
            ->with(
                'success',
                'Massa demo criada: '.$counts['processos'].' processos (título «'.DemoScenarioService::TITLE_PREFIX.'…») e '.$counts['clientes'].' signatários de teste.'
            );
    }
}
