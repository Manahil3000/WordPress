#!/bin/bash
# Use Railway's port if available, otherwise default to 80
PORT=${PORT:-80}

# Update Apache to listen on that port
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/" /etc/apache2/sites-available/000-default.conf

# Optional: set a dummy ServerName to remove the warning
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Start Apache in the foreground
apache2-foreground
