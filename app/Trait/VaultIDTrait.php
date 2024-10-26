<?php

namespace App\Trait;

use App\Models\Order;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

Trait GranatumTrait
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function authenticate()
    {
        $response = $this->client->post('https://api.birdid.com.br/v0/oauth/authorize', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => env('VAULTID_CLIENT_ID'),
                'client_secret' => env('VAULTID_CLIENT_SECRET'),
            ],
        ]);

        return json_decode($response->getBody(), true)['access_token'];
    }

    public function signDocument($documentPath)
    {
        $token = $this->authenticate();

        $response = $this->client->post('https://api.vaultid.com.br/assinatura-digital', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => fopen($documentPath, 'r'),
                ],
                [
                    'name'     => 'certificado_id',
                    'contents' => env('VAULTID_CERTIFICATE_ID'),
                ],
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function checkSignatureStatus($signatureId)
    {
        $token = $this->authenticate();

        $response = $this->client->get("https://api.vaultid.com.br/assinatura-digital/{$signatureId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }



}
