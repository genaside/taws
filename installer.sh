#!/bin/bash

cert_dir='/etc/ssl/taws/' 
site_dir='/var/www/taws/'

#exit

# Try to create directory for the self signed certs
#mkdir -pv $cert_dir
# create and self sign certs
#openssl req -x509 -nodes -days 365 -newkey rsa:4096 -keyout $cert_dir/apache.key -out $cert_dir/apache.crt

# Copy the taws apache conf file
cp -v ./src/apache/taws.conf /etc/apache2/vhosts.d/


# Copy the site over
mkdir -pv /var/www/taws/
cp -Rv ./src/web/* $site_dir
chown apache:apache -Rv $site_dir

# Copy the sphinx conf file
cp -uv ./src/sphinx/conf/sphinx.conf /etc/sphinx/sphinx.conf

# Copy the rest of the sphinx files
mkdir -pv /var/sphinx
cp -uv ./src/sphinx/data/* /var/sphinx/

