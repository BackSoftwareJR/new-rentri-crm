<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    /** @return array{publicKey: string, privateKey: string} */
    public function generateVapidKeys(): array
    {
        return VAPID::createVapidKeys();
    }

    public function subscribe(User $user, array $subscriptionData): PushSubscription
    {
        $keys = $subscriptionData['keys'] ?? [];

        return PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $subscriptionData['endpoint'],
            ],
            [
                'auth_key' => $keys['auth'] ?? '',
                'p256dh_key' => $keys['p256dh'] ?? '',
                'device_name' => $subscriptionData['device_name'] ?? null,
            ],
        );
    }

    /**
     * @param  string|list<string>  $roles
     */
    public function sendToRoles(string|array $roles, string $title, string $body, string $url = ''): void
    {
        $roleList = is_array($roles) ? $roles : [$roles];

        foreach (User::role($roleList)->get() as $user) {
            try {
                $this->send($user, $title, $body, $url);
            } catch (\Throwable) {
                // Push failures must never break core workflow.
            }
        }
    }

    public function send(User $user, string $title, string $body, string $url = ''): void
    {
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');
        $subject = config('webpush.vapid.subject');

        if (blank($publicKey) || blank($privateKey)) {
            return;
        }

        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'tag' => 'rentri-push',
        ], JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $stored) {
            $subscription = Subscription::create([
                'endpoint' => $stored->endpoint,
                'keys' => [
                    'p256dh' => $stored->p256dh_key,
                    'auth' => $stored->auth_key,
                ],
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            $endpoint = $report->getEndpoint();

            if ($endpoint !== null) {
                PushSubscription::query()
                    ->where('user_id', $user->id)
                    ->where('endpoint', $endpoint)
                    ->delete();
            }
        }
    }

    public function publicKey(): ?string
    {
        $key = config('webpush.vapid.public_key');

        return filled($key) ? $key : null;
    }
}
