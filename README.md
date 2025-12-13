## VirgoSoft Assessment

Laravel API + Vue 3 frontend with Pusher realtime events

### Setup
1) Backend env  
Copy `backend/.env.example` to `backend/.env` and set:
- `DB_CONNECTION=pgsql`
- `BROADCAST_DRIVER=pusher`
- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`
- Although all of these are already set in backend/.env.example to make it easier for running the app

2) Frontend env  
Copy `frontend/.env.example` to `frontend/.env` and set:
- `VITE_API_BASE`
- Pusher keys/cluster/host/port/scheme

3) Start the stack  
```bash
docker compose up -d
```

4) Install backend deps + migrate/seed  
```bash
docker exec -it virgosoft-assessment bash
composer install
composer dumpautoload
php artisan passport:keys
php artisan migrate --seed
```

5) The frontend dev server runs in the `virgosoft-assessment-frontend-1` container on port 5173.

6) Tests  
```bash
docker exec virgosoft-assessment php artisan test
```

### Realtime channels
- `private-user.{id}`
- `orderbook.{symbol}`
- `trades.{symbol}`

See `ARCHITECTURE.md` for an overview of flows and components.
