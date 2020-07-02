<?php /** @noinspection DuplicatedCode */

/**
 * TruUcde
 *
 * @package Truucde
 */

namespace TruUcdeBlog;

require_once 'class-wp-error.php';
use \WP_Error; // need to create empty error objects.

/**
 * TruUcde test case
 */
class TruUcdeTest extends \WP_Mock\Tools\TestCase {
// TODO: eliminate need for WP_ERROR object
	/*
	 * Define all the things
	 */
	public $Target_code  = 'user_email';
	public $Black_msg    = 'You cannot use that email address to signup. We are having problems with them blocking some of our email. Please use another email provider.';
	public $White_msg    = 'Sorry, that email address is not allowed!';
	public $Target_data  = 'User email things.';
	public $Another_code = 'user_foolishness';
	public $Another_msg  = 'The eagle lands at midnight';
	public $Another_data = 'Spy games';

	/**
	 * Test setup
	 */
	public function setUp(): void {
		\WP_Mock::setUp();
	}

	/**
	 * Test teardown
	 */
	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	/**
	 * Test that hooks are being initialized.
	 */
	public function test_it_adds_hooks(): void {

		// ensure the filter is added.
		\WP_Mock::expectFilterAdded(
			'wpmu_validate_user_signup',
			'TruUcde\on_loaded'
		);
		// Now test the init hook method of the class to check if the filter is added.
		truucde_init();
		$this->assertConditionsMet();
	}

	/**
	 * Tests that user checks are working as they should
	 * TT
	 */
	public function test_user_can_add_TT() {

		// Mock 'is_user_logged' in and 'current_user_can'.
		\WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => true,
			)
		);
		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'args'   => array( 'promote_users' ),
				'return' => true,
			)
		);
		// Run it through the function.
		$result = user_can_add();
		// And assert
		$this->assertSame( true, $result );
	}

	/**
	 * Tests that user checks are working as they should
	 * TF
	 */
	public function test_user_can_add_TF() {

		// Mock 'is_user_logged' in and 'current_user_can'.
		\WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => true,
			)
		);
		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'args'   => array( 'promote_users' ),
				'return' => false,
			)
		);
		// Run it through the function.
		$result = user_can_add();
		// And assert.
		$this->assertSame( false, $result );
	}

	/**
	 * Tests that user checks are working as they should
	 * FT
	 */
	public function test_user_can_add_FT() {

		// Mock 'is_user_logged' in and 'current_user_can'.
		\WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => false,
			)
		);
		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 0,
				'args'   => array( 'promote_users' ),
				'return' => true,
			)
		);
		// Run it through the function.
		$result = user_can_add();
		// And assert
		$this->assertSame( false, $result );
	}

	/**
	 * Tests that user checks are working as they should
	 * FF
	 */
	public function test_user_can_add_FF() {
		// Mock 'is_user_logged' in and 'current_user_can'.
		\WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => false,
			)
		);
		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 0,
				'args'   => array( 'promote_users' ),
				'return' => false,
			)
		);
		// Run it through the function.
		$result = user_can_add();
		// And assert
		$this->assertSame( false, $result );
	}

	/**
	 * Tests that the error object is valid, non-empty and
	 * has white/black list errors that need processing
	 *
	 * Tests error object empty (4 cases)
	 */
	public function test_e_needs_processing_empty() {
		// Is error object -> is empty (4 cases).
		$empty_wp_error = new WP_Error();
		$result         = e_needs_processing( $empty_wp_error );
		$this->assertFalse( $result );

	}

	/**
	 * Tests that the error object is valid, non-empty and
	 * has white/black list errors that need processing
	 *
	 * Tests truth table of list msg presence.
	 */
	public function test_e_needs_processing_lists() {
		// TODO: Separate functions
		// Doing this the lazy way, adding and removing as I go
		// to create the various truth table conditions
		$truucde_error = new WP_Error();

		$truucde_error->add( $this->Another_code, $this->Another_msg, $this->Another_data );

		// FF
		$result = e_needs_processing( $truucde_error );
		$this->assertFalse( $result );

		// TF
		$truucde_error->add( $this->Target_code, $this->Black_msg, $this->Target_data );

		$result = e_needs_processing( $truucde_error );
		$this->assertTrue( $result );

		// FT
		$truucde_error->remove( $this->Target_code );

		$truucde_error->add( $this->Target_code, $this->White_msg, $this->Target_data );

		$result = e_needs_processing( $truucde_error );
		$this->assertTrue( $result );

		// TT
		$truucde_error->add( $this->Target_code, $this->Black_msg, $this->Target_data );

		$result = e_needs_processing( $truucde_error );
		$this->assertTrue( $result );
	}

	/**
	 * Testing if on load conditions failure returns
	 * original object
	 */
	// TODO: other bail tests
	public function test_on_load_conditions_bail() {

		\WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'args'   => array( 'promote_users' ),
				'return' => true,
			)
		);

		$truucde_error = new WP_Error();

		$truucde_error->add( $this->Another_code, $this->Another_msg, $this->Another_data );

		$orig_result           = array();
		$orig_result['errors'] = $truucde_error;

		$result = on_loaded( $orig_result );

		$this->assertEquals( $orig_result, $result );

	}

	/**
	 * Testing if function properly processes object
	 */
	public function test_on_load_processing() {

		\WP_Mock::userFunction(
			'is_user_logged_in',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'args'   => array( 'promote_users' ),
				'return' => true,
			)
		);

		$truucde_error = new WP_Error();

		$truucde_error->add( $this->Another_code, $this->Another_msg, $this->Another_data );
		$truucde_error->add( $this->Target_code, $this->Another_msg, $this->Another_data );
		$truucde_error->add( $this->Target_code, $this->Black_msg, $this->Target_data );
		$truucde_error->add( $this->Target_code, $this->White_msg, $this->Target_data );

		$orig_result           = array();
		$orig_result['errors'] = $truucde_error;

		$result = on_loaded( $orig_result );

		$error_return = $result['errors'];
		// Object is WP_Error
		$this->assertInstanceOf( WP_Error::class, $error_return );

		// Returns correct error codes
		$return_codes = $error_return->get_error_codes();
		$this->assertContains( $this->Another_code, $return_codes );
		$this->assertContains( $this->Target_code, $return_codes );

		// Returns correct messages
		$return_a_msg = $error_return->get_error_messages( $this->Another_code );
		$return_t_msg = $error_return->get_error_messages( $this->Target_code );

		$this->assertContains( $this->Another_msg, $return_a_msg );
		$this->assertContains( $this->Another_msg, $return_t_msg );

		$this->assertNotContains( $this->Black_msg, $return_t_msg );
		$this->assertNotContains( $this->White_msg, $return_t_msg );

		// Returns data
		$this->assertContains( $this->Another_data, $error_return->get_error_data( $this->Another_code ) );
		$this->assertNotContains( $this->Target_data, $error_return->get_error_data( $this->Another_code ) );
		$this->assertContains( $this->Target_data, $error_return->get_error_data( $this->Target_code ) );
	}

}
