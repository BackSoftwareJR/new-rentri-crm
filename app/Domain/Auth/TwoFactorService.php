<?php

namespace App\Domain\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(
        private readonly Google2FA $google2fa,
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function verifyUser(User $user, string $code): bool
    {
        $secret = $user->twoFactorSecretPlain();

        if ($secret === null) {
            return false;
        }

        return $this->verifySecret($secret, $code);
    }

    public function verifySecret(string $secret, string $code): bool
    {
        $normalized = preg_replace('/\s+/', '', $code) ?? '';

        if ($normalized === '' || strlen($normalized) !== 6) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $normalized);
    }

    public function currentOtp(string $secret): string
    {
        return $this->google2fa->getCurrentOtp($secret);
    }

    /**
     * Enable 2FA for the user, generating and storing fresh recovery codes.
     *
     * @return list<string> Plaintext recovery codes (shown once to the user)
     */
    public function enable(User $user, string $secret): array
    {
        $plainCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret'         => encrypt($secret),
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($plainCodes),
        ])->save();

        return $plainCodes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /**
     * Regenerate recovery codes, invalidating all previous ones.
     *
     * @return list<string> New plaintext recovery codes (shown once to the user)
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $plainCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($plainCodes),
        ])->save();

        Log::channel('security')->info('2FA recovery codes regenerated', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return $plainCodes;
    }

    /**
     * Attempt to consume a recovery code. Returns true on match (code is then nulled).
     */
    public function useRecoveryCode(User $user, string $inputCode): bool
    {
        $normalized = strtoupper(trim(preg_replace('/\s+/', '', $inputCode) ?? ''));

        if ($normalized === '') {
            return false;
        }

        $storedCodes = $user->two_factor_recovery_codes ?? [];

        foreach ($storedCodes as $index => $hashedCode) {
            if ($hashedCode === null) {
                continue;
            }

            if (password_verify($normalized, $hashedCode)) {
                $storedCodes[$index] = null;

                $user->forceFill([
                    'two_factor_recovery_codes' => $storedCodes,
                ])->save();

                Log::channel('security')->warning('2FA recovery code used', [
                    'user_id'      => $user->id,
                    'email'        => $user->email,
                    'code_index'   => $index,
                    'remaining'    => count(array_filter($storedCodes)),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Returns how many recovery codes are still available (not yet used).
     */
    public function remainingRecoveryCodesCount(User $user): int
    {
        return count(array_filter($user->two_factor_recovery_codes ?? []));
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $otpUrl = $this->google2fa->getQRCodeUrl(
            (string) config('two-factor.issuer', 'ERP VFU'),
            $user->email,
            $secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(192),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($otpUrl);
    }

    // -------------------------------------------------------------------------

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = $this->randomRecoveryCode();
        }

        return $codes;
    }

    private function randomRecoveryCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segment = fn () => implode('', array_map(fn () => $chars[random_int(0, strlen($chars) - 1)], range(1, 4)));

        return $segment() . '-' . $segment() . '-' . $segment();
    }

    /**
     * @param  list<string>  $plainCodes
     * @return list<string>
     */
    private function hashRecoveryCodes(array $plainCodes): array
    {
        return array_map(fn (string $code) => password_hash($code, PASSWORD_BCRYPT), $plainCodes);
    }
}
