<?php

namespace App\Http\Controllers;

use App\Services\VaultId\VaultIdService;
use Illuminate\Http\Request;

class VaultIdController extends Controller
{

    private $vaultIdService;

    public function __construct(VaultIdService $vaultIdService) {
        $this->vaultIdService = $vaultIdService;
    }

    
    public function getCertificates(Request $request) {
        $result = $this->vaultIdService->getCertificates($request);

        if($result['status']) $result['message'] = "Configuração Atualizada com sucesso";
        return $this->response($result);
    }

    private function response($result){
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null
        ], $result['statusCode'] ?? 200);
    }
}
