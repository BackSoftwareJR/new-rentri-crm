<?php

namespace App\Domain\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
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

    public function enable(User $user, string $secret): void
    {
        $user->forceFill([
            'two_factor_secret'       => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'       => null,
            'two_factor_confirmed_at' => null,
        ])->save();
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
}
