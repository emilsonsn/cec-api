<?php

namespace App\Http\Controllers;

use App\Services\Setting\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private $settingService;

    public function __construct(SettingService $settingService) {
        $this->settingService = $settingService;
    }

    public function search(Request $request){
        $result = $this->settingService->getSetting($request);

        return $result;
    }

    public function update(Request $request){

        $result = $this->settingService->update($request);

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
