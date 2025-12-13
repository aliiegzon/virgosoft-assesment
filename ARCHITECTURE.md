## Architecture Overview

### Stack
- **Backend:** Laravel API, Passport auth, Pusher broadcasting.
- **Frontend:** Vue 3 (Composition API) + Vite, Pusher/Echo for realtime.
- **Database:** Postgres.
- **Realtime Channels:** `private-user.{id}`, `orderbook.{symbol}`, `trades.{symbol}`.

### Domain Model
- **User:** Has USD `balance` and related assets/orders.
- **Asset:** `user_id`, `symbol`, `amount`, `locked_amount`.
- **Order:** `user_id`, `symbol`, `side (buy/sell)`, `price`, `amount`, `status (1=open,2=filled,3=cancelled)`, `locked_value`.
- **Trade:** Records executed matches (symbol, buy_order_id, sell_order_id, price, amount, volume_usd, fee_usd).

### Order Lifecycle
1) **Place order (POST /api/orders)**  
   - Validates funds/assets.  
   - Creates order, locks USD or asset.  
   - Attempts match (full match only).
2) **Match (OrderService::settle)**  
   - Locks rows, applies fee, updates buyer/seller balances/assets, marks both FILLED, clears locked_value.  
   - Creates Trade.  
   - Broadcasts `OrderMatched` to buyer/seller + `orderbook.{symbol}` + `trades.{symbol}`.
3) **Cancel (POST /api/orders/{id}/cancel)**  
   - Refunds locked USD or asset, marks order CANCELLED.  
   - Broadcasts `OrderCancelled` to user + `orderbook.{symbol}`.

### Broadcasting Events
- **OrderPlaced:** New unmatched order; channels: `private-user.{user_id}`, `orderbook.{symbol}`.  
- **OrderCancelled:** Cancelled order; channels: `private-user.{user_id}`, `orderbook.{symbol}`.  
- **OrderMatched:** Matched trade; channels: `private-user.{buyer_id}`, `private-user.{seller_id}`, `orderbook.{symbol}`, `trades.{symbol}`.  
All implement `ShouldBroadcastNow` and `$afterCommit = true`.

### Frontend State & Realtime Flow
- Initial load: GET profile, open orders, orderbook, trades.  
- Echo subscriptions: `private-user.{id}`, `orderbook.{symbol}`, `trades.{symbol}`.  
- Handlers:  
  - **OrderPlaced:** Update wallet, push order into open orders, add to orderbook.  
  - **OrderCancelled:** Remove from open orders/orderbook, update wallet.  
  - **OrderMatched:** Update wallet (buyer/seller), remove matched orders from open orders and orderbook, prepend trade.
- Goal: No extra GETs after initializing, UI stays in sync from events.

### Persistence & Concurrency
- All monetary/asset updates inside DB transactions with row locks.  
- Fee applied on match; buyer/seller balances/assets are updated atomically.  
- `locked_value` and `locked_amount` are maintained for BUY/SELL reservations.
