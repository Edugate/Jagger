# HOWTO Install and Configure Jagger Federation Registry on Debian based Linux Distribution

## Table of Contents

1.  [Requirements](#requirements)
    1.  [Hardware](#hardware)
    2.  [Software](#software)
    3.  [Others](#others)
2.  [Notes](#notes)
3.  [Configure the environment](#configure-the-environment)
4.  [Configure APT Mirror](#configure-apt-mirror)
5.  [Install Dependencies](#install-dependencies)
6.  [Install MySQL Server](#install-mysql-server)
    1. [Protect MySQL Server](#protect-mysql-server)
7.  [Install Apache Web Server](#install-apache-web-server)
8.  [Configure Apache Web Server](#configure-apache-web-server)
9.  [Install Jagger](#install-jagger)
10. [Configure Jagger database](#configure-jagger-database)
11. [Configure Jagger](#configure-jagger)
12. [Populate database tables](#populate-database-tables)
13. [Code fixes](#code-fixes)
14. [Configure Apache Jagger VirtualHost](#configure-apache-jagger-virtualhost)
15. [Setup Jagger Registry](#setup-jagger-registry)
16. [Documentation](#documentation)
17. [Authors](#authors)
18. [Thanks](#thanks)

## Requirements

### Hardware

-   CPU: 2 Core (64 bit)
-   RAM: 4 GB
-   HDD: 10 GB
-   OS:
    - Debian 12 *(under testing)*
    - Ubuntu 22.04 (tested)

### Software

-   Apache Web Server (*\<= 2.4*)
-   OpenSSL (*\<= 3.0.2*)
-   Shibboleth Service Provider (*\<= 3.4.1*) - Optionally
-   PHP (*\<= 8.1*)

### Others

-   SSL Credentials: HTTPS Certificate & Private Key
-   Logo:
    -   size: 64px by 350px wide and 64px by 146px high
    -   format: PNG
    -   style: with a transparent background

[TOC](#table-of-contents)

## Notes

This HOWTO uses `example.org` and `jagger.example.org` as example values.

Please remember to **replace all occurencences** of:

-   the `example.org` value with the domain name
-   the `jagger.example.org` value with the Full Qualified Domain Name of the Jagger instance.

[TOC](#table-of-contents)

## Configure the environment

1.  Become ROOT:

    ``` text
    sudo su -
    ```

2.  Be sure that your firewall **is not blocking** the traffic on port **443** and **80** for the Jagger server.

3.  Set the SP hostname:

    **!!!ATTENTION!!!**: Replace `jagger.example.org` with your SP Full Qualified Domain Name and `<HOSTNAME>` with the Jagger hostname

    -   ``` text
        echo "<YOUR-SERVER-IP-ADDRESS> jagger.example.org <HOSTNAME>" >> /etc/hosts
        ```

    -   ``` text
        hostnamectl set-hostname <HOSTNAME>

[TOC](#table-of-contents)

## Configure APT Mirror

Debian Mirror List: <https://www.debian.org/mirror/list>

Ubuntu Mirror List: <https://launchpad.net/ubuntu/+archivemirrors>

Example with the Consortium GARR italian mirrors:

1.  Become ROOT:

    ``` text
    sudo su -
    ```

2.  Change the default mirror:

    -   Debian 12 - Deb822 file format:

        ``` text
        bash -c 'cat > /etc/apt/sources.list.d/garr.sources <<EOF
        Types: deb deb-src
        URIs: https://debian.mirror.garr.it/debian/
        Suites: bookworm bookworm-updates bookworm-backports
        Components: main

        Types: deb deb-src
        URIs: https://debian.mirror.garr.it/debian-security/
        Suites: bookworm-security
        Components: main
        EOF'
        ```

    -   Ubuntu:

        ``` text
        bash -c 'cat > /etc/apt/sources.list.d/garr.list <<EOF
        deb https://ubuntu.mirror.garr.it/ubuntu/ jammy main
        deb-src https://ubuntu.mirror.garr.it/ubuntu/ jammy main
        EOF'
        ```

3.  Update packages:

    ``` text
    apt update && apt-get upgrade -y --no-install-recommends
    ```

[TOC](#table-of-contents)

## Install Dependencies

``` text
sudo apt install fail2ban vim wget ca-certificates openssl ntp git --no-install-recommends
```

[TOC](#table-of-contents)

## Install MySQL Server

``` text
sudo apt install default-mysql-server --no-install-recommends
```
[TOC](#table-of-contents)

### Protect MySQL Server

``` text
sudo mysql_secure_installation
```
   
On Ubuntu 22.04:
  - Would you like to setup VALIDATE PASSWORD component? **No**
  - Remove anonymous users? **Yes**
  - Disallow root login remotely? **Yes**
  - Remove test database and access to it? **Yes**
  - Reload privilege tables now? **Yes**

On Debian 12:
  - Root password: **empty or a desired value for the root password of MariaDB**
  - Switch to unix_socket: **Y**
  - Change the root password? **N**
  - Remove anonymous users? **Y**
  - Disallow root login remotely? **Y**
  - Remove test database and access to it? **Y**
  - Reload privilege tables now? **Y**

[TOC](#table-of-contents)

## Install Apache Web Server

The Apache HTTP Server will be configured for SSL offloading.

``` text
sudo apt install apache2
```

[TOC](#table-of-contents)

## Configure Apache Web Server

1.  Become ROOT:

    ``` text
    sudo su -
    ```

2.  Create the DocumentRoot:

    -   ``` text
        mkdir /var/www/html/$(hostname -f)
        ```

    -   ``` text
        chown -R www-data: /var/www/html/$(hostname -f)
        ```

    -   ``` text
        echo '<h1>It Works!</h1>' > /var/www/html/$(hostname -f)/index.html
        ```

3.  Put SSL credentials in the right place:

    > According to [NSA and NIST](https://www.keylength.com/en/compare/), RSA with 3072 bit-modulus is the minimum to protect up to TOP SECRET over than 2030.

    -   HTTPS Server Certificate (Public Key) inside `/etc/ssl/certs/$(hostname -f).crt`

    -   HTTPS Server Key (Private Key) inside `/etc/ssl/private/$(hostname -f).key`

    -   Add CA Cert into `/etc/ssl/certs/ca-cert.pem`

4.  Configure the right privileges for the SSL Certificate and Private Key used by HTTPS:

    -   ``` text
        chmod 400 /etc/ssl/private/$(hostname -f).key
        ```

    -   ``` text
        chmod 644 /etc/ssl/certs/$(hostname -f).crt
        ```

    (`$(hostname -f)` will provide your SP Full Qualified Domain Name)

5.  Verify that SSL certificate file matches the CA certificate file with:

    - ``` text
      openssl verify --CAfile /etc/ssl/certs/ssl-ca.pem /etc/ssl/certs/$(hostname -f).crt
      ```

    and make sure you get an `OK` as an outcome.

6.  Enable the required Apache modules and the virtual hosts:

    -   ``` text
        a2enmod ssl rewrite headers alias include negotiation
        ```

    -   ``` text
        a2dissite 000-default.conf default-ssl
        ```

    -   ``` text
        systemctl restart apache2.service
        ```

[TOC](#table-of-contents)

## Install Jagger

1.  Become ROOT:

    ``` text
    sudo su -
    ```

2. Install packages required:

   - Ubuntu 22.04

     - ```txt
       apt install curl php php-common php8.1-opcache php-gd php-curl php-mysql php-intl php-xml php-mbstring php-xmlrpc php-soap php-bcmath php-cli php-zip php-gearman php-apcu php-memcached python-pip default-jdk gearman-job-server --no-install-recommends
       ```

   - Debian 12:

     - ```txt
       apt install curl php php-common php8.2-opcache php-gd php-curl php-mysql php-intl php-xml php-mbstring php-xmlrpc php-soap php-bcmath php-cli php-zip php-gearman php-apcu php-memcached python-pip default-jdk gearman-job-server --no-install-recommends
       ```

2. Install Composer:

   - ```txt
     curl -sS https://getcomposer.org/installer | php
     ```

   - ```txt
     cp composer.phar /usr/local/bin/composer
     ```

3. Install CodeIgniter:
   
   - ```txt
     wget https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.tar.gz -O /opt/codeigniter-3.1.13.tar.gz
     ```
     
   - ```txt
     tar zxf /opt/codeigniter-3.1.13.tar.gz
     ```

   - ```txt
     mv /opt/CodeIgniter-3.1.13 /opt/codeigniter
     ```

4. Download Jagger:

   - ```txt
     git clone https://github.com/Edugate/Jagger /opt/rr3
     ```
     
5. Install required third parties libraries:

   - ```txt
     vim /opt/rr3/application/composer.json
     ```
     and replace `"mtdowling/cron-expression": "1.1.*",` with `"dragonmantank/cron-expression": "3.*",`
      
   - ```txt
     cd /opt/rr3/application ; sudo composer install
     ```

7. Configure the "index.php" file:

   - ```text
     cp /opt/codeigniter/index.php /opt/rr3/
     ```

     by setting `$system_path = "/opt/codeigniter/system"`.

[TOC](#table-of-contents)

## Configure Jagger database

```text
mysql -u root
```

- ```text
  CREATE DATABASE rr3 CHARACTER SET utf8 COLLATE utf8_general_ci;
  ```
- ```text
  CREATE USER 'rr3user'@'localhost' IDENTIFIED BY 'rr3pass';
  ```
- ```text
  GRANT ALL PRIVILEGES ON rr3.* TO rr3user@'localhost';
  ```
- ```text
  FLUSH PRIVILEGES;
  ```

[TOC](#table-of-contents)

## Configure Jagger

- ```text
  mkdir /var/log/rr3
  ```
  
- ```text
  chown www-data /var/log/rr3
  ```

- ```text
  chown www-data:www-data /opt/rr3/application /opt/rr3/application/models/Proxies
  ```
  
- ```text
  cd /opt/rr3
  ```

- ```text
  ./install.sh
  ```

- ```text
  cd /opt/rr3/application/config
  ```
  
- ```text
  cp config-default.php config.php
  ```

  `config.php` base configuration:

  - `$config['base_url'] = 'https://jagger.example.org/rr3';`
  - `$config['index_page'] = '';`
  - `$config['log_threshold'] = 1;`
  - `$config['log_path'] = '/var/log/rr3/';`
  - `$config['encryption_key'] = '<ENCRYPTION-KEY>';`

     `<ENCRYPTION-KEY>` generation:

     ```text
     tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
     ```

- ```text
  cp config_rr-default.php config_rr.php
  ```

  `config_rr.php` base configuration:

  - `$config['rr_setup_allowed'] = TRUE`  (HAS TO COME BACK to FALSE after Jagger setup)
  - `$config['site_logo'] = 'logo-default.png';`  (set filename to be used as main logo in top-left corner. File should be stored in `/opt/rr3/images/` folder.)
  - `$config['syncpass'] = <SYNCPASS>`
  
    `<SYNCPASS>` generation:
        ```text
        tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
        ```
      
  - `$config['Shib_required'] = array('Shib_mail','Shib_username');`
  - `$config['nameids'] and all its content has to be removed.`
  - `$config['gearman'] = TRUE;`
  
- ```text
  cp database-default.php database.php
  ```

  `database.php` base Configuration:
  
  - `$db['default']['username'] = 'rr3user';`
  - `$db['default']['password'] = 'rr3pass';`
  - `$db['default']['database'] = 'rr3';`
  - `$db['default']['dsn']      = 'mysql:host=127.0.0.1;port=3306;dbname=rr3';`
  
- ```text
  email-default.php email.php
  ```
  
- ```text
  memcached-default.php memcached.php
  ```

[TOC](#table-of-contents)

## Populate database tables

- ```text
  cd /opt/rr3/application
  ```

- ```text
  ./doctrine
  ```

- ```text
  ./doctrine orm:schema-tool:create
  ```

- ```text
  ./doctrine orm:generate-proxies
  ```

[TOC](#table-of-contents)

# Code fixes

Take a look to my Pull Request on: https://github.com/Edugate/Jagger/pulls

[TOC](#table-of-contents)

## Configure Apache Jagger VirtualHost

1.  Become ROOT:

    ``` text
    sudo su -
    ```

2.  Create the Virtualhost file (**PLEASE PAY ATTENTION! you need to edit this file and customize it, check the initial comment of the file**):

    ``` text
    vim /etc/apache2/sites-available/$(hostname -f).conf
    ```

    ``` text
    # This is an example Apache2 configuration for Jagger Federation Registry tool.
    #
    # Edit this file and:
    # - Adjust "jagger.example.org" with your Jagger Full Qualified Domain Name
    # - Adjust "ServerAdmin" email address
    # - Adjust "CustomLog" and "ErrorLog" with Apache log files path
    # - Adjust "SSLCertificateFile", "SSLCertificateKeyFile" and "SSLCACertificateFile" with the correct file path


    # SSL general security improvements should be moved in global settings
    # OCSP Stapling, only in httpd/apache >= 2.3.3
    SSLUseStapling on
    SSLStaplingResponderTimeout 5
    SSLStaplingReturnResponderErrors off
    SSLStaplingCache shmcb:/var/run/ocsp(128000)

    <VirtualHost *:80>
       ServerName "jagger.example.org"
       RedirectMatch permanent ^/$ /rr3
    </VirtualHost>

    <IfModule mod_ssl.c>
       <VirtualHost _default_:443>
         ServerName jagger.example.org:443
         ServerAdmin admin@example.org
         RedirectMatch permanent ^/$ /rr3

         CustomLog /var/log/apache2/jagger.example.org.log combined
         ErrorLog /var/log/apache2/jagger.example.org-error.log
         
         DocumentRoot /var/www/html/jagger.example.org
         
         SSLEngine On
         SSLProtocol All -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
         SSLCipherSuite "EECDH+ECDSA+AESGCM EECDH+aRSA+AESGCM EECDH+ECDSA+SHA384 EECDH+ECDSA+SHA256 EECDH+aRSA+SHA384 EECDH+aRSA+SHA256 EECDH+aRSA+RC4 EECDH EDH+aRSA RC4 !aNULL !eNULL !LOW !3DES !MD5 !EXP !PSK !SRP !DSS !RC4"

         SSLHonorCipherOrder on
         
         # This will disallow embedding your sp's login page within an iframe.
         <IfModule headers_module>
            Header set X-Frame-Options DENY
            # Enable HTTP Strict Transport Security with a 2 year duration
            Header always set Strict-Transport-Security "max-age=63072000;includeSubDomains;preload"
         </IfModule>
         
         SSLCertificateFile /etc/ssl/certs/jagger.example.org.crt
         SSLCertificateKeyFile /etc/ssl/private/jagger.example.org.key
         SSLCACertificateFile /etc/ssl/certs/ca-cert.pem

         Alias /rr3 /opt/rr3
         <Directory /opt/rr3>
            Require all granted

            RewriteEngine On
            RewriteBase /rr3
            RewriteCond $1 !^(Shibboleth\.sso|index\.php|logos|signedmetadata|flags|images|app|schemas|fonts|styles|images|js|robots\.txt|pub|includes)
            RewriteRule  ^(.*)$ /rr3/index.php?/$1 [L]
         </Directory>
         <Directory /opt/rr3/application>
            Order allow,deny
            Deny from all
         </Directory>

       </VirtualHost>
    </IfModule>
    ```

4. Enable the Apache2 SP Virtualhosts created:

    -   ``` text
        a2ensite $(hostname -f).conf
        ```

    -   ``` text
        systemctl restart apache2.service
        ```

5.  Check that Jagger web application works on:

    ``` text
    https://jagger.example.org
    ```

6.  Verify the strength of your SP's machine on [SSLLabs](https://www.ssllabs.com/ssltest/analyze.html).

[TOC](#table-of-contents)

## Setup Jagger Registry

Go to https://jagger.example.org/rr3/setup and create the Admin user.

After that, set to FALSE the line:

`$config['rr_setup_allowed'] = TRUE`

on `/opt/rr3/application/config/config_rr.php`

[TOC](#table-of-contents)

## Documentation

https://jagger.heanet.ie/jaggerdocadmin/index.html

[TOC](#table-of-contents)

## Authors

### Original Author

 * Marco Malavolti

[TOC](#table-of-contents)

## Thanks

https://github.com/janul
