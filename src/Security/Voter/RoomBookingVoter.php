<?php

namespace App\Security\Voter;

use App\Repository\SettingsRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RoomBookingVoter extends Voter
{
    public const string FEATURE_ROOM_BOOKING = 'FEATURE_ROOM_BOOKING';

    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly CacheInterface $cache
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::FEATURE_ROOM_BOOKING === $attribute;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $this->cache->get('settings_room_booking', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            $settings = $this->settingsRepository->getSettings();

            return $settings->isRoomBookingEnabled();
        });
    }
}
