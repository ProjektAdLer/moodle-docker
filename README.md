# Moodle Bitnami Image Extension - User Creation, PHP Environment Variables, and AdLer Setup
This project extends the bitnami/moodle image with the following features:
- Setting up AdLer (after the first start the Moodle part of AdLer is fully set up).
- Create user(s) on first start 
- Adding another environment variable to set a php.ini option.



## Windows Users
This project works only under Linux. 
Git on Windows (also WSL) breaks the line endings which is why it cannot be used there. 
Also, editing on Windows can cause the project to stop working on Linux as well. 
To use this project on Windows you have to disable the option core.autocrlf 
(why the hell does this option exist and why is it enabled by default on Windows...). 
To do this run the following command before the git clone `git config --global core.autocrlf false`. 

**ATTENTION**: This affects all git repositories on this PC.

If you want to run this project with Windows without disabling autocrlf you can use an Docker-twostage approach.
I will not support this, but this is an approach you could use to implement it by yourself:
```
RUN apk add dos2unix
RUN cat setup.sh | dos2unix > setup.sh.tmp
RUN mv setup.sh.tmp setup.sh
RUN chmod +x setup.sh
```

## Environment variables
### PHP environment variables

| Variable                | Description                                                                       |
|-------------------------|-----------------------------------------------------------------------------------|
| `PHP_OUTPUT_BUFFERING`  | Controls the output buffering behavior of PHP. Set it to adjust the buffering setting in the `php.ini` file. |

### Moodle user creation variables

| Variable             | Description                                                                                                                                                            |
|----------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `USER_NAME`          | Specifies the login name of a user to be created during the initial setup. Watch out that the default name of the admin user of bitnami/docker is "user"               |
| `USER_PASSWORD`      | Specifies the password for the user created during the initial setup. Passwords have to follow moodle password validation rules. Otherwise the setup script will break. |
| `USER_FIRST_NAME`    | Specifies the first name of the user created during the initial setup.                                                                                                 |
| `USER_LAST_NAME`     | Specifies the last name of the user created during the initial setup.                                                                                                  |
| `USER_EMAIL`         | Specifies the email address of the user created during the initial setup.                                                                                              |
| `USER_ROLE`          | Specifies the short name of a role to assign to the user created during the initial setup.                                                                             |

#### Examples
Example one user
```
USER_NAME=john_doe
USER_PASSWORD=Pass1234
USER_FIRST_NAME=John
```
Example three users
```
USER_NAME=user1,user2,user3
USER_PASSWORD=Secret123,Secret123,Pass1234
USER_FIRST_NAME=First1,First2,First3
USER_LAST_NAME=Last1,Last2,Last3
USER_EMAIL=user1@example.com,user2@example.com,user3@example.com
USER_ROLE=false,manager,false
```

## Sample docker-compose.yml
see [tests/docker-compose.yml](tests/docker-compose.yml)

## Docker Build Arguments

When building the Docker image for this project, you can customize the following arguments:

- `MOODLE_VERSION`: Specifies the version of Moodle to be used in the image. The default value is `latest`.
- `PLUGIN_VERSION`: Specifies the version of the Moodle plugin to be included in the image. The default value is `main`.

These arguments allow you to control the versions of Moodle and the plugin that are used during the image build process. You can adjust these values according to your specific requirements and preferences.


# Moodle dev env
## approach WSL
This documentation outlines the steps to set up a Moodle PHP development environment 
using Windows Subsystem for Linux (WSL2) and Docker Desktop. Other potential approaches are 
described and evaluated below.

## Requirements
- WSL2
- Docker Desktop

## Warnings / Hints
- This approach expects port 80 to be unused.
- This approach expects apache is not yet used in the WSL instance.
  It will likely break whatever apache is running in the WSL instance.
  If you are already using apache in the WSL instance, you might want to use another `--distribution` for this approach.
  Note that you will likely also have to change the port of the apache server in this case.
- To resolve any issues with shell scripts (typically ^M errors), disable automatic line ending conversion in git by running: 
- `git config --global core.autocrlf false` or `git config --global core.autocrlf auto`

**Debug shell scripts manually executed in WSL**:
For PHPStorm path mapping to work it is required to set an environment variable in the WSL instance before executing the PHP script: `export PHP_IDE_CONFIG="serverName=localhost"`

## Environment Setup


### Windows Setup
Windows classifies the WSL network as public. 
Therefore, no incoming network connections from WSL to Windows are allowed.

