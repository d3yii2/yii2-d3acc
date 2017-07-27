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
    const PLAYER_ACC        = 4;
    const EXPENSES          = 10;
    const FOND_PLAYGROUND   = 7;

    /**
     * get player  account
     * @param int $personId
     * @return AcRecAcc
     */
    public static function player($personId)
    {
        return AcRecAcc::getAcc(self::PLAYER_ACC, ['d3p_person' => $personId]);
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
                ['d3p_person' => $personId, 'pk_playground' => $playgroundId]);
    }    
}
 
```

Define account plan by creating acc class

Add definition record in tables
