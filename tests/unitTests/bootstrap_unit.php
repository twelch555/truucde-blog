<?php

// First we need to load the composer autoloader so we can use WP Mock
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/autoload.php';

// Now call the bootstrap method of WP Mock
\WP_Mock::bootstrap();

/**
 * Now we include any plugin files that we need to be able to run the tests. This
 * should be files that define the functions and classes you're going to test.
 */
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/truucde.php';
