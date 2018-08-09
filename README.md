# LogHero PHP SDK

## Developer Setup

Install PHP:
```
sudo dnf install php-cli php-json
```

Download the composer-setup script https://getcomposer.org/download/ and run:

```
php composer-setup.php --install-dir /home/user/.local/bin/ --filename=composer
```

Install the required PHP extensions and run 'composer install' to load the PHP dependencies:

```
sudo dnf install php-mbstring php-xml php-posix
composer install
```
