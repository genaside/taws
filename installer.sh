#!/bin/bash

cert_dir='/etc/ssl/taws/' 
site_dir='/var/www/taws/'

# Try to create directory for the self signed certs
#mkdir -pv $cert_dir
# create and self sign certs
#openssl req -x509 -nodes -days 365 -newkey rsa:4096 -keyout $cert_dir/apache.key -out $cert_dir/apache.crt

# Copy the taws apache conf file
#cp -v ./src/apache/taws.conf /etc/apache2/vhosts.d/

# Copy the site over
#mkdir -pv /var/www/taws/
cp -Ruv ./src/web/* $site_dir
chown apache:apache -Rv $site_dir

# Copy the sphinx files
#cp -uv ./src/sphinx/sphinx.conf /etc/sphinx/sphinx.conf
#searchd --stop; indexer -all; searchd