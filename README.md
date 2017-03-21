# Instasent - SMS Counter for PHP

Character counter for SMS Messages

[![Build Status](https://img.shields.io/travis/instasent/sms-counter-php.svg?style=flat-square)](https://travis-ci.org/instasent/sms-counter-php)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/0a2fa87a-0287-46f6-b8b5-818b44a2b9f9.svg?style=flat-square)](https://insight.sensiolabs.com/projects/0a2fa87a-0287-46f6-b8b5-818b44a2b9f9)

## Usage

```php
use Instasent\SMSCounter\SMSCounter;

$smsCounter = new SMSCounter();
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

You can sanitize your text to be a valid GSM 03.38 charset

```php
use Instasent\SMSCounter\SMSCounter;

$smsCounter = new SMSCounter();
$smsCounter->sanitizeToGSM('dadáó'); //return dadao
```

## Installation

`sms-counter-php` is available via [composer](http://getcomposer.org) on [packagist](https://packagist.org/packages/instasent/sms-counter-php).

```json
{
    "require": {
       "instasent/sms-counter-php": "^0.4"
    }
}
```

## License

SMS Counter (PHP) is released under the [MIT License](LICENSE-MIT.md)

### Mentions

* Original idea : [danxexe/sms-counter](https://github.com/danxexe/sms-counter)
* Fork Idea from: [acpmasquerade/sms-counter-php](https://github.com/acpmasquerade/sms-counter-php)
