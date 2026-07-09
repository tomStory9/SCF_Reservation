<?php

namespace App\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;

class Line extends AbstractProvider
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
        return $this->baseUrl . '/v2/profile';
    }


    protected function getDefaultScopes(): array
    {
        return [
            'openid',
            'profile',
            'email'
        ];
    }



    protected function checkResponse(
        \Psr\Http\Message\ResponseInterface $response,
        $data
    ): void
    {

        if ($response->getStatusCode() >= 400) {

            throw new \Exception(
                'LINE API error : ' . $response->getBody()
            );

        }

    }



    protected function createResourceOwner(
        array $response,
        AccessToken $token
    ): ResourceOwnerInterface
    {

        return new LineResourceOwner($response);

    }


}