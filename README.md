# everimg

[![Build Status](https://travis-ci.org/gonejack/everimg.svg?branch=master)](https://travis-ci.org/gonejack/everimg)


### Preparing

```
# clone code
git clone git@github.com:gonejack/everimg.git
cd everimg


# get composer (dependencies manage tool)
wget -O composer.phar https://getcomposer.org/composer-stable.phar


# install dependencies
php ./composer.phar install


# get Account & Token at https://dev.evernote.com/doc/articles/dev_tokens.php
# talk with the customer support for enabling developer token of your evernote account.


# new config
cp ./conf/dev.ini.template ./conf/dev.ini

# edit your config with your editor.
```



### Developing

##### Compile (remember to run this after edits)

```
php ./composer.phar dump-autoload
```

##### Run

```
export CONF_FILE=./conf/dev.ini

php ./index.php
```

##### Test

```
wget -O phpunit.phar https://phar.phpunit.de/phpunit-7.4.3.phar

php ./phpunit.phar --configuration ./phpunit.xml
```



### Releasing & Deploying

##### Packaging

```
wget -O phar-composer.phar https://github.com/clue/phar-composer/releases/download/v1.1.0/phar-composer-1.1.0.phar
php -d phar.readonly=off ./phar-composer.phar build . ./bin/everimg.phar
```

##### Boot

```
env CONF_FILE=./conf/dev.ini ./bin/everimg.phar
```



### Suggestions

##### Service management

Supervisor is recommend for service management, take a look on ./deploy/supervisor.ini.



### Cautions

##### For public server

Don't leak your *.phar files through browser downloads.
