cp /home/site/wwwroot/default /etc/nginx/sites-available/default
chown -R www-data:www-data /var/www/html/wwwroot
cd /home/site/wwwroot/ 

php artisan cache:clear
php artisan route:clear
php artisan config:cache
php artisan config:clear

service nginx reload

nginx -g "daemon off;"

tail -F storage/logs/laravel.log