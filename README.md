# Laravel Test
##### This application requires laravel version 8 and php version 7.4 or above
#

> create a database with name "laravel_test" and add database credentials in .env file

#### Run following commands one by one

```
composer install
```
```
php artisan migrate
```
```
php artisan db:seed
```

> Note: The seeders are meant to be run only once for the sake of test

#### Swagger
> visit /docs for swagger documentation

#### Postman
> Postman API collection file 'api.postman_collection' is available at root directory

#### Testing
```
php artisan test
```
