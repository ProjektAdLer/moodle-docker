#!/bin/bash

xdebug_version="3.2.2"

apt update
apt-get install wget gnupg ca-certificates apt-transport-https software-properties-common -y
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/sury-php.list
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
apt update
apt install -y php8.1-dev autoconf automake

mkdir /tmp/xdebug
cd /tmp/xdebug
wget https://xdebug.org/files/xdebug-${xdebug_version}.tgz
tar -xvzf xdebug-${xdebug_version}.tgz
cd xdebug-${xdebug_version}
phpize
./configure
make
cp modules/xdebug.so /opt/bitnami/php/lib/php/extensions/

{
  echo "zend_extension = xdebug"
  echo ""
  echo "; Defaults"
  echo "xdebug.default_enable=1"
  echo "xdebug.remote_enable=1"
  echo "xdebug.remote_port=9000"
  echo ""
  echo "; The Windows way"
  echo "xdebug.remote_connect_back=0"
  echo "xdebug.remote_host=127.10.0.1"
  echo ""
  echo "; idekey value is specific to PhpStorm"
  echo "xdebug.idekey=PHPSTORM"
  echo ""
  echo "; Optional: Set to true to always auto-start xdebug"
  echo "xdebug.remote_autostart=true"
  echo "#change the above lines starting from \"; Defaults\" to be echoed to the xdebug.ini file"
} >> /opt/bitnami/php/etc/conf.d/xdebug.ini

