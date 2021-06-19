<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */
declare (strict_types=1);

namespace Larva\Passport\Socialite;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

/**
 * Passport Socialite Grant Provider
 * @author Tongle Xu <xutongle@gmail.com>
 */
class PassportSocialiteGrantProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->afterResolving(AuthorizationServer::class, function (AuthorizationServer $oauthServer) {
            $oauthServer->enableGrantType($this->makeSocialGrant(), Passport::tokensExpireIn());
        });
    }

    /**
     * Create and configure Social Grant
     *
     * @return SocialGrant
     * @throws BindingResolutionException
     */
    public function makeSocialGrant(): SocialGrant
    {
        $grant = new SocialGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );
        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        return $grant;
    }
}
