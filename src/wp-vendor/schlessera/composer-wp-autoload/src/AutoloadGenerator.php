<?php
/**
 * WordPress Custom Autoloader Generator.
 *
 * @package   WordPress\ComposerAutoload
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://www.alainschlesser.com/
 * @copyright 2016 Alain Schlesser
 *
 * Partially based on xrstf/composer-php52 by Christoph Mewes.
 * @see       https://github.com/composer-php52/composer-php52
 */

namespace WordPress\ComposerAutoload;

use Composer\Autoload\AutoloadGenerator as BaseGenerator;
use Composer\Autoload\ClassMapGenerator;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

/**
 * Class AutoloadGenerator.
 *
 * Generate the WordPress-specific class loader.
 *
 * @since   1.0.0
 *
 * @package WordPress\ComposerAutoload
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
class AutoloadGenerator extends BaseGenerator
{

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var bool
     */
    private $classMapAuthoritative = false;

    /**
     * @var string
     */
    private $classRoot;

    /**
     * @var bool
     */
    private $caseSensitive;

    /**
     * Instantiate a AutoloadGenerator object.
     *
     * @param IOInterface $io
     * @param string      $classRoot
     * @param bool        $caseSensitive
     */
    public function __construct($io, $classRoot, $caseSensitive)
    {
        $this->io            = $io;
        $this->classRoot     = $classRoot;
        $this->caseSensitive = $caseSensitive;
    }

    /**
     * Whether or not generated autoloader considers the class map
     * authoritative.
     *
     * @param bool $classMapAuthoritative
     */
    public function setClassMapAuthoritative($classMapAuthoritative)
    {
        $this->classMapAuthoritative = (boolean)$classMapAuthoritative;
    }

    /**
     * Dump the autoloader.
     *
     * @param Config                       $config
     * @param InstalledRepositoryInterface $localRepo
     * @param PackageInterface             $mainPackage
     * @param InstallationManager          $installationManager
     * @param string                       $targetDir
     * @param bool                         $scanPsr0Packages
     * @param string                       $suffix
     */
    public function dump(
        Config $config,
        InstalledRepositoryInterface $localRepo,
        PackageInterface $mainPackage,
        InstallationManager $installationManager,
        $targetDir,
        $scanPsr0Packages = false,
        $suffix = ''
    ) {
        if ($this->classMapAuthoritative) {
            // Force scanPsr0Packages when classmap is authoritative
            $scanPsr0Packages = true;
        }

        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($config->get('vendor-dir'));

        $basePath   = $filesystem->normalizePath(realpath(getcwd()));
        $vendorPath = $filesystem->normalizePath(realpath($config->get('vendor-dir')));
        $targetDir  = $vendorPath . '/' . $targetDir;
        $filesystem->ensureDirectoryExists($targetDir);

        $useGlobalIncludePath  = (bool)$config->get('use-include-path');
        $prependAutoloader     = $config->get('prepend-autoloader') === false ? 'false' : 'true';
        $classMapAuthoritative = $config->get('classmap-authoritative');

        $vendorPathCode            = $filesystem->findShortestPathCode(realpath($targetDir), $vendorPath, true);
        $vendorPathToTargetDirCode = $filesystem->findShortestPathCode($vendorPath, realpath($targetDir), true);

        $appBaseDirCode = $filesystem->findShortestPathCode($vendorPath, $basePath, true);
        $appBaseDirCode = str_replace('__DIR__', '$vendorDir', $appBaseDirCode);
        $appBaseDirCode = str_replace('dirname($vendorDir)', $this->classRoot, $appBaseDirCode);

        // add 5.2 compat
        $vendorPathCode            = str_replace('__DIR__', 'dirname(__FILE__)', $vendorPathCode);
        $vendorPathToTargetDirCode = str_replace('__DIR__', 'dirname(__FILE__)', $vendorPathToTargetDirCode);

        $packageMap = $this->buildPackageMap($installationManager, $mainPackage, $localRepo->getCanonicalPackages());
        $autoloads  = $this->parseAutoloads($packageMap, $mainPackage);

        // add custom psr-0 autoloading if the root package has a target dir
        $targetDirLoader = null;
        $mainAutoload    = $mainPackage->getAutoload();
        if ($mainPackage->getTargetDir() && ! empty($mainAutoload['psr-0'])) {
            $levels   = count(explode('/', $filesystem->normalizePath($mainPackage->getTargetDir())));
            $prefixes = implode(', ', array_map(function ($prefix) {
                return var_export($prefix, true);
            }, array_keys($mainAutoload['psr-0'])));

            $baseDirFromTargetDirCode = $filesystem->findShortestPathCode($targetDir, $basePath, true);

            $targetDirLoader = <<<EOF

	public static function autoload(\$class) {
		\$dir      = $baseDirFromTargetDirCode.'/';
		\$prefixes = array($prefixes);

		foreach (\$prefixes as \$prefix) {
			if (0 !== strpos(\$class, \$prefix)) {
				continue;
			}

			\$path = explode(DIRECTORY_SEPARATOR, self::getClassPath(\$class));
			\$path = \$dir.implode('/', array_slice(\$path, $levels));

			if (!\$path = self::resolveIncludePath(\$path)) {
				return false;
			}

			require \$path;
			return true;
		}
	}

EOF;
        }

        $filesCode          = "";
        $autoloads['files'] = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($autoloads['files']));
        foreach ($autoloads['files'] as $functionFile) {
            // don't include file if it is using PHP 5.3+ syntax
            // https://bitbucket.org/xrstf/composer-php52/issue/4
            if ($this->isPHP53($functionFile)) {
                $filesCode .= '//		require ' . $this->getPathCode($filesystem, $basePath, $vendorPath,
                        $functionFile) . "; // disabled because of PHP 5.3 syntax\n";
            } else {
                $filesCode .= '		require ' . $this->getPathCode($filesystem, $basePath, $vendorPath,
                        $functionFile) . ";\n";
            }
        }

        if (! $suffix) {
            $suffix = md5(uniqid('', true));
        }

        $includePathFile = $this->getIncludePathsFile($packageMap, $filesystem, $basePath, $vendorPath, $vendorPathCode,
            $appBaseDirCode);

        $classmapFile = <<<EOF
