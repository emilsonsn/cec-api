<?php

namespace App\Services\VaultId;

use App\Trait\VaultIDTrait;
use Exception;


class VaultIdService
{

    use VaultIDTrait;

    public function getCertificates($request)
    {
        try{
            $cpf_cnpj = $request->cpf_cnpj;
            $code_otp = $request->code_otp;
            
            if(!isset($cpf_cnpj)) throw new Exception('Campo cpf/cnpj é obrigatório');

            if(!isset($code_otp)) throw new Exception('Código OTP é obrigatório');

            $auth = $this->authenticate($cpf_cnpj, $code_otp);
    
            if(!isset($auth['access_token'])) throw new Exception('Autenticação falhou');
    
            $responseCertificates = $this->getUserCertificates($auth['access_token']);
    
            if(!isset($responseCertificates['certificates'])) throw new Exception('Não foi possível buscar certificados');
    
            return [
                'status' => true,
                'data' => [
                    'certificates' => $responseCertificates['certificates'],
                    'access_token' => $auth['access_token']
                ]
            ];

        } catch(Exception $error){
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
}
