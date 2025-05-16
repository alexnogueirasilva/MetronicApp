<?php declare(strict_types = 1);

namespace App\Support;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class SocialiteHelper
{
    /**
     * Retorna o driver com tipagem correta para acesso a métodos como stateless().
     */
    public static function getTypedDriver(string $provider): AbstractProvider
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver;
    }
}
