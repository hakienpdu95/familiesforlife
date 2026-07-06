<?php

namespace Modules\Auth\Actions\Auth;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialUser;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\SocialAccount;
use Modules\Auth\Rules\NotOrgDomainEmail;

class SocialLoginAction
{
    use AsAction;

    /**
     * @param  int|null  $authenticatedUserId  Auth::id() của request hiện tại.
     *                                          Null = user chưa đăng nhập (luồng login).
     *                                          Non-null = user đang link thêm social account.
     *                                          Dùng để abort TRƯỚC khi write DB nếu phát hiện conflict,
     *                                          tránh tạo ghost user.
     */
    public function handle(string $provider, SocialUser $socialUser, ?int $authenticatedUserId = null): SocialLoginResult
    {
        $this->assertProviderAllowed($provider);

        // 1. Tìm theo provider_user_id (stable ID từ provider)
        $social = SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $socialUser->getId())
            ->with('user')
            ->first();

        if ($social) {
            // Orphaned social account: FK cascade đáng lẽ xử lý, nhưng nếu
            // DB inconsistency khiến user row bị xóa mà social row còn lại,
            // xóa luôn record lỗi và fall-through để tạo user mới bình thường.
            if (! $social->user) {
                $social->delete();
            } else {
                // Guard: social account này thuộc user khác với người đang đăng nhập
                if ($authenticatedUserId !== null && $social->user_id !== $authenticatedUserId) {
                    throw ValidationException::withMessages([
                        'social' => 'Tài khoản ' . ucfirst($provider) . ' này đã được liên kết với tài khoản khác.',
                    ]);
                }

                $social->update([
                    'access_token'     => $socialUser->token,
                    'refresh_token'    => $socialUser->refreshToken,
                    'token_expires_at' => $socialUser->expiresIn
                        ? now()->addSeconds($socialUser->expiresIn) : null,
                    'last_used_at'     => now(),
                    'provider_name'    => $socialUser->getName(),
                    'provider_avatar'  => $socialUser->getAvatar(),
                ]);

                return new SocialLoginResult($social->user, isNewUser: false, isNewLink: false);
            }
        }

        // 2. Validate email từ provider
        $email = $this->resolveEmail($provider, $socialUser);

        // 3. Tìm user hiện có theo email → link thêm social account
        $user = User::where('email', $email)->first();

        if ($user) {
            // Guard: email này thuộc user khác với người đang đăng nhập
            if ($authenticatedUserId !== null && $user->id !== $authenticatedUserId) {
                throw ValidationException::withMessages([
                    'social' => 'Email này đã được liên kết với tài khoản khác.',
                ]);
            }

            // Guard (luồng login): không auto-link vào tài khoản chưa verify email.
            // Nếu local user chưa verify, social provider không đủ để chứng minh ownership
            // với hệ thống này — attacker có thể tạo social account trên email chưa được claim.
            // Không áp dụng khi user đã đăng nhập ($authenticatedUserId !== null) vì họ đã được xác thực.
            if ($authenticatedUserId === null && ! $user->email_verified_at) {
                throw ValidationException::withMessages([
                    'email' => 'Email này chưa được xác minh trong hệ thống. '
                             . 'Vui lòng đăng nhập bằng mật khẩu và xác minh email trước khi liên kết '
                             . 'tài khoản ' . ucfirst($provider) . '.',
                ]);
            }

            $this->linkSocialAccount($user, $provider, $socialUser, $email);

            // Nâng trust_level nếu user chưa verify email (chỉ xảy ra trong linking flow)
            if (! $user->email_verified_at) {
                $user->update([
                    'email_verified_at' => now(),
                    'trust_level'       => max($user->trust_level, 1),
                ]);
            }

            return new SocialLoginResult($user, isNewUser: false, isNewLink: true);
        }

        // 4. Tạo user mới — chỉ khi KHÔNG có user đang đăng nhập
        // Guard: nếu đang login mà tới đây nghĩa là email provider không khớp với
        // bất kỳ user nào → social account này không thuộc về user hiện tại
        if ($authenticatedUserId !== null) {
            throw ValidationException::withMessages([
                'social' => 'Email của tài khoản ' . ucfirst($provider) . ' không khớp với email tài khoản đang đăng nhập.',
            ]);
        }

        $newUser = $this->createUserFromSocial($provider, $socialUser, $email);

        return new SocialLoginResult($newUser, isNewUser: true, isNewLink: true);
    }

    private function assertProviderAllowed(string $provider): void
    {
        if (! array_key_exists($provider, config('services.social_providers', []))) {
            throw ValidationException::withMessages([
                'provider' => "Provider '{$provider}' chưa được hỗ trợ.",
            ]);
        }
    }

    private function resolveEmail(string $provider, SocialUser $socialUser): string
    {
        $email = $socialUser->getEmail();

        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản ' . ucfirst($provider) . ' không có địa chỉ email. '
                         . 'Vui lòng đăng nhập bằng email và mật khẩu.',
            ]);
        }

        $email = Str::lower($email);

        // LinkedIn thường bind email công việc → bắt buộc kiểm tra org domain
        if ($provider === 'linkedin') {
            $rule   = new NotOrgDomainEmail();
            $failed = null;
            $rule->validate('email', $email, function (string $msg) use (&$failed) {
                $failed = $msg;
            });

            if ($failed) {
                throw ValidationException::withMessages(['email' => $failed]);
            }
        }

        return $email;
    }

    private function linkSocialAccount(
        User $user,
        string $provider,
        SocialUser $socialUser,
        string $email,
    ): void {
        SocialAccount::create([
            'user_id'          => $user->id,
            'provider'         => $provider,
            'provider_user_id' => $socialUser->getId(),
            'provider_email'   => $email,
            'provider_name'    => $socialUser->getName(),
            'provider_avatar'  => $socialUser->getAvatar(),
            'access_token'     => $socialUser->token,
            'refresh_token'    => $socialUser->refreshToken,
            'token_expires_at' => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn) : null,
            'linked_at'        => now(),
            'last_used_at'     => now(),
        ]);
    }

    private function createUserFromSocial(
        string $provider,
        SocialUser $socialUser,
        string $email,
    ): User {
        return DB::transaction(function () use ($provider, $socialUser, $email) {
            $user = User::create([
                'name'              => $socialUser->getName() ?? Str::before($email, '@'),
                'email'             => $email,
                'password'          => null,
                'account_type'      => AccountType::Free,
                'trust_level'       => 1,
                'email_verified_at' => now(),
            ]);

            $this->linkSocialAccount($user, $provider, $socialUser, $email);

            return $user;
        });
    }
}
