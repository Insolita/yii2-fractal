# yii2-fractal   Beta

![yii2-fractal](https://github.com/Insolita/yii2-fractal/workflows/yii2-fractal/badge.svg)

The set of utils and actions for prepare Rest API accordingly JSON:Api https://jsonapi.org/format/
With https://fractal.thephpleague.com

### Installation

`composer require insolita/yii2-fractal`

### Usage

1. Add class `insolita\fractal\JsonApiBootstrap` to the ['bootstrap' section] of api application config
   (or update application config manually with same changes as in `JsonApiBootstrap` class )
   see [tests/testapp/config/api.php](./tests/testapp/config/api.php).
  
2. Create your controller classes by extending `JsonApiController` or `JsonApiActiveController` which contains predefined
   CRUD actions.
   See examples at [tests/testapp/controllers](./tests/testapp/controllers).
 
['bootstrap' section]: https://www.yiiframework.com/doc/guide/2.0/en/runtime-bootstrapping
 
 ### Testing
 
  - Clone project
  - Run `make up` 
  - Run once `make installdocker`
  - Run `make testdocker` or `make cli` and inside docker env `make test`
  
