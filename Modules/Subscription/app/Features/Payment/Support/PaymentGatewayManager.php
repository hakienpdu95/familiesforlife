<?php

namespace Modules\Subscription\Features\Payment\Support;

use InvalidArgumentException;
use Modules\Subscription\Features\Payment\Contracts\PaymentGatewayInterface;

/**
 * Registry for payment gateways. Bound as a singleton in the service container.
 *
 * To add a new gateway:
 *   1. Implement PaymentGatewayInterface.
 *   2. Call $manager->register(new YourGateway()) in SubscriptionServiceProvider.
 *   3. Add gateway config under subscription.gateways.{slug}.
 *
 * No other files need to change — controllers and webhook handler resolve
 * gateways by slug through this manager.
 */
final class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->slug()] = $gateway;
    }

    /**
     * @throws InvalidArgumentException for unknown gateway slugs
     */
    public function resolve(string $slug): PaymentGatewayInterface
    {
        return $this->gateways[$slug]
            ?? throw new InvalidArgumentException("Unknown payment gateway: [{$slug}]");
    }

    public function default(): PaymentGatewayInterface
    {
        return $this->resolve(config('subscription.gateways.default', 'manual'));
    }

    /** Returns only gateways that are currently configured and enabled. */
    public function enabled(): array
    {
        return array_filter($this->gateways, fn ($g) => $g->isEnabled());
    }

    public function all(): array
    {
        return $this->gateways;
    }

    public function has(string $slug): bool
    {
        return isset($this->gateways[$slug]);
    }
}
