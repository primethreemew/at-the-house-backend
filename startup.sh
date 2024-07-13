#!/bin/bash

# Path to the default Nginx configuration file
NGINX_CONF="/etc/nginx/sites-available/default"

# Update Nginx configuration to set the document root to the public directory of Laravel
cat <<EOL > $NGINX_CONF
server {
    listen 8080;
    listen [::]:8080;
    root /var/www/html/wwwroot;
    index index.php index.html index.htm;
    server_name example.com www.example.com;
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

    location ~* [^/]\.php(/|$) {
        fastcgi_split_path_info ^(.+?\.[Pp][Hh][Pp])(|/.*)$;
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        fastcgi_param HTTP_PROXY "";
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
        fastcgi_param QUERY_STRING \$query_string;
        fastcgi_intercept_errors on;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 3600;
        fastcgi_read_timeout 3600;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }
}
EOL

# Restart Nginx to apply the changes
service nginx restart
