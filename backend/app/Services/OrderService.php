<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Events\OrderPlaced;
use App\Events\OrderCancelled;
use App\Enums\OrderStatus;
use App\Enums\OrderSymbol;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\QueryBuilder;

class OrderService extends BaseService
{
    private const SCALE = 8;
    private const FEE_RATE = '0.015';

    private const BUY  = 'buy';
    private const SELL = 'sell';

    /**
     * @param  Order  $model
     * @param  Asset  $asset
     * @param  Trade  $trade
     */
    public function __construct(Order $model, private readonly Asset $asset, private readonly Trade $trade)
    {
        parent::__construct($model);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): array|Collection|LengthAwarePaginator
    {
        $model = $this->model;

        $query = QueryBuilder::for($model::class)
            ->allowedFilters($model->allowedFilters())
            ->allowedSorts($model->allowedSorts())
            ->defaultSorts($model->defaultSorts())
            ->allowedIncludes($model->allowedIncludes())
            ->where('user_id', auth()->id());

        return $query->paginate((int) request()->get('per_page') ?: $this->indexLimit);
    }

    /**
     * @param  User  $user
     * @param  array  $data
     * @return array
     */
    public function placeOrder(User $user, array $data): array
    {
        $data['symbol'] = strtoupper((string) $data['symbol']);
        $data['price'] = OrderSymbol::from($data['symbol'])->price();

        $trade = null;

        $order = DB::transaction(function () use ($user, $data, &$trade) {
            $order = $this->reserveAndCreate($user, $data);
            $trade = $this->matchAndSettle($order);

            return $order->fresh();
        });

        // If no trade occurred, broadcast placement to user + orderbook
        if (!$trade) {
            $this->broadcastOrderPlaced($user->fresh(), $order->fresh());
        }

        return [$order, $trade];
    }

    /**
     * @param  User  $user
     * @param  Order  $order
     * @return Order
     * @throws ValidationException
     */
    public function cancel(User $user, Order $order): Order
    {
        $this->ensureOwner($user, $order);
        $this->ensureOpen($order);

        DB::transaction(function () use ($user, $order) {
            $lockedOrder = $this->lockOrder($order->id);

            $this->refundReservation($user, $lockedOrder);

            $lockedOrder->status = OrderStatus::CANCELLED;
            $lockedOrder->locked_value = '0';
            $lockedOrder->save();
        });

        $order->refresh();
        $user->refresh();

        $this->broadcastOrderCancelled($user, $order);

        return $order->fresh();
    }

    // Reserve + Create
    /**
     * @param  User  $user
     * @param  array  $data
     * @return Builder|Model
     * @throws ValidationException
     */
    private function reserveAndCreate(User $user, array $data): Builder|Model
    {
        $side   = (string) $data['side'];
        $symbol = (string) $data['symbol'];
        $amount = (string) $data['amount'];
        $price  = (string) $data['price'];

        return match ($side) {
            self::BUY  => $this->createBuyOrder($user, $symbol, $amount, $price),
            self::SELL => $this->createSellOrder($user, $symbol, $amount, $price),
            default    => throw ValidationException::withMessages(['side' => ['Invalid side.']]),
        };
    }

    /**
     * @param  User  $user
     * @param  string  $symbol
     * @param  string  $amount
     * @param  string  $price
     * @return Builder|Model
     * @throws ValidationException
     */
    private function createBuyOrder(User $user, string $symbol, string $amount, string $price): Builder|Model
    {
        $total = $this->reserveBuyFunds($user->id, $amount, $price);

        return $this->model->newQuery()->create([
            'user_id'      => $user->id,
            'symbol'       => $symbol,
            'side'         => self::BUY,
            'price'        => $price,
            'amount'       => $amount,
            'status'       => OrderStatus::OPEN,
            'locked_value' => $total,
        ]);
    }

    /**
     * @param  User  $user
     * @param  string  $symbol
     * @param  string  $amount
     * @param  string  $price
     * @return Builder|Model
     * @throws ValidationException
     */
    private function createSellOrder(User $user, string $symbol, string $amount, string $price): Builder|Model
    {
        $this->reserveSellAsset($user->id, $symbol, $amount);

        return $this->model->newQuery()->create([
            'user_id'      => $user->id,
            'symbol'       => $symbol,
            'side'         => self::SELL,
            'price'        => $price,
            'amount'       => $amount,
            'status'       => OrderStatus::OPEN,
            'locked_value' => '0',
        ]);
    }

