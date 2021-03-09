<?php
/**
 * User social account interface.
 *
 * @author      Anand Siddharth <anandsiddharth21@gmail.com>
 * @copyright   Copyright (c) Anand Siddharth
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/schedula/laravel-passport-socialite
 */
namespace Larva\Passport\Socialite\User;

use Laravel\Socialite\Contracts\User;

interface UserSocialAccount {
    /**
     * Get user from social provider and from provider's user's id
     * 
     * @param string $provider Provider name as requested from oauth e.g. facebook
     * @param User $socialUser user by provider
     * @param bool $autoRegistration
     */
    public static function findForPassportSocialite($provider,User $socialUser, $autoRegistration = true);
}
