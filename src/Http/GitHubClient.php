<?php
declare(strict_types=1);

namespace App\Http;

use App\Http\Client;
use Cake\I18n\DateTime;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

class GitHubClient
{
    /**
     * GitHub API base URL
     */
    private const API_BASE_URL = 'https://api.github.com';

    /**
     * GitHub API version
     */
    private const API_VERSION = '2022-11-28';

    /**
     * JWT expiration time in seconds
     */
    private const JWT_EXPIRATION_TIME = 500; // 8 minutes

    private Client $httpClient;
    private ?string $installationToken = null;
    private ?DateTime $tokenExpiration = null;

    // GitHub App credentials
    private string $appId;
    private string $privateKey;
    private ?string $installationId = null;

    public function __construct()
    {
        $this->httpClient = new Client();

        $this->appId = env('GITHUB_APP_ID');
        $this->installationId = env('GITHUB_APP_INSTALLATION_ID');
        $this->privateKey = file_get_contents(CONFIG . 'github-app-private-key.pem');
    }

    /**
     * Generate a JWT token for GitHub App authentication
     * 
     * According to GitHub docs, the JWT must:
     * - Be signed using RS256 algorithm
     * - Have iat (issued at) set to 60 seconds in the past
     * - Have exp (expiration) no more than 10 minutes in the future
     * - Have iss (issuer) set to the GitHub App's ID
     *
     * @return string JWT token
     * @see https://docs.github.com/en/apps/creating-github-apps/authenticating-with-a-github-app/generating-a-json-web-token-jwt-for-a-github-app
     */
    private function generateJWT(): string
    {
        $config = Configuration::forSymmetricSigner(
            new Sha256(), // RS256 algorithm
            InMemory::plainText($this->privateKey)
        );

        $now = new \DateTimeImmutable();
        // GitHub recommends setting iat 60 seconds in the past to allow for clock drift
        $issuedAt = $now->modify('-60 seconds');
        // JWT expiration time (10 minute maximum)
        $expiresAt = $now->modify('+' . self::JWT_EXPIRATION_TIME . ' seconds');

        $token = $config->builder()
            ->issuedBy($this->appId)  // GitHub App's ID
            ->issuedAt($issuedAt)      // iat claim - 60 seconds in the past
            ->expiresAt($expiresAt)    // exp claim - 10 minutes in the future
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    /**
     * Get installation access token for the GitHub App
     *
     * @param string|null $installationId Optional installation ID (uses env variable if not provided)
     * @return string Installation access token
     * @throws \Exception If unable to get installation token
     */
    private function getInstallationToken(?string $installationId = null): string
    {
        $installationId = $installationId ?? $this->installationId;
        
        if (!$installationId) {
            throw new \Exception('GitHub App installation ID not provided');
        }

        // Check if we have a valid cached token
        if ($this->installationToken && $this->tokenExpiration && $this->tokenExpiration->isFuture()) {
            return $this->installationToken;
        }

        $jwt = $this->generateJWT();
        $url = sprintf('%s/app/installations/%s/access_tokens', self::API_BASE_URL, $installationId);

        $headers = [
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer ' . $jwt,
            'X-GitHub-Api-Version' => self::API_VERSION,
        ];

        $response = $this->httpClient->post($url, '{}', [
            'headers' => $headers,
            'type' => 'json',
        ]);

        if (!$response->isSuccess()) {
            $errorMessage = 'Failed to get installation token: ' . $response->getStatusCode();
            $body = $response->getJson();
            if (isset($body['message'])) {
                $errorMessage .= ' - ' . $body['message'];
            }
            throw new \Exception($errorMessage);
        }

        $data = $response->getJson();
        $this->installationToken = $data['token'];
        $this->tokenExpiration = new DateTime($data['expires_at']);

        return $this->installationToken;
    }

    /**
     * Get authenticated headers for API requests
     *
     * @param string|null $installationId Optional installation ID
     * @return array Headers array
     * @throws \Exception If unable to get installation token
     */
    private function getAuthHeaders(?string $installationId = null): array
    {
        $token = $this->getInstallationToken($installationId);

        return [
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer ' . $token,
            'X-GitHub-Api-Version' => self::API_VERSION,
        ];
    }

    /**
     * Create an issue in a GitHub repository
     *
     * @param string $owner Repository owner (username or organization)
     * @param string $repo Repository name
     * @param array $issueData Issue data containing at minimum 'title' field
     * @param string|null $installationId Optional installation ID
     * @return array The created issue data
     * @throws \Exception If the API request fails
     */
    public function createIssue(string $owner, string $repo, array $issueData, ?string $installationId = null): array
    {
        $url = sprintf('%s/repos/%s/%s/issues', self::API_BASE_URL, $owner, $repo);

        $response = $this->httpClient->post($url, json_encode($issueData), [
            'headers' => $this->getAuthHeaders($installationId),
            'type' => 'json',
        ]);

        if (!$response->isSuccess()) {
            $errorMessage = 'Failed to create issue: ' . $response->getStatusCode();
            $body = $response->getJson();
            if (isset($body['message'])) {
                $errorMessage .= ' - ' . $body['message'];
            }
            throw new \Exception($errorMessage);
        }

        return $response->getJson();
    }

    /**
     * Create an issue from a JSON string
     *
     * @param string $owner Repository owner (username or organization)
     * @param string $repo Repository name
     * @param string $jsonData JSON string containing issue data
     * @param string|null $installationId Optional installation ID
     * @return array The created issue data
     * @throws \Exception If JSON is invalid or API request fails
     */
    public function createIssueFromJson(string $owner, string $repo, string $jsonData, ?string $installationId = null): array
    {
        $issueData = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: ' . json_last_error_msg());
        }

        if (!isset($issueData['title'])) {
            throw new \Exception('Issue title is required');
        }

        return $this->createIssue($owner, $repo, $issueData, $installationId);
    }
}