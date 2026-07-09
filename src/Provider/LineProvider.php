<?php

namespace App\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class LineProvider extends AbstractProvider
{
    protected string $baseUrl = 'https://api.line.me';

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://access.line.me/oauth2/v2.1/authorize';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://api.line.me/oauth2/v2.1/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://api.line.me/v2/profile';
    }

    protected function getDefaultScopes(): array
    {
        return [
            'profile',
            'openid',
            'email',
        ];
    }

    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer %s',
            'Accept' => 'application/json',
        ];
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : (string) $response->getBody(),
                $response->getStatusCode(),
                is_array($data) ? $data : []
            );
        }

        if (is_array($data) && isset($data['error'])) {
            throw new IdentityProviderException(
                $data['error_description'] ?? $data['error'],
                $response->getStatusCode(),
                $data
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new LineResourceOwner($response);
    }

    protected function getAuthorizationParameters(array $options): array
    {
        $options = parent::getAuthorizationParameters($options);

        if (isset($options['scope']) && is_array($options['scope'])) {
            $options['scope'] = implode(' ', $options['scope']);
        }

        return $options;
    }
}