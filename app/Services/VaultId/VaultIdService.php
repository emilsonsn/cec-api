<?php

namespace App\Services\VaultId;

use App\Trait\VaultIDTrait;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VaultIdService
{

    use VaultIDTrait;

    public function getCertificates($request)
    {
        try{
            $cpf_cnpj = Auth::user()->cpf_cnpj;
            $code_otp = $request->code_otp;
            
            if(!isset($code_otp)) throw new Exception('Código OTP é obrigatório');

            $auth = $this->authenticate($cpf_cnpj, $code_otp);
    
            if(!isset($auth['access_token'])) throw new Exception('Autenticação falhou');
    
            $responseCertificates = $this->getUserCertificates($auth['access_token']);
    
            if(!isset($responseCertificates['certificates'])){
                $error = json_encode($responseCertificates);
                Log::error($error);
                throw new Exception('Não foi possível buscar certificados');
            }
    
            return [
                'status' => true,
                'data' => [
                    'certificates' => $responseCertificates['certificates'],
                    'access_token' => $auth['access_token']
                ]
            ];

        } catch(Exception $error){
            return ['status' => false, 'error' => 'Não foi possível obter seus certificados.', 'statusCode' => 400];
        }
    }
}