Workaround ([see this issue](https://github.com/microsoft/WSL/issues/4585#issuecomment-610061194):
1. Open Windows terminal as admin
2. Run `New-NetFirewallRule -DisplayName "WSL" -Direction Inbound  -InterfaceAlias "vEthernet (WSL)"  -Action Allow`

⚠️ This has to be done after every reboot.

### PHPStorm setup
1. **WSL PHP Interpreter**:
  - Navigate to Settings -> PHP -> CLI interpreter
  - Click 3 dots -> "+" -> From Docker, Vagrant, ... -> WSL
  - Choose your WSL2 distribution and press OK.

2. **Set new interpreter as project default**:
  - Ctrl + Shift + A -> Change PHP interpreter 
  - Choose new interpreter -> OK

3. **PHP Server Setup**:
  - Navigate to Settings -> PHP -> Servers -> localhost
  - On the first incoming debugging connection, this entry should be created. If not, add it manually (Host: localhost, Port: 80, Debugger: Xdebug).
  - Check "Use path mappings (...)"
  - Add the following path mapping:  
    `\\wsl$\\Ubuntu\\home\\markus\\moodle -> /home/markus/moodle`

### Installation Script
⚠️ **Run this script only once. To run again, execute the uninstall script first.**

1. **Download Moodle**:  
   - Download and place the Moodle folder in `/home/markus/moodle`.
   - Download plugins and copy them to respective folders in `/home/markus/moodle`. If installing without plugins the section "setup for plugins" of the setup script will fail.

2. **Execute the Script**:  
   The following bash script sets up your environment, including installing required packages, setting up the database, and configuring Apache and PHP.

```bash
#!/bin/bash
WSL_USER=markus
MOODLE_PARENT_DIRECTORY=/home/$WSL_USER

# install dependencies
sudo apt install -y apache2 php8.1 php8.1-curl php8.1-zip composer php8.1-gd php8.1-dom php8.1-xml php8.1-mysqli php8.1-soap php8.1-xmlrpc php8.1-intl php8.1-xdebug

# create moodle folders
mkdir $MOODLE_PARENT_DIRECTORY/moodle $MOODLE_PARENT_DIRECTORY/moodledata $MOODLE_PARENT_DIRECTORY/moodledata_phpu
# download moodle to $MOODLE_PARENT_DIRECTORY/moodle

# setup database
sudo docker run -d --name moodle_mariadb --env MARIADB_USER=moodle --env MARIADB_PASSWORD=moodle --env MARIADB_DATABASE=moodle --env MARIADB_ROOT_PASSWORD=password --restart=always -p 3312:3306 mariadb
while ! mysqladmin ping -hlocalhost -P3312 --silent 2>/dev/null; do echo "db is starting" && sleep 1; done
echo "db is up"

# configure apache
sudo sed -i 's#<Directory /var/www/>#<Directory $MOODLE_PARENT_DIRECTORY/>#g'  /etc/apache2/apache2.conf
sudo sed -i 's#DocumentRoot /var/www/html#DocumentRoot $MOODLE_PARENT_DIRECTORY/moodle#g' /etc/apache2/sites-enabled/000-default.conf
sudo sed -i 's#export APACHE_RUN_USER=www-data#export APACHE_RUN_USER=$WSL_USER#g' /etc/apache2/envvars
sudo sed -i 's#export APACHE_RUN_GROUP=www-data#export APACHE_RUN_GROUP=$WSL_USER#g' /etc/apache2/envvars

# configure php
echo "max_input_vars = 5000" | sudo tee /etc/php/8.1/cli/conf.d/moodle.ini
sudo ln -s  /etc/php/8.1/cli/conf.d/moodle.ini /etc/php/8.1/apache2/conf.d/moodle.ini

echo "
[XDebug]
# https://xdebug.org/docs/all_settings
zend_extension = xdebug

xdebug.mode=debug
;xdebug.mode=develop
xdebug.client_port=9000

; host ip adress of wsl network adapter
xdebug.client_host=172.18.48.1

; idekey value is specific to PhpStorm
xdebug.idekey=phpstorm

xdebug.start_with_request=true
" | sudo tee /etc/php/8.1/apache2/conf.d/20-xdebug.ini
sudo rm /etc/php/8.1/cli/conf.d/20-xdebug.ini
sudo ln -s  /etc/php/8.1/apache2/conf.d/20-xdebug.ini /etc/php/8.1/cli/conf.d/20-xdebug.ini

# restart apache to apply updated config
sudo systemctl restart apache2

# install moodle
php $MOODLE_PARENT_DIRECTORY/moodle/admin/cli/install.php --lang=DE --wwwroot=http://localhost --dataroot=$MOODLE_PARENT_DIRECTORY/moodledata --dbtype=mariadb --dbhost=127.0.0.1 --dbport=3312 --dbuser=moodle --dbpass=moodle --dbname=moodle --fullname=fullname --shortname=shortname --adminpass=pass --adminemail=admin@blub.blub --non-interactive --agree-license

# setup for plugins
git clone https://github.com/Glutamat42/moodle-docker /tmp/moodle-docker
cp -r /tmp/moodle-docker/opt/adler/moodle/adler_setup $MOODLE_PARENT_DIRECTORY/moodle/
rm -rf /tmp/moodle-docker
php $MOODLE_PARENT_DIRECTORY/moodle/adler_setup/setup.php --first_run=true --user_name=student,manager --user_password='Student1234!1234,Manager1234!1234' --user_role=manager,false --develop_dont_install_plugins=true

# moodle config.php
echo "
//=========================================================================
// 7. SETTINGS FOR DEVELOPMENT SERVERS - not intended for production use!!!
//=========================================================================

// configure phpunit
$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '$MOODLE_PARENT_DIRECTORY/moodledata_phpu';
// $CFG->phpunit_profilingenabled = true; // optional to profile PHPUnit runs.

// Force a debugging mode regardless the settings in the site administration
@error_reporting(E_ALL | E_STRICT); // NOT FOR PRODUCTION SERVERS!
@ini_set('display_errors', '1');    // NOT FOR PRODUCTION SERVERS!
$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
// $CFG->debugdisplay = 1;             // NOT FOR PRODUCTION SERVERS!

// Force result of checks used to determine whether a site is considered "public" or not (such as for site registration).
// $CFG->site_is_public = false;

# disable some caching (recommended by moodle introduction course)
$CFG->langstringcache = 0;
$CFG->cachetemplates = 0;
$CFG->cachejs = 0;
" >> $MOODLE_PARENT_DIRECTORY/moodle/config.php

echo moodle login data: username: admin password: pass
echo db root pass: password

```

### uninstall script
To reset the environment run the following script. 
It will not undo all changes made by the installation script.

```bash
#!/bin/bash
MOODLE_PARENT_DIRECTORY=/home/markus

sudo rm -r $MOODLE_PARENT_DIRECTORY/moodledata $MOODLE_PARENT_DIRECTORY/moodledata_phpu
sudo rm $MOODLE_PARENT_DIRECTORY/moodle/config.php
sudo docker stop moodle_mariadb && docker rm moodle_mariadb
```



## Evaluation of different approaches to develop for moodle on windows
**Running moodle webserver on Windows**: All approaches where the webserver runs on Windows have the common problem that the performance is very bad. 
The following variants of this approach were tested: [Moodle on Windows](https://docs.moodle.org/402/de/Vollst%C3%A4ndiges_Installationspaket_f%C3%BCr_Windows), own setup with XAMPP with database from XAMPP (DB had problem starting sometimes) and DB in WSL.

**Running moodle webserver in WSL**: This approach works well. This is the approach I followed the most time. 
Windows 10 had a bug where WSL hung up regularly (probably caused by switching monitor configuration).

**Running moodle in docker**: In theory a well working approach. Should provide the same performance as the WSL approach and setup should be easier.
In practice there were too many problems with this approach.

### docker compose approach
These are my notes for the docker-compose approach. It never fully worked. Just here for future reference.

**IMPORTANT** Run all docker commands from inside WSL. It will not work from windows because bind mounts to WSL filesystem are broken from windows.
**NOTE** It might be required to set automatic line ending conversion to false or auto in git config. Otherwise, the scripts might not work (not yet tested).

#### prepare container
1) Disable Plugin installation in docker compose: `DEVELOP_DONT_INSTALL_PLUGINS: false`
2) Start container: `docker-compose up -d`
2) setup xdebug. Run once after container is up: `docker exec moodle-docker-moodle-1 /opt/adler/setup_xdebug.sh`. It is not save (aka tested) tun run this script multiple times.

