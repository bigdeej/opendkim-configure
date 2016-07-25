#!/bin/sh

echo "Installing OpenDKIM-Configure..."

cp -a ./req/opendkim-configure /usr/sbin/opendkim-configure
cp -a ./req/opendkim-configure.conf.php /usr/sbin/opendkim-configure.conf.php
cp -a ./req/opendkim-configure.php /usr/sbin/opendkim-configure.php

echo "Setting permissions..."

chmod +x /usr/sbin/opendkim-configure

echo "Done!"
echo "You can now run using the command opendkim-configure"
echo ""
