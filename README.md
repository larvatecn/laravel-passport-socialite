# laravel-passport-socialite

The missing social authentication plugin (i.e. SocialGrant) for laravel passport.

# Laravel Passport Socialite
The missing social authentication plugin (i.e. SocialGrant) for laravel passport.

## Description
This package helps integrate social login using laravel's native packages i.e. (passport and socialite). This package allows social login from the providers that is supported in laravel/socialite package.

## Getting Started
To get started add the following package to your composer.json file using this command.

`composer require larva/laravel-passport-socialite -vv`

## Configuration
When composer installs this package successfully, register the   `Larva\Passport\Socialite\PassportSocialiteGrantProvider::class` in your `config/app.php` configuration file.

```php
'providers' => [
    // Other service providers...
    Larva\Passport\Socialite\PassportSocialiteGrantProvider::class,
],
```

**Note: You need to configure third party social provider keys and secret strings as mentioned in laravel socialite documentation https://laravel.com/docs/5.6/socialite#configuration**

## 使用

### S设置你的 User 模型

添加 `findAndValidateForPassportSocialite` 方法到你的 `User` 模型，
`findAndValidateForPassportSocialite` 方法接受两个参数 `$provider` and `$socialUser`。

**$provider - string - 你的社交账户提供商。如： facebook, google。**

**$socialUser - \Larva\Socialite\Contracts\User - 社交服务商获取到的用户实例**

**And the function should find the user which is related to that information and return user object or return null if not found**

Below is how your `User` model should look like after above implementations.

```php
namespace App\Models;

class User extends Authenticatable {
    
    use HasApiTokens, Notifiable;

    /**
    * Find user using social provider's user
    * 
    * @param string $provider Provider name as requested from oauth e.g. facebook
    * @param \Larva\Socialite\Contracts\User $socialUser User of social provider
    *
    * @return User|void
    */
    public static function findAndValidateForPassportSocialiteRequest(string $provider, \Larva\Socialite\Contracts\User $socialUser) {
        if( $socialUser->user) {
            return $socialUser->user;
        }
        
        // 你其他代码，例如自动注册用户 如果你绑定了用户 \Larva\Socialite\Contracts\User 里面有你绑定的用户模型实例
        return;
    }
}
```
