# Patchwork interceptor for PHP AOP

Interceptor for [php-aop](http://aop.io) using [stream wrapper](http://php.net//manual/en/class.streamwrapper.php) of PHP via the [Patchwork](https://github.com/antecedent/patchwork) package.

This interceptor was created only for _R&D_ concerning the possibility of going through a protocol handler and PHP stream (stream wrapper), it does not support the interception of "around" kind, nor the interception of properties.

Use just for your own _R&D_ (or playing) or to inspire you when creating an interceptor.

The lib _Patchwork_ is not being created for use of AOP, so this interceptor has no future. However, the PHP _stream wrapper_ to create an interceptor is a viable solution!


## Getting Started

### Install pecl-aop-interceptor

Download [patchwork-interceptor](https://github.com/aop-io/patchwork-interceptor/archive/master.zip) (and configure your autoloader) or use composer `require: "aop-io/patchwork-interceptor"`.


### Usage

```php
use Aop\Aop;

// Init
$aop = new Aop([ 'php_interceptor' => '\PatchworkInterceptor\PatchworkInterceptor']);
```

The usage of the [PHP-AOP](http://aop.io/en/php/doc) abstraction layer is documented on [AOP.io](http://aop.io).


## License

[MIT](https://github.com/aop-io/patchwork-interceptor/blob/master/LICENSE) (c) 2014, Nicolas Tallefourtane.


## Author

| [![Nicolas Tallefourtane - Nicolab.net](http://www.gravatar.com/avatar/d7dd0f4769f3aa48a3ecb308f0b457fc?s=64)](http://nicolab.net) |
|---|
| [Nicolas Talle](http://nicolab.net) |
| [![Make a donation via Paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PGRH4ZXP36GUC) |
