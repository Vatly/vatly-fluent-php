<?php

declare(strict_types=1);

namespace Vatly\Fluent\Tests\Actions;

use Mockery;
use Vatly\API\Endpoints\SubscriptionEndpoint;
use Vatly\API\Types\Link;
use Vatly\API\VatlyApiClient;
use Vatly\Fluent\Actions\UpdateSubscriptionBilling;
use Vatly\Fluent\Contracts\ConfigurationInterface;
use Vatly\Fluent\Tests\TestCase;

class UpdateSubscriptionBillingTest extends TestCase
{
    private VatlyApiClient $mockApiClient;
    private SubscriptionEndpoint $mockSubscriptionEndpoint;
    private ConfigurationInterface $mockConfig;
    private UpdateSubscriptionBilling $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApiClient = Mockery::mock(VatlyApiClient::class);
        $this->mockSubscriptionEndpoint = Mockery::mock(SubscriptionEndpoint::class);
        $this->mockApiClient->subscriptions = $this->mockSubscriptionEndpoint;

        $this->mockConfig = Mockery::mock(ConfigurationInterface::class);
        $this->mockConfig->shouldReceive('getDefaultRedirectUrlSuccess')
            ->andReturn('https://app.example.com/billing/updated');
        $this->mockConfig->shouldReceive('getDefaultRedirectUrlCanceled')
            ->andReturn('https://app.example.com/billing');

        $this->action = new UpdateSubscriptionBilling($this->mockApiClient, $this->mockConfig);
    }

    public function test_it_fills_redirect_urls_from_config_defaults_when_caller_omits_them(): void
    {
        $subscriptionId = 'subscription_abc123';
        $expectedUrl = 'https://checkout.vatly.com/billing-update/abc123';

        $this->mockSubscriptionEndpoint
            ->shouldReceive('updateBilling')
            ->once()
            ->with($subscriptionId, [
                'redirectUrlSuccess' => 'https://app.example.com/billing/updated',
                'redirectUrlCanceled' => 'https://app.example.com/billing',
            ])
            ->andReturn(new Link($expectedUrl, 'text/html'));

        $response = $this->action->execute($subscriptionId);

        $this->assertInstanceOf(Link::class, $response);
        $this->assertSame($expectedUrl, $response->href);
        $this->assertSame('text/html', $response->type);
    }

    public function test_it_merges_config_defaults_with_caller_supplied_billing_address(): void
    {
        $subscriptionId = 'subscription_xyz789';

        $this->mockSubscriptionEndpoint
            ->shouldReceive('updateBilling')
            ->once()
            ->with($subscriptionId, [
                'billingAddress' => [
                    'streetAndNumber' => '123 Main St',
                    'city' => 'Amsterdam',
                    'country' => 'NL',
                ],
                'redirectUrlSuccess' => 'https://app.example.com/billing/updated',
                'redirectUrlCanceled' => 'https://app.example.com/billing',
            ])
            ->andReturn(new Link('https://checkout.vatly.com/billing-update/xyz', 'text/html'));

        $response = $this->action->execute($subscriptionId, [
            'billingAddress' => [
                'streetAndNumber' => '123 Main St',
                'city' => 'Amsterdam',
                'country' => 'NL',
            ],
        ]);

        $this->assertInstanceOf(Link::class, $response);
    }

    public function test_caller_supplied_redirect_urls_win_over_config_defaults(): void
    {
        $subscriptionId = 'subscription_override';

        $this->mockSubscriptionEndpoint
            ->shouldReceive('updateBilling')
            ->once()
            ->with($subscriptionId, [
                'redirectUrlSuccess' => 'https://override.example.com/done',
                'redirectUrlCanceled' => 'https://override.example.com/oops',
            ])
            ->andReturn(new Link('https://checkout.vatly.com/billing-update/override', 'text/html'));

        $response = $this->action->execute($subscriptionId, [
            'redirectUrlSuccess' => 'https://override.example.com/done',
            'redirectUrlCanceled' => 'https://override.example.com/oops',
        ]);

        $this->assertInstanceOf(Link::class, $response);
    }
}
