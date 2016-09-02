WordPress Core Autoloader for Composer
=====================================

This is a custom autoloader generator and class map loader Composer plugin for WordPress Core.

It diverges from the default Composer autoloader setup in the following ways:

* The generated autoloader is compatible with PHP 5.2. Classes containing PHP 5.3+ code will be skipped and throw warnings.
* The paths to the classes are relative to a set constant. The default constant being used is `ABSPATH`.
* The class maps can optionally be configured to be case-insensitive.

Usage
-----

In your project's `composer.json`, add the following lines:

```json
{
    "require": {
        "schlessera/composer-wp-autoload": "^1"
    },
    "scripts": {
        "post-install-cmd": [
            "WordPress\\ComposerAutoload\\Generator::dump"
        ],
        "post-update-cmd": [
            "WordPress\\ComposerAutoload\\Generator::dump"
        ],
        "post-autoload-dump": [
            "WordPress\\ComposerAutoload\\Generator::dump"
        ]
    },
    "extra": {
        "wordpress-autoloader": {
            "class-root": "ABSPATH",
            "case-sensitive": true
        },
    }
}
```

After the next update/install, you will have a `vendor/autoload_wordpress.php` file, that you can simply include and use to autoload classes within WordPress Core.

Valid "extra" Keys
------------------

You can configure the autoloader by providing `"extra"` keys under the `"wordpress-autoloader"` root key.

* __`"class-root"`__ :

    String value that is used to replace the `dirname($vendorDir)` string.
    The default is `"ABSPATH"`, to make the autoloader use the `ABSPATH` constant.

* __`"case-sensitive"`__:

    Boolean value to configure whether the classmap loader should be case-sensitive or not. The default value is `true`.

Contributing
------------

All feedback / bug reports / pull requests are welcome.

License
-------

This code is released under the MIT license.

For the full copyright and license information, please view the [`LICENSE`](LICENSE) file distributed with this source code.
