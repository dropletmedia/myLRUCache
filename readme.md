# LRUCache

> Implementation of a Least Recently Used Cache using [Memcached](https://memcached.org/)

## Installation (debian)

### Install memcached and client

```
$ sudo apt-get install memcached
$ sudo apt-get install php-memcached
```

NB Depending upon your environment you may need to configure your firewall or memcached to prevent port 11211 being exposed to the interwebs...

### Run tests

```
$ composer install
$ composer dump-autoload
$ ./vendor/bin/phpunit tests
```

## Credits

Inspiration from [this repo](https://github.com/rogeriopvl/php-lrucache)
