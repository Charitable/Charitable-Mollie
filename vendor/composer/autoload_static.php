<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8672133ad7c4b73b0cafac78212035cc
{
    public static $files = array (
        '3937806105cc8e221b8fa8db5b70d2f2' => __DIR__ . '/..' . '/wp-cli/mustangostang-spyc/includes/functions.php',
        'be01b9b16925dcb22165c40b46681ac6' => __DIR__ . '/..' . '/wp-cli/php-cli-tools/lib/cli/cli.php',
        'ffb465a494c3101218c4417180c2c9a2' => __DIR__ . '/..' . '/wp-cli/i18n-command/i18n-command.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WP_CLI\\I18n\\' => 12,
        ),
        'S' => 
        array (
            'Symfony\\Component\\Finder\\' => 25,
        ),
        'P' => 
        array (
            'Peast\\test\\' => 11,
            'Peast\\' => 6,
        ),
        'M' => 
        array (
            'Mustangostang\\' => 14,
        ),
        'G' => 
        array (
            'Gettext\\Languages\\' => 18,
            'Gettext\\' => 8,
        ),
        'C' => 
        array (
            'Charitable\\Pro\\Mollie\\' => 22,
            'Charitable\\Extensions\\Activation\\' => 33,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WP_CLI\\I18n\\' => 
        array (
            0 => __DIR__ . '/..' . '/wp-cli/i18n-command/src',
        ),
        'Symfony\\Component\\Finder\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/finder',
        ),
        'Peast\\test\\' => 
        array (
            0 => __DIR__ . '/..' . '/mck89/peast/test/Peast',
        ),
        'Peast\\' => 
        array (
            0 => __DIR__ . '/..' . '/mck89/peast/lib/Peast',
        ),
        'Mustangostang\\' => 
        array (
            0 => __DIR__ . '/..' . '/wp-cli/mustangostang-spyc/src',
        ),
        'Gettext\\Languages\\' => 
        array (
            0 => __DIR__ . '/..' . '/gettext/languages/src',
        ),
        'Gettext\\' => 
        array (
            0 => __DIR__ . '/..' . '/gettext/gettext/src',
        ),
        'Charitable\\Pro\\Mollie\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Charitable\\Extensions\\Activation\\' => 
        array (
            0 => __DIR__ . '/..' . '/wpcharitable/charitable-extension-activation/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'c' => 
        array (
            'cli' => 
            array (
                0 => __DIR__ . '/..' . '/wp-cli/php-cli-tools/lib',
            ),
        ),
        'W' => 
        array (
            'WP_CLI' => 
            array (
                0 => __DIR__ . '/..' . '/wp-cli/wp-cli/php',
            ),
        ),
        'R' => 
        array (
            'Requests' => 
            array (
                0 => __DIR__ . '/..' . '/rmccue/requests/library',
            ),
        ),
        'M' => 
        array (
            'Mustache' => 
            array (
                0 => __DIR__ . '/..' . '/mustache/mustache/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8672133ad7c4b73b0cafac78212035cc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8672133ad7c4b73b0cafac78212035cc::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit8672133ad7c4b73b0cafac78212035cc::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
