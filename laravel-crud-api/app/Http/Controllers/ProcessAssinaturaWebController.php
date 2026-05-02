<?php

namespace App\Http\Controllers;

use App\Services\ApprovalFlowService;
use App\Services\ProcessSigningTokenService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\View\View;

class ProcessAssinaturaWebController extends Controller
{
    public function show(string $token, ProcessSigningTokenService $tokens): View
    {
        $record = $tokens->findValid($token);

        return view('assinatura.show', [
            'token' => $token,
            'valid' => (bool) $record,
            'processo' => $record?->processo,
            'cliente' => $record?->cliente,
        ]);
    }

    public function approve(string $token, Request $request, ApprovalFlowService $flow)
    {
        try {
            $flow->approve($token, $request);
        } catch (HttpException $e) {
            return redirect()
                ->route('assinatura.show', ['token' => $token])
                ->withErrors(['fluxo' => $e->getMessage()]);
        }

        return redirect()
            ->route('assinatura.show', ['token' => $token])
            ->with('status', 'Resposta registrada: aprovação.');
    }

    public function reject(Request $request, string $token, ApprovalFlowService $flow)
    {
        $data = $request->validate([
            'justificativa' => 'required|string|min:3',
        ]);

        try {
            $flow->reject($token, $data['justificativa'], $request);
        } catch (HttpException $e) {
            return redirect()
                ->route('assinatura.show', ['token' => $token])
                ->withErrors(['fluxo' => $e->getMessage()]);
        }

        return redirect()
            ->route('assinatura.show', ['token' => $token])
            ->with('status', 'Resposta registrada: reprovação.');
    }
}
