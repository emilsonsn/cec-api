<?php

namespace App\Trait;

use GuzzleHttp\Client;

Trait VaultIDTrait
{
    protected $apiKey;
    protected $baseUrl = 'https://api.birdid.com.br/v0';

    public function __construct()
    {
        $this->client = new Client();
    }

    public function authenticate($cpf_cnpj, $code_otp)
    {

        $url = $this->baseUrl . '/oauth/pwd_authorize';
        $clientId = env('VAULTID_CLIENT_ID');
        $clientSecret = env('VAULTID_CLIENT_SECRET');

        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $cpf_cnpj,
            'password' => $code_otp,
            'grant_type' => 'password',
            'scope' => 'single_signature',
            'lifetime' => 86400
        ];

        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $data
        ]);
    
        return json_decode($response->getBody(), true);
    }

    public function getUserCertificates($accessToken)
    {
        $url = $this->baseUrl . '/oauth/certificate-discovery';
        
        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    public function signDocument($accessToken, $certificateAlias, $hashes)
    {

        return [
            'signatures' => [
                [
                    "id" => 345,
                    "raw_signature" => "PZ/ocDj4bl8cvj9iQnaIWAdFRAcIjbrvsDxV23E4Ga4eKX+5FF0oLrcHMaTvgnyuMSvXMipOnu4k4NRv2fRTGGURru0s3mkrxSnUML0gbTvjQzji8g++2Y+0Cl7xUoPlBLq+BPyDEVJex9IpeVtHSUYy9lCQcceV073KUdmAtAIBLomr8F/esqsphH3Dkz+whspC8vy+AXIVa+AWxPgegK3gLwowFFVz3qM8ymg7fMLSIPEOWDgx7LsqL14txkfIFrDp38Xn+juTBZ2sSOvtZBTAl5/8S6bHUHkjXvqdB8EEYAo2Jh1Lh8UnXeaczrxIb3evKSxIntk8/shP7NBUAw=="
                ]
            ]
        ];

        // $url = $this->baseUrl . '/oauth/signature';
        // $response = $this->client->post($url, [
        //     'headers' => [
        //         'Authorization' => 'Bearer ' . $accessToken,
        //         'Content-Type' => 'application/json',
        //         'Accept' => 'application/json',
        //     ],
        //     'json' => [
        //         'certificate_alias' => trim($certificateAlias),
        //         'hashes' => $hashes,
        //         'include_chain' => true
        //     ]
        // ]);        

        // return json_decode($response->getBody(), true);
    }


}
