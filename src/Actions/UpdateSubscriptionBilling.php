<?php

declare(strict_types=1);

namespace Vatly\Fluent\Actions;

use Vatly\API\Types\Link;
use Vatly\API\VatlyApiClient;
use Vatly\Fluent\Contracts\ConfigurationInterface;

class UpdateSubscriptionBilling extends BaseAction
{
    public function __construct(
        VatlyApiClient $vatlyApiClient,
        /** @readonly */
        private ConfigurationInterface $config,
    ) {
        parent::__construct($vatlyApiClient);
    }

    /**
     * Create a signed URL where the customer can update the billing details for a subscription
     * (billing address, VAT number, company name) via a hosted flow.
     *
     * Missing `redirectUrlSuccess` / `redirectUrlCanceled` keys are filled in from
     * {@see ConfigurationInterface::getDefaultRedirectUrlSuccess()} and
     * {@see ConfigurationInterface::getDefaultRedirectUrlCanceled()}; caller-supplied
     * values always win.
     *
     * @param string $subscriptionId The subscription ID (e.g., subscription_xxx)
     * @param array<string, mixed> $prefillData May override `redirectUrlSuccess` /
     *                                          `redirectUrlCanceled`, and may include
     *                                          `billingAddress` as an optional prefill.
     */
    public function execute(string $subscriptionId, array $prefillData = []): Link
    {
        $payload = $prefillData + [
            'redirectUrlSuccess' => $this->config->getDefaultRedirectUrlSuccess(),
            'redirectUrlCanceled' => $this->config->getDefaultRedirectUrlCanceled(),
        ];

        return $this->vatlyApiClient->subscriptions->updateBilling(
            $subscriptionId,
            $payload,
        );
    }
}
