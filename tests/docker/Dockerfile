FROM bitnami/laravel

RUN cd / && composer create-project laravel/laravel app
RUN composer require glushkovds/phpclickhouse-laravel
WORKDIR "/app"
