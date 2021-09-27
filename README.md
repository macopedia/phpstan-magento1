# PHPStan Extension for Magento 1 / OpenMage MageLTS

Extension for [PHPStan](https://github.com/phpstan/phpstan) to allow analysis of Magento 1 code.
It doesn't require db connection to run.


## Usage

Add `phpstan.neon` to your Magento 1 project.

Make sure it has

```neon
includes:
    - vendor/macopedia/phpstan-magento1/extension.neon
```

Then run

```bash
composer require --dev macopedia/phpstan-magento1
```

## Alternative Magento path

By default this extension assumes the Magento directory is `%currentWorkingDirectory%/htdocs`.
You can adapt the path by changing the `magentoRootPath` parameter in the phpstan.neon file.
Add to the project's phpstan.neon:

```neon
parameters:
    magentoRootPath: %currentWorkingDirectory%/htdocs

```

## Example configuration file for analysing Magento Module

```neon
includes:
    - vendor/macopedia/phpstan-magento1/extension.neon
parameters:
    magentoRootPath: %currentWorkingDirectory%/htdocs
    paths:
        - %currentWorkingDirectory%/path/to/magento/app/code/local
    autoload_files:
        - %currentWorkingDirectory%/path/to/magento/app/Mage.php
    paths:
        #lets start small with just our extensions
        - %currentWorkingDirectory%/app/code/local/VendorName/ModuleName
    excludePaths:
        - */Vendor/ModuleName/SomePathToExclude/*
    level: 0
```

# Known Issues

## Data/SQL scripts can't be tested

Since these scripts use a presumed $this variable due to being included from a setup class, work is needed to:

* work out the correct setup class
* somehow make phpstan aware of it for the file

## Magento fluent interface classes aren't fluent for sub-classes

This causes subsequent calls to the class object to assume the scope of the super-class that defined the return type.

This is due to their PHPDoc not using the up to date way of specifying fluency with subclasses using "$this" as the type.

Options to resolve:

 * Avoid using the fluent interface on these classes
 * Patch Magento code to use $this as the return type
 * Define @method PHPDoc for the called methods in the subclass
 * Add the error to the parameters.ignoreErrors phpstan configuration to ignore it - this loses the ability to properly type the subseqent methods of the fluent interface

## Mage_Core_Model_Abstract::load $id is not an integer

This is due to an inadequate PHPDoc of the load method of the Mage_Core_Model_Abstract class. It should have used a "mixed" type to support when a field is specified as the 2nd argument.

PHPStan extensions have no way of altering existing method parameters.

Options to resolve:

 * Patch the Magento code to use mixed as the $id parameter type
 * If available in the chain of super-classes, use the loadBy{Field} method, which has the correct parameter typing
 * Define @method PHPDoc in your class (which extends Mage_Core_Model_Abstract or a subclass of it) with the correct parameter types
 * Add the error to the parameters.ignoreErrors phpstan configuration to ignore it
