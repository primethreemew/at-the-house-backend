# Copy the custom Nginx configuration
cp /var/www/html/wwwroot/nginx.conf /etc/nginx/sites-available/default

chmod +x /var/www/html/wwwroot/startup.sh

# Restart Nginx to apply the new configuration
service nginx restart