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
        $url = $this->baseUrl . '/oauth/signature';
        $response = $this->client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'certificate_alias' => trim($certificateAlias),
                'hashes' => $hashes,
                'include_chain' => true
            ]
        ]);        

        return json_decode($response->getBody(), true);
    }


}
