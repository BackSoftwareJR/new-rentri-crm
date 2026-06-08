<?php

namespace Tests\Feature\Sprint120;

use App\Http\Livewire\Operatore\Profilo;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Push\WebPushService;
use Livewire\Livewire;
use Tests\TestCase;

class WebPushPhase1Test extends TestCase
{
    public function test_web_push_service_generates_vapid_keys(): void
    {
        $keys = app(WebPushService::class)->generateVapidKeys();

        $this->assertArrayHasKey('publicKey', $keys);
        $this->assertArrayHasKey('privateKey', $keys);
        $this->assertNotEmpty($keys['publicKey']);
        $this->assertNotEmpty($keys['privateKey']);
    }

    public function test_subscribe_persists_push_subscription(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $payload = json_encode([
            'endpoint' => 'https://push.example.com/sub/abc123',
            'keys'     => [
                'auth'   => 'auth-secret',
                'p256dh' => 'p256dh-key',
            ],
        ], JSON_THROW_ON_ERROR);

        Livewire::actingAs($operatore)
            ->test(Profilo::class)
            ->call('subscribePush', $payload, 'Test Device')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id'     => $operatore->id,
            'endpoint'    => 'https://push.example.com/sub/abc123',
            'device_name' => 'Test Device',
        ]);
    }

    public function test_send_is_noop_without_vapid_keys(): void
    {
        config([
            'webpush.vapid.public_key'  => null,
            'webpush.vapid.private_key' => null,
        ]);

        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        PushSubscription::create([
            'user_id'    => $user->id,
            'endpoint'   => 'https://push.example.com/sub/xyz',
            'auth_key'   => 'a',
            'p256dh_key' => 'b',
        ]);

        app(WebPushService::class)->send($user, 'Test', 'Body');

        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $user->id]);
    }
}