    /**
     * @param  int|string  $userId
     * @param  string  $amount
     * @param  string  $price
     * @return string
     * @throws ValidationException
     */
    private function reserveBuyFunds(int|string $userId, string $amount, string $price): string
    {
        $volume = $this->multiply($amount, $price);
        $fee    = $this->multiply($volume, self::FEE_RATE);
        $total  = $this->add($volume, $fee);

        $user = $this->lockUser($userId);

        if ($this->lessThan($user->balance, $total)) {
            throw ValidationException::withMessages(['balance' => ['Insufficient USD balance to place order.']]);
        }

        $user->balance = $this->subtract($user->balance, $total);
        $user->save();

        return $total;
    }

    /**
     * @param  int|string  $userId
     * @param  string  $symbol
     * @param  string  $amount
     * @return void
     * @throws ValidationException
     */
    private function reserveSellAsset(int|string $userId, string $symbol, string $amount): void
    {
        $asset = $this->lockAsset($userId, $symbol);

        if ($this->lessThan($asset->amount, $amount)) {
            throw ValidationException::withMessages(['asset' => ['Insufficient asset balance to place order.']]);
        }

        $asset->amount        = $this->subtract($asset->amount, $amount);
        $asset->locked_amount = $this->add($asset->locked_amount, $amount);
        $asset->save();
    }

    // Match and Settle
    /**
     * @param  Order  $order
     * @return Builder|Model|null
     * @throws ValidationException
     */
    private function matchAndSettle(Order $order): Builder|Model|null
    {
        $order = $this->lockOrder($order->id);

        if ($order->status !== OrderStatus::OPEN) {
            return null;
        }

        $counter = $this->findCounterOrder($order);

        if (!$counter) {
            return null;
        }

        [$buy, $sell] = $order->side === self::BUY ? [$order, $counter] : [$counter, $order];

        return $this->settle($buy, $sell);
    }

    /**
     * @param  Order  $order
     * @return Builder|Model|HigherOrderWhenProxy|null
     */
    private function findCounterOrder(Order $order): Builder|Model|HigherOrderWhenProxy|null
    {
        return $this->model->newQuery()
            ->where('symbol', $order->symbol)
            ->where('status', OrderStatus::OPEN)
            ->whereKeyNot($order->id)
            ->when(
                $order->side === self::BUY,
                fn ($q) => $q->where('side', self::SELL)->where('price', '<=', $order->price),
                fn ($q) => $q->where('side', self::BUY)->where('price', '>=', $order->price),
            )
            ->where('amount', $order->amount)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();
    }

