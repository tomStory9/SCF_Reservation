<?php

namespace App\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class LineResourceOwner implements ResourceOwnerInterface
{
    public function __construct(
        private array $response
    ) {
    }

    public function getId(): ?string
    {
        return $this->response['userId'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->response['displayName'] ?? null;
    }

    public function getPicture(): ?string
    {
        return $this->response['pictureUrl'] ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->response['email'] ?? null;
    }

    public function toArray(): array
    {
        return $this->response;
    }
}
