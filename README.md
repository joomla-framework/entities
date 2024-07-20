# The Entity Package [![Build Status](https://ci.joomla.org/api/badges/joomla-framework/entities/status.svg?ref=refs/heads/3.x-dev)](https://ci.joomla.org/joomla-framework/entities)

[![Latest Stable Version](https://poser.pugx.org/joomla/entities/v/stable)](https://packagist.org/packages/joomla/entities)
[![Total Downloads](https://poser.pugx.org/joomla/entities/downloads)](https://packagist.org/packages/joomla/entities)
[![Latest Unstable Version](https://poser.pugx.org/joomla/entities/v/unstable)](https://packagist.org/packages/joomla/entities)
[![License](https://poser.pugx.org/joomla/entities/license)](https://packagist.org/packages/joomla/entities)

## Introduction

The *Entities* package is an simple redesigned Active Record pattern for working with your database.
Each database table has a corresponding Entity which is used to interact with that table. This is based partially from
the CMS JTable Active Record pattern and from the various improvements that have been made through the PHP Community
since.

## Installation via Composer

Add `"joomla/entities": "3.0-dev"` to the require block in your composer.json and then run `composer install`.

```json
{
	"require": {
		"joomla/entities": "3.0-dev"
	}
}
```

Alternatively, you can simply run the following from the command line:

```sh
composer require joomla/entities "3.0-dev"
```

If you want to include the test sources and docs, use

```sh
composer require --prefer-source joomla/entities "3.0-dev"
```