    /**
     * @param  Order  $buy
     * @param  Order  $sell
     * @return Builder|Model
     * @throws ValidationException
     */
    private function settle(Order $buy, Order $sell): Builder|Model
    {
        // Trade at seller (maker) price with exact amount match
        $price  = (string) $sell->price;
        $amount = (string) $buy->amount;

        $volume = $this->multiply($amount, $price);
        $fee    = $this->multiply($volume, self::FEE_RATE);

        $buyer  = $this->lockUser($buy->user_id);
        $seller = $this->lockUser($sell->user_id);

        $buyerAsset  = $this->lockAsset($buyer->id, $buy->symbol, allowCreate: true);
        $sellerAsset = $this->lockAsset($seller->id, $sell->symbol);

        if ($this->lessThan($sellerAsset->locked_amount, $amount)) {
            throw ValidationException::withMessages([
                'asset' => ['Locked asset amount is insufficient to complete trade.']
            ]);
        }

        $buyerAsset->amount = $this->add($buyerAsset->amount, $amount);
        $buyerAsset->save();

        $sellerAsset->locked_amount = $this->subtract($sellerAsset->locked_amount, $amount);
        $sellerAsset->save();

        // Refund buyer any over lock when trade executes below their limit
        $requiredTotal = $this->add($volume, $fee);
        $lockedTotal   = (string) $buy->locked_value;
        if ($this->lessThan($requiredTotal, $lockedTotal)) {
            $refund = $this->subtract($lockedTotal, $requiredTotal);
            $buyer->balance = $this->add($buyer->balance, $refund);
            $buyer->save();
        }

        $seller->balance = $this->add($seller->balance, $volume);
        $seller->save();

        $this->markFilled($buy);
        $this->markFilled($sell);
        $buy->locked_value = '0';
        $sell->locked_value = '0';
        $buy->save();
        $sell->save();

        $trade = $this->trade->newQuery()->create([
            'symbol'        => $buy->symbol,
            'buy_order_id'  => $buy->id,
            'sell_order_id' => $sell->id,
            'price'         => $price,
            'amount'        => $amount,
            'volume_usd'    => $volume,
            'fee_usd'       => $fee,
        ]);

        $buy->refresh();
        $sell->refresh();
        $buyerAsset->refresh();
        $sellerAsset->refresh();
        $buyer->refresh();
        $seller->refresh();

        OrderMatched::dispatch([
            'trade' => [
                'id' => $trade->id,
                'symbol' => $trade->symbol,
                'price' => $trade->price,
                'amount' => $trade->amount,
                'volume_usd' => $trade->volume_usd,
                'fee_usd' => $trade->fee_usd,
            ],
            'buy_order' => [
                'id' => $buy->id,
                'user_id' => $buy->user_id,
                'symbol' => $buy->symbol,
                'side' => $buy->side,
                'price' => $buy->price,
                'amount' => $buy->amount,
                'status' => [
                    'value' => OrderStatus::FILLED->value,
                    'label' => OrderStatus::FILLED->name,
                ],
                'locked_value' => $buy->locked_value,
            ],
            'sell_order' => [
                'id' => $sell->id,
                'user_id' => $sell->user_id,
                'symbol' => $sell->symbol,
                'side' => $sell->side,
                'price' => $sell->price,
                'amount' => $sell->amount,
                'status' => [
                    'value' => OrderStatus::FILLED->value,
                    'label' => OrderStatus::FILLED->name,
                ],
                'locked_value' => $sell->locked_value,
            ],
            'buyer' => [
                'id' => $buyer->id,
                'balance' => $buyer->balance,
                'asset' => [
                    'symbol' => $buyerAsset->symbol,
                    'amount' => $buyerAsset->amount,
                    'locked_amount' => $buyerAsset->locked_amount,
                ],
            ],
            'seller' => [
                'id' => $seller->id,
                'balance' => $seller->balance,
                'asset' => [
                    'symbol' => $sellerAsset->symbol,
                    'amount' => $sellerAsset->amount,
                    'locked_amount' => $sellerAsset->locked_amount,
                ],
            ],
        ]);

        return $trade;
    }

    /**
     * @param  Order  $order
     * @return void
     */
    private function markFilled(Order $order): void
    {
        $order->status = OrderStatus::FILLED;
        $order->locked_value = '0';
        $order->save();
    }

    // Order cancel helpers
    /**
     * @param  User  $user
     * @param  Order  $order
     * @return void
     * @throws ValidationException
     */
    private function refundReservation(User $user, Order $order): void
    {
        match ($order->side) {
            self::BUY  => $this->refundBuy($user->id, (string) $order->locked_value),
            self::SELL => $this->refundSell($user->id, (string) $order->symbol, (string) $order->amount),
            default    => null,
        };
    }

    /**
     * @param  int|string  $userId
     * @param  string  $lockedValue
     * @return void
     */
    private function refundBuy(int|string $userId, string $lockedValue): void
    {
        $user = $this->lockUser($userId);
        $user->balance = $this->add($user->balance, $lockedValue);
        $user->save();
    }

    /**
     * @param  int|string  $userId
     * @param  string  $symbol
     * @param  string  $amount
     * @return void
     * @throws ValidationException
     */
    private function refundSell(int|string $userId, string $symbol, string $amount): void
    {
        $asset = $this->lockAsset($userId, $symbol);
        $asset->amount = $this->add($asset->amount, $amount);
        $asset->locked_amount = $this->subtract($asset->locked_amount, $amount);
        $asset->save();
    }

