<?php

namespace Services;

use App\Enums\OrderStatus;
use App\Enums\OrderSymbol;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    private OrderService $service;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // avoid external broadcast calls
        Config::set('broadcasting.default', 'log');

        $this->service = app(OrderService::class);
    }

    /**
     * @return void
     */
    public function test_place_order_buy_insufficient_balance_throws(): void
    {
        $user = User::factory()->create(['balance' => '10']);
        Passport::actingAs($user);

        $this->expectException(ValidationException::class);

        $this->service->placeOrder($user, [
            'symbol' => 'BTC',
            'side' => 'buy',
            'amount' => '1',
        ]);
    }

    /**
     * @return void
     */
    public function test_place_order_sell_insufficient_asset_throws(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->expectException(ValidationException::class);

        $this->service->placeOrder($user, [
            'symbol' => 'BTC',
            'side' => 'sell',
            'amount' => '1',
        ]);
    }

    /**
     * @return void
     */
    public function test_full_match_fills_orders_updates_balances_and_assets(): void
    {
        $buyer = User::factory()->create(['balance' => '100000']);
        Passport::actingAs($buyer);

        [$buyOrder] = $this->service->placeOrder($buyer, [
            'symbol' => 'BTC',
            'side' => 'buy',
            'amount' => '1',
        ]);

        $seller = User::factory()->create(['balance' => '0']);
        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '1',
            'locked_amount' => '0',
        ]);

        Passport::actingAs($seller);

        [$sellOrder, $trade] = $this->service->placeOrder($seller, [
            'symbol' => 'BTC',
            'side' => 'sell',
            'amount' => '1',
        ]);

        $buyOrder->refresh();
        $sellOrder->refresh();
        $buyer->refresh();
        $seller->refresh();
        $buyerAsset = Asset::where('user_id', $buyer->id)->where('symbol', 'BTC')->first();
        $sellerAsset = Asset::where('user_id', $seller->id)->where('symbol', 'BTC')->first();

        $this->assertEquals(OrderStatus::FILLED, $buyOrder->status);
        $this->assertEquals(OrderStatus::FILLED, $sellOrder->status);
        $this->assertNotNull($trade);
        $this->assertTrue(bccomp((string) $trade->price, OrderSymbol::BTC->price(), 8) === 0);

        $tradePrice = OrderSymbol::BTC->price();
        $volume = bcmul($tradePrice, '1', 8);
        $fee = bcmul($volume, '0.015', 8);
        $expectedBuyerBalance = bcsub('100000', bcadd($volume, $fee, 8), 8);

        $this->assertTrue(bccomp($buyer->balance, $expectedBuyerBalance, 8) === 0);
        $this->assertTrue(bccomp($seller->balance, $volume, 8) === 0);
        $this->assertTrue(bccomp($buyerAsset->amount, '1', 8) === 0);
        $this->assertTrue(bccomp($sellerAsset->amount, '0', 8) === 0);
        $this->assertTrue(bccomp($sellerAsset->locked_amount, '0', 8) === 0);
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function test_cancel_buy_refunds_locked_value(): void
    {
        $user = User::factory()->create(['balance' => '1000']);
        Passport::actingAs($user);

        [$order] = $this->service->placeOrder($user, [
            'symbol' => 'USDT',
            'side' => 'buy',
            'amount' => '50',
        ]);

        $user->refresh();
        $this->assertTrue(bccomp($user->balance, '949.25', 4) === 0);

        $this->service->cancel($user, $order);

        $user->refresh();
        $order->refresh();

        $this->assertTrue(bccomp($user->balance, '1000', 4) === 0);
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
    }
}
