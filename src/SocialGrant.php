<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Passport\Socialite;

use DateInterval;
use Exception;
use InvalidArgumentException;
use Laravel\Passport\Bridge\User;
use Larva\Socialite\Facades\Socialite;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class SocialGrant extends AbstractGrant
{
    /**
     * SocialGrant constructor.
     * @param UserRepositoryInterface $userRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(UserRepositoryInterface $userRepository, RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->setUserRepository($userRepository);
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    /**
     * 获取标识
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'social';
    }

    /**
     * Respond to an incoming request.
     * @param ServerRequestInterface $request
     * @param ResponseTypeInterface $responseType
     * @param DateInterval $accessTokenTTL
     * @return ResponseTypeInterface
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     * @throws \League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException
     */
    public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, DateInterval $accessTokenTTL): ResponseTypeInterface
    {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));
        $user = $this->validateUser($request, $client);

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new tokens
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $finalizedScopes);
        $refreshToken = $this->issueRefreshToken($accessToken);

        // Inject tokens into response
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);

        return $responseType;
    }

    /**
     * 验证用户
     * @param ServerRequestInterface $request
     * @param ClientEntityInterface $client
     * @return UserEntityInterface
     * @throws OAuthServerException
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client): UserEntityInterface
    {
        $provider = $this->getRequestParameter('provider', $request);
        if (is_null($provider)) {
            throw OAuthServerException::invalidRequest('provider');
        }
        $authorizationCode = $this->getRequestParameter('code', $request);
        if (is_null($authorizationCode)) {
            throw OAuthServerException::invalidRequest('code');
        }
        $user = $this->getUserEntityBySocialProvider($authorizationCode, $provider, $this->getIdentifier(), $client);
        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));
            throw OAuthServerException::invalidCredentials();
        }
        return $user;
    }

    /**
     * 获取用户
     * @param string $authorizationCode
     * @param string $socialProvider
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     * @return User|null
     * @throws OAuthServerException
     */
    protected function getUserEntityBySocialProvider(string $authorizationCode, string $socialProvider, string $grantType, ClientEntityInterface $clientEntity): ?User
    {
        $provider = config('auth.guards.api.provider');
        if (is_null($model = config('auth.providers.' . $provider . '.model'))) {
            throw OAuthServerException::serverError('Unable to determine authentication model from configuration.');
        }
        try {
            $socialUser = Socialite::with($socialProvider)->stateless()->user();
            if (method_exists($model, 'findAndValidateForPassportSocialite')) {
                $user = (new $model)->findAndValidateForPassportSocialite($socialProvider, $socialUser);
                if (!$user) {
                    return null;
                }
                return new User($user->getAuthIdentifier());
            } else {
                throw OAuthServerException::serverError('Unable to find findAndValidateForPassportSmsRequest method on user model.');
            }
        } catch (InvalidArgumentException $e) {
            throw OAuthServerException::invalidRequest('provider');
        } catch (Throwable | Exception $e) {
            throw OAuthServerException::serverError($e->getMessage());
        }
    }
}