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

DB structure
------------

[DB Schema](https://github.com/d3yii2/yii2-d3acc/blob/master/doc/DbSchema.png "DB Schema")

[DB Schema]: https://github.com/d3yii2/yii2-d3acc/blob/master/doc/DbSchema.png 

Account definition
------------------

Create object acc

```php
use \d3acc\models\AcRecAcc;
use Yii;
/**
 * Description of acc
 *
 * @author Dealer
 */
 class acc
{
    const MONTH_PERIOD = 1;

    const PLAYER_ACC        = 4;
    const EXPENSES          = 10;
    const FOND_PLAYGROUND   = 7;
    
    acc::CODE_CRD_PLAYGROUND = 'CreditPlaygound';

    /**
     * get player  account
     * @param int $personId
     * @return AcRecAcc
     */
    public static function player($personId)
    {
        return AcRecAcc::getAcc(self::PLAYER_ACC, ['person' => $personId]);
    }

    /**
     * get expenses  account
     * @return AcRecAcc
     */
    public static function expenses()
    {
        return AcRecAcc::getAcc(self::EXPENSES);
    }
    
    /**
     * get for player playground account
     * @param int $personId
     * @param int $playgroundId
     * @return AcRecAcc
     */
    public static function fondPlayground($personId, $playgroundId)
    {
        return AcRecAcc::getAcc(self::FOND_PLAYGROUND,
                ['person' => $personId, 'playground' => $playgroundId]);
    }    
}
 
```

Transaction registration
------------------------

```php

       /**
        * player accounts
        */
       $recAccPPG    = acc::playerPlayground($person_id, $playground_id);
       $recAccPlayer = acc::player($person_id);
       $day = date('Y-m-d');
       $tran = AcTran::registre($recAccPlayer, $recAccPPG, $personAmt,
               $day, acc::MONTH_PERIOD, acc::CODE_CRD_PLAYGROUND);


```

Periods
-------

```php
use d3acc\models\AcPeriod;
$acPeriod = AcPeriod::getActivePeriod(acc::MONTH_PERIOD))

//close period
\d3acc\components\PeriodMonth::close(acc::MONTH_PERIOD);


```


Transactions
------------

```php
 $recAccPlayer = acc::player($person_id);
 $data = AcTran::accPeriodTran($recAccPlayer, $acPeriod);

```


Balance
------



```php
 $filter  = ['playground' => $playgroundId]
 $playgroundAllPersonBalance = AcTran::accBalanceFilter(acc::FOND_PLAYGROUND, $acPeriod,$filter);
 
 $filter  = ['person' => $personId]
 $personAllPlaygroundsBalance = AcTran::accBalanceFilter(acc::FOND_PLAYGROUND, $acPeriod,$filter);
 
 $allPlaygroundsAllPersonBalance = AcTran::accBalanceFilter(acc::FOND_PLAYGROUND, $acPeriod,[]);


```

Define account plan by creating acc class

Add definition record in tables


Migrations with AccConstructor
------
```php
$constructor = new AccConstructor();
```
Load existind or create new account (ac_account table) for session
```php
$constructor->load($accId);
$constructor->create($code, $name);
```
Add new account dimension (ac_def table) for loaded/created account
```php
$definition = $constructor->addDimension($table, $pkField);
```
Add new extended-account (ac_rec_acc table) for loaded/created account
```php
$extAccount = $constructor->addExtendedAccount();
```
Add new dimension value (ac_rec_ref table) and recalculate label for given extended-account (ac_rec_acc table)
```php
$constructor->addDimensionRecAcc($extAccount->id, $definition->id, $pk_value);
```
