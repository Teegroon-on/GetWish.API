cd /var/www/getwish

git reset --hard
git fetch --all
git pull origin master

composer install
php artisan migrate