<?php

// autoload_classmap_wordpress.php @generated by Composer

\$vendorDir = $vendorPathCode;
\$baseDir = $appBaseDirCode;

return array(

EOF;

        $blacklist = null;
        if (! empty($autoloads['exclude-from-classmap'])) {
            $blacklist = '{(' . implode('|', $autoloads['exclude-from-classmap']) . ')}';
        }

        // flatten array
        $classMap = array();
        if ($scanPsr0Packages) {
            $namespacesToScan = array();

            // Scan the PSR-0/4 directories for class files, and add them to the class map
            foreach (array('psr-0', 'psr-4') as $psrType) {
                foreach ($autoloads[$psrType] as $namespace => $paths) {
                    $namespacesToScan[$namespace][] = array('paths' => $paths, 'type' => $psrType);
                }
            }

            krsort($namespacesToScan);

            foreach ($namespacesToScan as $namespace => $groups) {
                foreach ($groups as $group) {
                    $psrType = $group['type'];
                    foreach ($group['paths'] as $dir) {
                        $dir = $filesystem->normalizePath($filesystem->isAbsolutePath($dir) ? $dir : $basePath . '/' . $dir);
                        if (! is_dir($dir)) {
                            continue;
                        }

                        $namespaceFilter = $namespace === '' ? null : $namespace;
                        $classMap        = $this->addClassMapCode($filesystem, $basePath, $vendorPath, $dir, $blacklist,
                            $namespaceFilter, $classMap);
                    }
                }
            }
        }

        foreach ($autoloads['classmap'] as $dir) {
            $classMap = $this->addClassMapCode($filesystem, $basePath, $vendorPath, $dir, $blacklist, null, $classMap);
        }

        ksort($classMap);
        foreach ($classMap as $class => $code) {
            $className = $this->caseSensitive
                ? var_export($class, true)
                : mb_strtolower(var_export($class, true));
            $classmapFile .= '    ' . $className . ' => ' . $code;
        }
        $classmapFile .= ");\n";

        file_put_contents($vendorPath . '/autoload_wordpress.php',
            $this->getAutoloadFile($vendorPathToTargetDirCode, $suffix));
        file_put_contents($targetDir . '/autoload_real_wordpress.php',
            $this->getAutoloadRealFile(true, (bool)$includePathFile, $targetDirLoader, $filesCode, $vendorPathCode,
                $appBaseDirCode, $suffix, $useGlobalIncludePath, $prependAutoloader));
        file_put_contents($targetDir . '/autoload_classmap_wordpress.php', $classmapFile);

        // use stream_copy_to_stream instead of copy
        // to work around https://bugs.php.net/bug.php?id=64634
        $sourceLoader = fopen(__DIR__ . '/ClassLoaderWordPress.php', 'r');
        $targetLoader = fopen($targetDir . '/ClassLoaderWordPress.php', 'w+');
        $file         = $this->setCaseSensitivity(stream_get_contents($sourceLoader));
        fwrite($targetLoader, $file);
        fclose($sourceLoader);
        fclose($targetLoader);
        unset($sourceLoader, $targetLoader);
    }

    /**
     * Set the case sensitivity within the class loader.
     *
     * @param string $file
     *
     * @return string
     */
    private function setCaseSensitivity($file)
    {
        return str_replace(
            'private $caseSensitive = true;',
            'private $caseSensitive = ' . ($this->caseSensitive ? 'true' : 'false') . ';',
            $file
        );
    }

    /**
     * Check whether the file is PHP5.3+.
     *
     * @param string $file
     *
     * @return bool
     */
    protected function isPHP53($file)
    {
        $tokens = token_get_all(file_get_contents($file));
        $php53  = array(T_DIR, T_GOTO, T_NAMESPACE, T_NS_C, T_NS_SEPARATOR, T_USE);

        // PHP 5.4+
        if (defined('T_TRAIT')) {
            $php53[] = T_TRAIT;
            $php53[] = T_TRAIT_C;
            $php53[] = T_TRAIT_C;
        }

        // PHP 5.5+
        if (defined('T_FINALLY')) {
            $php53[] = T_FINALLY;
            $php53[] = T_YIELD;
        }

        // @todo Add PHP 5.6 + 7.0 language features.

        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], $php53)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a single class map resolution.
     *
     * @param Filesystem $filesystem
     * @param string     $basePath
     * @param string     $vendorPath
     * @param string     $dir
     * @param null       $blacklist
     * @param null       $namespaceFilter
     * @param array      $classMap
     *
     * @return array
     */
    private function addClassMapCode(
        $filesystem,
        $basePath,
        $vendorPath,
        $dir,
        $blacklist = null,
        $namespaceFilter = null,
        array $classMap = array()
    ) {
        foreach ($this->generateClassMap($dir, $blacklist, $namespaceFilter) as $class => $path) {
            $pathCode = $this->getPathCode($filesystem, $basePath, $vendorPath, $path) . ",\n";
            if (! isset($classMap[$class])) {
                $classMap[$class] = $pathCode;
            } elseif ($this->io && $classMap[$class] !== $pathCode && ! preg_match('{/(test|fixture|example|stub)s?/}i',
                    strtr($classMap[$class] . ' ' . $path, '\\', '/'))
            ) {
                $this->io->writeError(
                    '<warning>Warning: Ambiguous class resolution, "' . $class . '"' .
                    ' was found in both "' . str_replace(array('$vendorDir . \'', "',\n"), array($vendorPath, ''),
                        $classMap[$class]) . '" and "' . $path . '", the first will be used.</warning>'
                );
            }
        }

        return $classMap;
    }

    /**
     * Trigger the class map generation.
     *
     * @param string $dir
     * @param null   $blacklist
     * @param null   $namespaceFilter
     * @param bool   $showAmbiguousWarning
     *
     * @return array
     */
    private function generateClassMap($dir, $blacklist = null, $namespaceFilter = null, $showAmbiguousWarning = true)
    {
        return ClassMapGenerator::createMap($dir, $blacklist, $showAmbiguousWarning ? $this->io : null,
            $namespaceFilter);
    }

    /**
     * Fetch the includes path file.
     *
     * @param array      $packageMap
     * @param Filesystem $filesystem
     * @param string     $basePath
     * @param string     $vendorPath
     * @param string     $vendorPathCode
     * @param string     $appBaseDirCode
     *
     * @return string|void
     */
    protected function getIncludePathsFile(
        array $packageMap,
        Filesystem $filesystem,
        $basePath,
        $vendorPath,
        $vendorPathCode,
        $appBaseDirCode
    ) {
        $includePaths = array();

        foreach ($packageMap as $item) {
            list($package, $installPath) = $item;

            if (null !== $package->getTargetDir() && strlen($package->getTargetDir()) > 0) {
                $installPath = substr($installPath, 0, -strlen('/' . $package->getTargetDir()));
            }

            foreach ($package->getIncludePaths() as $includePath) {
                $includePath    = trim($includePath, '/');
                $includePaths[] = empty($installPath) ? $includePath : $installPath . '/' . $includePath;
            }
        }

        if (! $includePaths) {
            return;
        }

        $includePathsFile = <<<EOF
<?php

// include_paths_wordpress.php generated by schlessera/composer-wp-autoload

\$vendorDir = $vendorPathCode;
\$baseDir = $appBaseDirCode;

return array(

EOF;

        foreach ($includePaths as $path) {
            $includePathsFile .= "\t" . $this->getPathCode($filesystem, $basePath, $vendorPath, $path) . ",\n";
        }

        return $includePathsFile . ");\n";
    }

    /**
     * Get the base autoload file.
     *
     * @param string $vendorPathToTargetDirCode
     * @param string $suffix
     *
     * @return string
     */
    protected function getAutoloadFile($vendorPathToTargetDirCode, $suffix)
    {
        return <<<AUTOLOAD
<?php

// autoload_wordpress.php generated by schlessera/composer-wp-autoload

require_once $vendorPathToTargetDirCode.'/autoload_real_wordpress.php';

return ComposerAutoloaderInit$suffix::getLoader();

AUTOLOAD;
    }

    /**
     * Get the real autoload file.
     *
     * @param bool   $useClassMap
     * @param bool   $useIncludePath
     * @param string $targetDirLoader
     * @param string $filesCode
     * @param string $vendorPathCode
     * @param string $appBaseDirCode
     * @param string $suffix
     * @param bool   $useGlobalIncludePath
     * @param bool   $prependAutoloader
     * @param int    $staticPhpVersion
     *
     * @return string
     */
    protected function getAutoloadRealFile(
        $useClassMap,
        $useIncludePath,
        $targetDirLoader,
        $filesCode,
        $vendorPathCode,
        $appBaseDirCode,
        $suffix,
        $useGlobalIncludePath,
        $prependAutoloader,
        $staticPhpVersion = 70000
    ) {
        // TODO the class ComposerAutoloaderInit should be revert to a closure
        // when APC has been fixed:
        // - https://github.com/composer/composer/issues/959
        // - https://bugs.php.net/bug.php?id=52144
        // - https://bugs.php.net/bug.php?id=61576
        // - https://bugs.php.net/bug.php?id=59298

        if ($filesCode) {
            $filesCode = "\n\n" . rtrim($filesCode);
        }

        $file = <<<HEADER
<?php

// autoload_real_wordpress.php generated by schlessera/composer-wp-autoload

class ComposerAutoloaderInit$suffix {
	private static \$loader;

	public static function loadClassLoader(\$class) {
		if ('WordPress_Composer_ClassLoader' === \$class) {
			require dirname(__FILE__).'/ClassLoaderWordPress.php';
		}
	}

	/**
	 * @return WordPress_Composer_ClassLoader
	 */
	public static function getLoader() {
		if (null !== self::\$loader) {
			return self::\$loader;
		}

		spl_autoload_register(array('ComposerAutoloaderInit$suffix', 'loadClassLoader'), true /*, true */);
		self::\$loader = \$loader = new WordPress_Composer_ClassLoader();
		spl_autoload_unregister(array('ComposerAutoloaderInit$suffix', 'loadClassLoader'));

		\$vendorDir = $vendorPathCode;
		\$baseDir   = $appBaseDirCode;
		\$dir       = dirname(__FILE__);


HEADER;

        if ($useIncludePath) {
            $file .= <<<'INCLUDE_PATH'
		$includePaths = require $dir.'/include_paths.php';
		array_push($includePaths, get_include_path());
		set_include_path(implode(PATH_SEPARATOR, $includePaths));


INCLUDE_PATH;
        }

        $file .= <<<'PSR0'
		$map = require $dir.'/autoload_namespaces.php';
		foreach ($map as $namespace => $path) {
			$loader->add($namespace, $path);
		}


PSR0;

        if ($useClassMap) {
            $file .= <<<'CLASSMAP'
		$classMap = require $dir.'/autoload_classmap_wordpress.php';
		if ($classMap) {
			$loader->addClassMap($classMap);
		}


CLASSMAP;
        }

        if ($this->classMapAuthoritative) {
            $file .= <<<'CLASSMAPAUTHORITATIVE'
		$loader->setClassMapAuthoritative(true);

CLASSMAPAUTHORITATIVE;
        }

        if ($useGlobalIncludePath) {
            $file .= <<<'INCLUDEPATH'
		$loader->setUseIncludePath(true);


INCLUDEPATH;
        }

        if ($targetDirLoader) {
            $file .= <<<REGISTER_AUTOLOAD
		spl_autoload_register(array('ComposerAutoloaderInit$suffix', 'autoload'), true);


REGISTER_AUTOLOAD;

        }

        $file .= <<<METHOD_FOOTER
		\$loader->register($prependAutoloader);{$filesCode}

		return \$loader;
	}

METHOD_FOOTER;

        $file .= $targetDirLoader;

        return $file . <<<FOOTER
}

FOOTER;

    }
}
