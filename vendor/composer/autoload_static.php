<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc1529dc0cd930159e34e56f31cda3c3d
{
    public static $files = array (
        '12df85c0975153c38c943ef8a8b9b01f' => __DIR__ . '/../..' . '/src/helpers.php',
    );

    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Andiwijaya\\AppCore\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Andiwijaya\\AppCore\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc1529dc0cd930159e34e56f31cda3c3d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc1529dc0cd930159e34e56f31cda3c3d::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
