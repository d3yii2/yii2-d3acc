[![Latest Stable Version](https://poser.pugx.org/d3yii2/yii2-d3acc/v/stable)](https://packagist.org/packages/d3yii2/yii2-d3acc)
[![Total Downloads](https://poser.pugx.org/d3yii2/yii2-d3acc/downloads)](https://packagist.org/packages/d3yii2/yii2-d3acc)
[![Latest Unstable Version](https://poser.pugx.org/d3yii2/yii2-d3acc/v/unstable)](https://packagist.org/packages/d3yii2/yii2-d3acc)
[![Code Climate](https://img.shields.io/codeclimate/github/d3yii2/yii2-d3acc.svg)](https://codeclimate.com/github/d3yii2/yii2-d3acc)
[![License](https://poser.pugx.org/d3yii2/yii2-d3acc/license)](https://packagist.org/packages/d3yii2/yii2-d3acc)

Accounting
==========
This Yii2 module provides support for balance accounting (bookkeeping) system based on debit and credit principles.
Provide additinal functionality:
* periods (closing period and period balance)
* dynamicly creating accounts attached one or more tables

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist d3yii2/yii2-d3acc "*"
```

or add

```
"d3yii2/yii2-d3acc": "*"
```

to the require section of your `composer.json` file.

push migration

Usage
-----

Define account plan by creating acc class

Add definition record in tables
