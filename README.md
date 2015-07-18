# WobbleCode - SMS Counter for PHP

Character counter for SMS Messages

[![Build Status](https://travis-ci.org/wobblecode/sms-counter-php.svg?branch=master)](https://travis-ci.org/wobblecode/sms-counter-php)

##Usage

```php
use WobbleCode\SMSCounter\SMSCounter;

$smsCounter = new SMSCounter;
$smsCounter->count('some-string-to-be-counted');
```

which returns
```
stdClass Object
(
	[encoding]    => GSM_7BIT
	[length]      => 25
	[per_message] => 160
	[remaining]   => 135
	[messages]    => 1
)
```

##Installation

`sms-counter-php` is available via [composer](http://getcomposer.org) on [packagist](https://packagist.org/packages/wobblecode/sms-counter-php).

```json
{
    "require": {
        "wobblecode/sms-counter-php": "dev-master"
    }
}
```

##License

SMS Counter (PHP) is released under the [MIT License](LICENSE-MIT.md)

###Mentions

Original insipration : [danxexe/sms-counter](https://github.com/danxexe/sms-counter)
Original fork form: [acpmasquerade/sms-counter-php]
