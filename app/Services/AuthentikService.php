<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthentikService
{
    protected Client $client;
    protected string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $scope;
    protected string $apiToken;

    public function __construct()
    {
        $this->baseUrl = config('services.authentik.base_url');
        $this->clientId = config('services.authentik.client_id');
        $this->clientSecret = config('services.authentik.client_secret');
        $this->scope = config('services.authentik.scope', 'openid profile email offline_access');
        $this->apiToken = config('services.authentik.api_token');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
            'verify' => false, // Untuk development, di production set true
        ]);
    }

    public function createUser(string $name, string $email, string $password, int $tenantId): array
    {
        try {
            $response = $this->client->post('/api/v3/core/users/', [
                'json' => [
                    'username' => $email,
                    'name' => $name,
                    'email' => $email,
                    'is_active' => true,
                    'attributes' => [
                        'tenant_id' => (string) $tenantId,
                    ],
                    'type' => 'internal',
                ],
            ]);

            $userData = json_decode($response->getBody()->getContents(), true);

            if (isset($userData['pk'])) {
                $this->setUserPassword($userData['pk'], $password);
            }

            return $userData;
        } catch (GuzzleException $e) {
            Log::error('Error creating Goauthentik user', [
                'message' => $e->getMessage(),
                'email' => $email,
            ]);
            throw new \Exception('Failed to create user in Goauthentik: ' . $e->getMessage());
        }
    }

    protected function setUserPassword(int $userPk, string $password): void
    {
        try {
            $this->client->post("/api/v3/core/users/{$userPk}/set_password/", [
                'json' => [
                    'password' => $password,
                ],
            ]);
        } catch (GuzzleException $e) {
            Log::error('Error setting Goauthentik user password', [
                'message' => $e->getMessage(),
                'user_pk' => $userPk,
            ]);
            throw new \Exception('Failed to set user password');
        }
    }

    public function updateUserAttributes(int $userPk, array $attributes): array
    {
        try {
            $response = $this->client->patch("/api/v3/core/users/{$userPk}/", [
                'json' => [
                    'attributes' => $attributes,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Error updating Goauthentik user', [
                'message' => $e->getMessage(),
                'user_pk' => $userPk,
            ]);
            throw new \Exception('Failed to update user attributes');
        }
    }

    public function getUserByEmail(string $email): ?array
    {
        try {
            $response = $this->client->get('/api/v3/core/users/', [
                'query' => [
                    'email' => $email,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['results'][0] ?? null;
        } catch (GuzzleException $e) {
            Log::error('Error getting Goauthentik user', [
                'message' => $e->getMessage(),
                'email' => $email,
            ]);
            return null;
        }
    }

    public function deleteUser(int $userPk): bool
    {
        try {
            $this->client->delete("/api/v3/core/users/{$userPk}/");
            return true;
        } catch (GuzzleException $e) {
            Log::error('Error deleting Goauthentik user', [
                'message' => $e->getMessage(),
                'user_pk' => $userPk,
            ]);
            return false;
        }
    }

    public function loginWithPassword(string $email, string $password): array
    {
        $tokenUrl = $this->baseUrl . '/application/o/token/';

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => $this->scope,
        ]);

        Log::info('Authentik Token Request', ['url' => $tokenUrl, 'data' => $response->json()]);  // Log untuk debug

        if ($response->successful()) {
            return $response->json();
        }

        $errorBody = $response->json();
        $errorMsg = $errorBody['error_description'] ?? $errorBody['error'] ?? 'Unknown error from Authentik';
        Log::error('Authentik Token Error', ['status' => $response->status(), 'body' => $errorBody]);

        throw new \Exception($errorMsg);
    }

    public function getUserInfo(string $accessToken): array
    {
        $userinfoUrl = $this->baseUrl . '/application/o/userinfo/';

        $response = Http::withToken($accessToken)->get($userinfoUrl);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Gagal fetch user info');
    }
}
