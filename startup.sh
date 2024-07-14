#!/bin/bash

# Path to the default Nginx configuration file
NGINX_CONF="/etc/nginx/sites-available/default"

# Update Nginx configuration to set the document root to the public directory of Laravel
cat <<EOL > $NGINX_CONF
server {
    listen 8080;
    listen [::]:8080;
    root /home/site/wwwroot/public;
    index index.php index.html index.htm;
    server_name atthehouseorgapi.azurewebsites.net www.atthehouseorgapi.azurewebsites.net;
    port_in_redirect off;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    error_page 500 502 503 504 /50x.html;
    location = /50x.html {
        root /html/;
    }

    location ~ /\.git {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOL

# Set correct permissions
find /home/site/wwwroot -type d -exec chmod 755 {} \;
find /home/site/wwwroot -type f -exec chmod 644 {} \;

# Ensure correct ownership
chown -R www-data:www-data /home/site/wwwroot

# Restart Nginx to apply the changes
service nginx restart
