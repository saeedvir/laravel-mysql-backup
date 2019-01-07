# laravel mysql backup
This package will help you to backup mysql database in laravel (easy way !)

Here are a few short examples of what you can do:
<div lang="fa" dir="rtl">

## توضیحات فارسی

این پکیج برای پشتیبان گیری از دیتابیس مای اسکیوال کاربرد دارد.

می توانید از دستورات زمان بندی در کنار آن استفاده کنید و به صورت زمان بندی پشتیبان گیری کنید.

</div>

## Notice
Note that this package is in development and may have a lot of bugs at first

### How to install ?

```php
composer require saeedvir/laravel-mysql-backup
```
### How to use ?

create backup file (all tablse)
```php
php artisan mysql:backup
```

or

```php
php artisan mysql:backup table1,table2,table3,...
```

For Help :
```php
php artisan mysql:backup help
```

## Other Packages

- [Laravel Project Ghost](https://github.com/saeedvir/projectGhost)
- [Laravel Assets Optimize](https://github.com/saeedvir/laravel-assets-optimizer)

## Security

If you discover any security related issues, please email [saeed.es91@gmail.com](mailto:saeed.es91@gmail.com) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
