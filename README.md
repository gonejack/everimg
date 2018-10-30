# everimg



### Preparing

##### Clone

```
git clone git@github.com:gonejack/everimg.git && cd everimg
```

##### Get composer (dependencies manager tool)

```
wget https://getcomposer.org/composer.phar
```

##### Install dependencies

```
php ./composer.phar install
```

##### Get Account & Token

```
# get developer token here, talk with the customer support for enabling developer token of your evernote account.

https://dev.evernote.com/doc/articles/dev_tokens.php
```

##### Config file

```
cp ./conf/dev.ini.template ./conf/dev.ini

# edit your config
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
wget https://phar.phpunit.de/phpunit-7.4.3.phar -o ./phpunit.phar

php ./phpunit.phar --configuration ./phpunit.xml
```





### Releasing & Deploying

##### Packaging

```
wget https://github.com/clue/phar-composer/releases/download/v1.0.0/phar-composer.phar
php -d phar.readonly=off ./phar-composer.phar build . ./bin/everimg.phar
```

##### Boot

```
env CONF_FILE=./conf/dev.ini ./bin/everimg.phar
```





### Suggestions

##### Service management

Supervisor is recommend for service management, take a look on ./doc/supervisor.ini.





### Cautions

##### For public server

Don't leak your *.phar files through browser downloads.