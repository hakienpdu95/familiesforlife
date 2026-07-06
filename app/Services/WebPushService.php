<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private function client(): WebPush
    {
        return new WebPush(
            auth: [
                'VAPID' => [
                    'subject'    => config('webpush.vapid.subject'),
                    'publicKey'  => config('webpush.vapid.public_key'),
                    'privateKey' => config('webpush.vapid.private_key'),
                ],
            ],
            defaultOptions: [
                'TTL'              => 86400, // 24h
                'urgency'          => 'normal',
                'batchSize'        => config('webpush.batch_size', 200),
            ],
            timeout: config('webpush.timeout', 30),
        );
    }

    /**
     * Queue a push notification to ALL subscriptions of a user, then flush.
     * Expired subscriptions are automatically pruned from the database.
     */
    public function sendToUser(User $user, array $payload): void
    {
        $subscriptions = $user->pushSubscriptions;
        if ($subscriptions->isEmpty()) return;

        $push    = $this->client();
        $json    = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expired = [];

        foreach ($subscriptions as $sub) {
            $push->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'publicKey'       => $sub->public_key,
                    'authToken'       => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
                ]),
                $json,
            );
        }

        foreach ($push->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $expired[] = $report->getEndpoint();
            }
        }

        if ($expired) {
            PushSubscription::whereIn('endpoint', $expired)->delete();
        }
    }

    /**
     * Send to multiple users efficiently — queues all subscriptions in one WebPush
     * instance so Guzzle Pool handles concurrency across users.
     *
     * @param  iterable<User>  $users
     */
    public function sendToUsers(iterable $users, array $payload): void
    {
        $push    = $this->client();
        $json    = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expired = [];

        foreach ($users as $user) {
            foreach ($user->pushSubscriptions as $sub) {
                $push->queueNotification(
                    Subscription::create([
                        'endpoint'        => $sub->endpoint,
                        'publicKey'       => $sub->public_key,
                        'authToken'       => $sub->auth_token,
                        'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
                    ]),
                    $json,
                );
            }
        }

        foreach ($push->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $expired[] = $report->getEndpoint();
            }
        }

        if ($expired) {
            PushSubscription::whereIn('endpoint', $expired)->delete();
        }
    }

    public function isConfigured(): bool
    {
        return !empty(config('webpush.vapid.public_key'))
            && !empty(config('webpush.vapid.private_key'));
    }
}