#### setup PHPStorm
1) setup docker: Settings -> Build, Execution, Deployment -> Docker -> add new
- choose WSL
- add Mapping: /opt/bitnami/moodle (Virtual machine path) -> /home/markus/moodle (Local path)

2) setup PHP interpreter: Settings -> PHP -> CLI interpreter -> 2 dots ->
- add new -> From docker, ...
    - choose Docker compose
    - Select docker compose file
    - Select service moodle
    - press ok
- now choose
    - Lifecycle: Connect to existing container
    - again ok
- now ...
    - Path mappings -> folder icon
    - add new: not exactly sure what to add there, i think something like \\wsl$\Ubuntu\home\markus\moodle -> /bitnami/moodle \
      there is an existing mapping with the same remote path, but thats ok
    - press ok
- and ok

- set project default interpreter to new interpreter

3) Start debugging (create debug profile)

This approach uses a bind mount from the default WSL instance to the docker container. It has some disadvantages:
- PHPStorm can only modify files after changing the permissions of the moodle folder with `sudo chmod -R 777 moodle`. 
This is likely error-prone as the might be different reasons why the permissions (of some files) might change.
- Container can only be started from inside WSL. If run from windows, the bind mount will mount some other directory.

#### setup PHPStorm (alternative)
- use volume instead of bind mount
- path mapping `//wsl.localhost/docker-desktop-data/data/docker/volumes/moodle-docker_moodle_moodle/_data` -> `/bitnami/moodle`
- open `//wsl.localhost/docker-desktop-data/data/docker/volumes/moodle-docker_moodle_moodle/_data` as project in phpstorm