    /**
     * Broadcast newly placed (unmatched) order to user + orderbook.
     */
    private function broadcastOrderPlaced(User $user, Order $order): void
    {
        $asset = $this->asset->newQuery()
            ->where('user_id', $user->id)
            ->where('symbol', $order->symbol)
            ->first();

        $payload = [
            'order' => [
                'id'           => $order->id,
                'user_id'      => $order->user_id,
                'symbol'       => $order->symbol,
                'side'         => $order->side,
                'price'        => $order->price,
                'amount'       => $order->amount,
                'status'       => [
                    'value' => $order->status->value,
                    'label' => $order->status->name,
                ],
                'locked_value' => $order->locked_value,
                'created_at'   => $order->created_at?->toIso8601String(),
            ],
            'user' => [
                'id'      => $user->id,
                'balance' => $user->balance,
                'asset'   => $asset ? [
                    'symbol'        => $asset->symbol,
                    'amount'        => $asset->amount,
                    'locked_amount' => $asset->locked_amount,
                ] : null,
            ],
        ];

        OrderPlaced::dispatch($payload);
    }

    /**
     * Broadcast cancellation to user + orderbook.
     */
    private function broadcastOrderCancelled(User $user, Order $order): void
    {
        $asset = $this->asset->newQuery()
            ->where('user_id', $user->id)
            ->where('symbol', $order->symbol)
            ->first();

        $payload = [
            'order' => [
                'id'           => $order->id,
                'user_id'      => $order->user_id,
                'symbol'       => $order->symbol,
                'side'         => $order->side,
                'price'        => $order->price,
                'amount'       => $order->amount,
                'status'       => [
                    'value' => OrderStatus::CANCELLED->value,
                    'label' => OrderStatus::CANCELLED->name,
                ],
                'locked_value' => $order->locked_value,
                'updated_at'   => $order->updated_at?->toIso8601String(),
            ],
            'user' => [
                'id'      => $user->id,
                'balance' => $user->balance,
                'asset'   => $asset ? [
                    'symbol'        => $asset->symbol,
                    'amount'        => $asset->amount,
                    'locked_amount' => $asset->locked_amount,
                ] : null,
            ],
        ];

        OrderCancelled::dispatch($payload);
    }

    /**
     * @param  User  $user
     * @param  Order  $order
     * @return void
     * @throws ValidationException
     */
    private function ensureOwner(User $user, Order $order): void
    {
        if ((string) $order->user_id !== (string) $user->id) {
            throw ValidationException::withMessages(['order' => ['You are not allowed to cancel this order.']]);
        }
    }

    /**
     * @param  Order  $order
     * @return void
     * @throws ValidationException
     */
    private function ensureOpen(Order $order): void
    {
        if ($order->status !== OrderStatus::OPEN) {
            throw ValidationException::withMessages(['order' => ['Only open orders can be cancelled.']]);
        }
    }

    // Locks
    /**
     * @param  int|string  $id
     * @return Builder|Model
     */
    private function lockUser(int|string $id): Builder|Model
    {
        return User::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  int|string  $id
     * @return Builder|Model
     */
    private function lockOrder(int|string $id): Builder|Model
    {
        return $this->model->newQuery()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  int|string  $userId
     * @param  string  $symbol
     * @param  bool  $allowCreate
     * @return Builder|Model
     * @throws ValidationException
     */
    private function lockAsset(int|string $userId, string $symbol, bool $allowCreate = false): Builder|Model
    {
        $asset = $this->asset->newQuery()
            ->where('user_id', $userId)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->first();

        if ($asset) {
            return $asset;
        }

        if (!$allowCreate) {
            throw ValidationException::withMessages(['asset' => ['Asset not found for this user.']]);
        }

        $this->asset->newQuery()->create([
            'user_id'       => $userId,
            'symbol'        => $symbol,
            'amount'        => '0',
            'locked_amount' => '0',
        ]);

        return $this->asset->newQuery()
            ->where('user_id', $userId)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->firstOrFail();
    }

    // Decimal math functions
    private function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::SCALE);
    }
    private function subtract(string $a, string $b): string
    {
        return bcsub($a, $b, self::SCALE);
    }
    private function multiply(string $a, string $b): string
    {
        return bcmul($a, $b, self::SCALE);
    }
    private function lessThan(string $a, string $b): bool
    {
        return bccomp($a, $b, self::SCALE) === -1;
    }
}
