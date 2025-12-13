<?php

namespace Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderSymbol;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrderFeatureTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // Use log broadcaster during tests to avoid external calls
        Config::set('broadcasting.default', 'log');
    }

    /**
     * @return void
     */
    public function test_cannot_place_buy_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => '10']);
        Passport::actingAs($user);

        $payload = [
            'symbol' => 'BTC',
            'side' => 'buy',
            'amount' => '1',
        ];

        $this->postJson('/api/orders', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['balance']);

        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * @return void
     */
    public function test_cannot_place_sell_without_enough_asset(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        Asset::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '0.5',
            'locked_amount' => '0',
        ]);

        $payload = [
            'symbol' => 'BTC',
            'side' => 'sell',
            'amount' => '1',
        ];

        $this->postJson('/api/orders', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['asset']);

        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * @return void
     */
    public function test_full_match_sets_balances_assets_and_refunds_overlock(): void
    {
        $buyer = User::factory()->create(['balance' => '100000']);
        Passport::actingAs($buyer);

        $buyPayload = [
            'symbol' => 'BTC',
            'side' => 'buy',
            'amount' => '1',
        ];

        $this->postJson('/api/orders', $buyPayload)->assertCreated();

        $seller = User::factory()->create(['balance' => '0']);
        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '1',
            'locked_amount' => '0',
        ]);

        Passport::actingAs($seller);

        $sellPayload = [
            'symbol' => 'BTC',
            'side' => 'sell',
            'amount' => '1',
        ];

        $this->postJson('/api/orders', $sellPayload)->assertCreated();

        $buyOrder = Order::where('user_id', $buyer->id)->first();
        $sellOrder = Order::where('user_id', $seller->id)->first();

        $this->assertEquals(OrderStatus::FILLED, $buyOrder->status);
        $this->assertEquals(OrderStatus::FILLED, $sellOrder->status);
        $this->assertTrue(bccomp((string) $buyOrder->locked_value, '0', 8) === 0);
        $this->assertTrue(bccomp((string) $sellOrder->locked_value, '0', 8) === 0);

        $buyer->refresh();
        $seller->refresh();
        $buyerAsset = Asset::where('user_id', $buyer->id)->where('symbol', 'BTC')->first();
        $sellerAsset = Asset::where('user_id', $seller->id)->where('symbol', 'BTC')->first();

        $tradePrice = OrderSymbol::BTC->price();
        $volume = bcmul($tradePrice, '1', 8);
        $fee = bcmul($volume, '0.015', 8);
        $expectedBuyerBalance = bcsub('100000', bcadd($volume, $fee, 8), 8);
        $expectedSellerBalance = $volume;

        $this->assertTrue(bccomp($buyer->balance, $expectedBuyerBalance, 8) === 0);
        $this->assertTrue(bccomp($seller->balance, $expectedSellerBalance, 8) === 0);
        $this->assertTrue(bccomp($buyerAsset->amount, '1', 8) === 0);
        $this->assertTrue(bccomp($sellerAsset->amount, '0', 8) === 0);
        $this->assertTrue(bccomp($sellerAsset->locked_amount, '0', 8) === 0);
    }

    /**
     * @return void
     */
    public function test_cancel_buy_restores_locked_funds(): void
    {
        $user = User::factory()->create(['balance' => '1000']);
        Passport::actingAs($user);

        $payload = [
            'symbol' => 'USDT',
            'side' => 'buy',
            'amount' => '50', // locks 50*1 + 1.5% = 50.75
        ];

        $orderResponse = $this->postJson('/api/orders', $payload)->assertCreated();

        $orderId = $orderResponse->json('data.order.id') ?? $orderResponse->json('data.order.data.id') ?? $orderResponse->json('data.order.resource.id') ?? $orderResponse->json('data.order.resource.data.id');

        $user->refresh();
        $this->assertTrue(bccomp($user->balance, '949.25', 4) === 0);

        $this->postJson("/api/orders/{$orderId}/cancel")->assertOk();

        $user->refresh();
        $order = Order::find($orderId);

        $this->assertTrue(bccomp($user->balance, '1000', 4) === 0);
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
    }
}
