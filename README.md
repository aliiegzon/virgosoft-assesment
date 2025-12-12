## VirgoSoft Assesment

### After cloning the project execute the following steps:

1. Create a .env file if it there isn't one and copy the .env.example content to the .env file
2. Run:
```
 docker-compose up -d
```
3. Check if all of the docker desktop containers are running, if not manually run them via docker desktop, and then run:
```
 docker exec -it virgosoft-assesment bash
 composer install
 composer dumpautoload
 php artisan passport:keys --force
 chmod -R 666 storage/logs/laravel.log
 php artisan migrate --seed
```
4. To run tests run:
```
 docker exec virgosoft-assesment php artisan test
```
5. Read ARCHITECTURE.md for the architectural overview