Problems and workarounds with this approach:
- Default open dialog can't open docker-desktop-data folder (WTF how stupid is this). \
  Workaround: Enable new dialog (Help -> Edit Custom Properties -> add `ide.ui.new.file.chooser=true` to the file)
- Terminal can't be opened in docker desktop WSL instance. \
  Workaround: Open terminal in another WSL instance. Settings -> Tools -> Terminal -> Shell path: `wsl.exe -d Ubuntu`

#### files
setup script
```bash
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
} >> /opt/bitnami/php/etc/conf.d/xdebug.ini
```

docker-compose.yml
```yaml
version: '3'
services:
#  test:
#    image: debian
#    command: sleep infinity
#    volumes:
#      - /home/markus/moodle:/moodle
  moodle:
    build:
      context: .
      args:
        PLUGIN_VERSION: main
        MOODLE_VERSION: 4.2
    ports:
      - '8000:8080'
    environment:
      PHP_OUTPUT_BUFFERING: 8192
      PHP_POST_MAX_SIZE: 2048M
      PHP_UPLOAD_MAX_FILESIZE: 2048M
      MOODLE_DATABASE_HOST: db_moodle
      MOODLE_DATABASE_PORT_NUMBER: 3306
      MOODLE_DATABASE_USER: moodle
      MOODLE_DATABASE_PASSWORD: moodle
      MOODLE_DATABASE_NAME: moodle
      BITNAMI_DEBUG: true
      USER_NAME: student,manager
      USER_PASSWORD: Student1234!1234,Manager1234!1234
      USER_ROLE: manager,false
      DEVELOP_DONT_INSTALL_PLUGINS: true
    volumes:
      - moodle_moodle:/bitnami/moodle
#      - /home/markus/moodle:/bitnami/moodle
      - moodle_moodledata:/bitnami/moodledata
      - moodle_moodledata_phpu:/bitnami/moodledata_phpu
    depends_on:
      - db_moodle
#    restart: unless-stopped

  db_moodle:
    image: docker.io/bitnami/mariadb:10.6
    environment:
      MARIADB_USER: moodle
      MARIADB_PASSWORD: moodle
      MARIADB_ROOT_PASSWORD: root_pw
      MARIADB_DATABASE: moodle
      MARIADB_CHARACTER_SET: utf8mb4
      MARIADB_COLLATE: utf8mb4_unicode_ci
    volumes:
      - db_moodle_data:/bitnami/mariadb
    restart: unless-stopped

  phpmyadmin:
    image: phpmyadmin
    ports:
      - 8090:80
    environment:
      PMA_USER: root
      PMA_PASSWORD: abcd
      PMA_HOSTS: db_moodle
    restart: unless-stopped

volumes:
  moodle_moodledata_phpu:
    driver: local
  moodle_moodledata:
    driver: local
  moodle_moodle:
    driver: local
  db_moodle_data:
    driver: local
```

#### working/not working
**Known working**:
- running script from php storm -> debug works

**Known not working**:
- ~there was an overlay fs over /bitnami/moodle, therefore the files in phpstorm do not match the ones in container~ (not reproducable)
- starting docker container from windows \
it will create a bind mount to `\\wsl.localhost\docker-desktop\tmp\docker-desktop-root\containers\services\02-docker\rootfs\home\markus\moodle` which is useless.
- PHPStorm file watcher made problems at least the 2nd scenario (volume for moodle directory) 

- debugging webbrowser requests

**known problems**:
- PHPStorm runs php as root user. This causes problems with the permissions of the files. [potential workaround](https://youtrack.jetbrains.com/issue/WI-57044/Change-user-for-docker-compose-interpreter)


TODO
- automatisch berechtigungen setzen dass phpstorm die dateien bearbeiten kann
- debugging webbrowser requests
- moodle config.php debug configuration

