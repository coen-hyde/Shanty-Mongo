<?php

/*
 * Set error reporting to the level
 */
error_reporting(E_ALL | E_STRICT);

/*
 * Determine the root, library, and tests directories of the framework
 * distribution.
 */
$root = realpath(dirname(dirname(__FILE__)));
$coreLibrary = "$root/library";
$coreTests = "$root/tests";


/*
 * Load the user-defined test configuration file, if it exists; otherwise, load
 * the default configuration.
 */
if (is_readable($coreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php')) {
	require_once $coreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php';
} else {
	require_once $coreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php.dist';
}

if (is_null(ZEND_FRAMEWORK_PATH)) {
	die("Please configure the path to your Zend Framework library by setting the constant 'ZEND_FRAMEWORK_PATH' in your TestConfigureation.php file.");
}

/*
 * Set include path
 */
$path = array(
	$coreLibrary,
	$coreTests,
	ZEND_FRAMEWORK_PATH,
	get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $path));

if (defined('TESTS_GENERATE_REPORT') && TESTS_GENERATE_REPORT === true &&
	version_compare(PHPUnit_Runner_Version::id(), '3.1.6', '>=')) {

	/*
* Add library/ directory to the PHPUnit code coverage
* whitelist. This has the effect that only production code source files
* appear in the code coverage report and that all production code source
* files, even those that are not covered by a test yet, are processed.
*/
	PHPUnit_Util_Filter::addDirectoryToWhitelist($coreLibrary);

	/*
* Omit from code coverage reports the contents of the tests directory
*/
	foreach (array('.php', '.phtml', '.csv', '.inc') as $suffix) {
		PHPUnit_Util_Filter::addDirectoryToFilter($coreTests, $suffix);
	}
	PHPUnit_Util_Filter::addDirectoryToFilter(PEAR_INSTALL_DIR);
	PHPUnit_Util_Filter::addDirectoryToFilter(PHP_LIBDIR);
	PHPUnit_Util_Filter::addDirectoryToFilter(ZEND_FRAMEWORK_PATH);
	PHPUnit_Util_Filter::addDirectoryToFilter($coreTests);
}

/*
 * Unset global variables that are no longer needed.
 */
unset($root, $coreLibrary, $coreTests, $path);
