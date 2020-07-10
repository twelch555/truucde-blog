<?php
namespace TruUcdeBlog;

use WP_Error;
use WP_UnitTestCase;

class MsTruUcdeTest extends WP_UnitTestCase {

	/*
	 * Define all the things
	 */
	public $site2_admin;
	public $site3_admin;
	public $blog_2;
	public $blog_3;

	public $Target_code  = 'user_email';
	public $Black_msg    = 'You cannot use that email address to signup. We are having problems with them blocking some of our email. Please use another email provider.';
	public $White_msg    = 'Sorry, that email address is not allowed!';
	public $Target_code_not_bw = 'Please enter a valid email address.';
	public $Target_data  = 'User email things.';
	public $Another_code = 'user_name';
	public $Another_msg  = 'Usernames can only contain lowercase letters (a-z) and numbers.';


	/*
	 * Test setup
	 */
	public function setUp() {
		parent::setUp();

		// add additional subsites.
		$this->blog_2 = $this->factory->blog->create();
		$this->blog_3 = $this->factory->blog->create();

		// add additional users: user 1 - superadmin already created.
		$this->site2_admin = $this->factory->user->create();
		$this->site3_admin = $this->factory->user->create();

		// add users to blogs with roles.
		add_user_to_blog( $this->blog_2, $this->site2_admin, 'administrator' );
		add_user_to_blog( $this->blog_3, $this->site2_admin, 'editor' );
		add_user_to_blog( $this->blog_2, $this->site3_admin, 'editor' );
		add_user_to_blog( $this->blog_3, $this->site3_admin, 'administrator' );
		
		// add white list and black list domain.
		update_network_option( get_current_network_id(), 'limited_email_domains', 'whitelist.com' );
		update_network_option( get_current_network_id(), 'banned_email_domains', 'blacklist.com' );
	}

	/*
	 * Test Teardown
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Setup test: basic sanity check
	 */
	public function testBasic() {
		$this->assertTrue( true );
	}

	/**
	 * Check for a plugin constant to ensure connection with plugin
	 */
	public function test_plugin_constant() {
		$this->assertSame( 'user_email', TARGET_ERROR_CODE );
	}

	/*
	 * Make sure filter is registered
	 */
	public function test_for_filter() {
		$this->assertEquals(
			10,
			has_filter( 'wpmu_validate_user_signup', 'TruUcdeBlog\on_loaded' )
		);

	}

	/*
	 * Check context, blogs, users, roles
	 */
	public function test_context() {
		global $current_user;
		global $current_blog;

		// Blog 2.
		switch_to_blog( $this->blog_2 );
		set_current_screen( 'user_new.php' );

		// add_new_users option unchecked.
		update_network_option( get_current_network_id(), 'add_new_users', 0 );
		
		wp_set_current_user( $this->site2_admin );
		$this->assertFalse( user_can( $this->site2_admin, 'create_users' ) );

		wp_set_current_user( $this->site3_admin );
		$this->assertFalse( user_can( $this->site3_admin, 'create_users' ) );
		
		// add_new_users option checked.
		update_network_option( get_current_network_id(), 'add_new_users', 1 );
		
		wp_set_current_user( $this->site2_admin );
		$this->assertTrue( user_can( $this->site2_admin, 'create_users' ) );
		
		wp_set_current_user( $this->site3_admin );
		$this->assertFalse( user_can( $this->site3_admin, 'create_users' ) );

		// Blog 3.
		switch_to_blog( $this->blog_3 );
		set_current_screen( 'user_new.php' );
		
		// add_new_users option unchecked.
		update_network_option( get_current_network_id(), 'add_new_users', 0 );
		
		wp_set_current_user( $this->site3_admin );
		$this->assertFalse( user_can( $this->site3_admin, 'create_users' ) );

		wp_set_current_user( $this->site2_admin );
		$this->assertFalse( user_can( $this->site2_admin, 'create_users' ) );
		
		// add_new_users option checked.
		update_network_option( get_current_network_id(), 'add_new_users', 1 );
		
		wp_set_current_user( $this->site3_admin );
		$this->assertTrue( user_can( $this->site3_admin, 'create_users' ) );
		
		wp_set_current_user( $this->site2_admin );
		$this->assertFalse( user_can( $this->site2_admin, 'create_users' ) );
	}


	/*
	 * Check responses from user_can_add() for:
	 * No user logged in, superadmin, site2_admin and site3_admin
	 * for blogs 2 and 3.
	 */
	public function test_user_can_add() {
		update_network_option( get_current_network_id(), 'add_new_users', 1 );

		// No user logged in
		$this->assertSame( 0, get_current_user_id() );
		
		switch_to_blog( $this->blog_2 );
		$this->assertFalse( user_can_add() );
		
		switch_to_blog( $this->blog_3 );
		$this->assertFalse( user_can_add() );
		
		// Super admin
		wp_set_current_user( 1 );

		switch_to_blog( $this->blog_2 );
		$this->assertTrue( user_can_add() );

		switch_to_blog( $this->blog_3 );
		$this->assertTrue( user_can_add() );

		// Admin users
		wp_set_current_user( $this->site2_admin );
		switch_to_blog( $this->blog_2 );
		$this->assertTrue( user_can_add() );

		wp_set_current_user( $this->site3_admin );
		switch_to_blog( $this->blog_3 );
		$this->assertTrue( user_can_add() );

		// Editor
		wp_set_current_user( $this->site2_admin );
		switch_to_blog( $this->blog_3 );
		$this->assertFalse( user_can_add() );

		wp_set_current_user( $this->site3_admin );
		switch_to_blog( $this->blog_2 );
		$this->assertFalse( user_can_add() );
	}

	
	/*
	 * Test e_needs_processing and on_load
	 */
	
	// White list domain
	public function test_white_list() {
		update_network_option( get_current_network_id(), 'add_new_users', 1 );
		switch_to_blog( $this->blog_2 );
		
		// super admin
		wp_set_current_user( 1 );
		
		$result = wpmu_validate_user_signup( 'wlistuser', 'wlistuser@whitelist.com' );
		$this->assertEmpty( $result['errors']->errors );
		
		// site admin
		wp_set_current_user( $this->site2_admin );
		
		$result = wpmu_validate_user_signup( 'wlistuser', 'wlistuser@whitelist.com' );
		$this->assertEmpty( $result['errors']->errors );
		
		// site editor
		wp_set_current_user( $this->site3_admin );
		
		$result = wpmu_validate_user_signup( 'wlistuser', 'wlistuser@whitelist.com' );
		$this->assertEmpty( $result['errors']->errors );
		
	}
	
	// Black list domain
	public function test_black_list() {
		update_network_option( get_current_network_id(), 'add_new_users', 1 );
		switch_to_blog( $this->blog_2 );
		
		// super admin
		wp_set_current_user( 1 );
		
		$result = wpmu_validate_user_signup( 'blistuser', 'blistuser@blacklist.com' );
		$this->assertEmpty( $result['errors']->errors );
		
		// site admin
		wp_set_current_user( $this->site2_admin );
		
		$result = wpmu_validate_user_signup( 'blistuser', 'blistuser@blacklist.com' );
		$this->assertEmpty( $result['errors']->errors );
		
		// site editor
		wp_set_current_user( $this->site3_admin );
		
		$result = wpmu_validate_user_signup( 'blistuser', 'blistuser@blacklist.com' );
		$this->assertContains( $this->Target_code, $result['errors']->get_error_codes() );
		$this->assertContains( $this->Black_msg, $result['errors']->get_error_messages( $this->Target_code ) );
		$this->assertContains( $this->White_msg, $result['errors']->get_error_messages( $this->Target_code ) );
	}
	
	// Neither white or black
	public function test_neither_list() {
		update_network_option( get_current_network_id(), 'add_new_users', 1 );
		switch_to_blog( $this->blog_2 );
		
		// super admin
		wp_set_current_user( 1 );
		
		$result = wpmu_validate_user_signup( 'neither', 'neither@neither.com' );
		$this->assertEmpty( $result['errors']->errors );
		
		// site admin
		wp_set_current_user( $this->site2_admin );
		
		$result = wpmu_validate_user_signup( 'neither', 'neither@neither.com' );
		$this->assertEmpty( $result['errors']->errors );
		
		// site editor
		wp_set_current_user( $this->site3_admin );
		
		$result = wpmu_validate_user_signup( 'neither', 'neither@neither.com' );
		$this->assertContains( $this->Target_code, $result['errors']->get_error_codes() );
		$this->assertContains( $this->White_msg, $result['errors']->get_error_messages( $this->Target_code ) );
	}
	
	// Invalid email address: target code, other message
	public function test_invalid_email() {
		update_network_option( get_current_network_id(), 'add_new_users', 1 );
		switch_to_blog( $this->blog_2 );
		
		// super admin
		wp_set_current_user( 1 );
		
		$result = wpmu_validate_user_signup( 'neither', 'neither=neither.com' );
		$this->assertContains( $this->Target_code, $result['errors']->get_error_codes() );
		$this->assertContains( $this->Target_code_not_bw, $result['errors']->get_error_messages( $this->Target_code ) );
		
		// site admin
		wp_set_current_user( $this->site2_admin );
		
		$result = wpmu_validate_user_signup( 'neither', 'neither=neither.com' );
		$this->assertContains( $this->Target_code, $result['errors']->get_error_codes() );
		$this->assertContains( $this->Target_code_not_bw, $result['errors']->get_error_messages( $this->Target_code ) );
		
		// site editor
		wp_set_current_user( $this->site3_admin );
		
		$result = wpmu_validate_user_signup( 'neither', 'neither=neither.com' );
		$this->assertContains( $this->Target_code, $result['errors']->get_error_codes() );
		$this->assertContains( $this->Target_code_not_bw, $result['errors']->get_error_messages( $this->Target_code ) );
		$this->assertContains( $this->White_msg, $result['errors']->get_error_messages( $this->Target_code ) );
		
	}
	
	// Invalid user name
	public function test_invalid_username() {
		update_network_option( get_current_network_id(), 'add_new_users', 1 );
		switch_to_blog( $this->blog_2 );
		
		// super admin
		wp_set_current_user( 1 );
		
		$result = wpmu_validate_user_signup( 'white=list', 'whitelist@whitelist.com' );
		$this->assertContains( $this->Another_code, $result['errors']->get_error_codes() );
		$this->assertContains( $this->Another_msg, $result['errors']->get_error_messages( $this->Another_code ) );
		
		// site admin
		wp_set_current_user( $this->site2_admin );
		
		$result = wpmu_validate_user_signup( 'white=list', 'whitelist@whitelist.com' );
		$this->assertContains( $this->Another_code, $result['errors']->get_error_codes() );
		$this->assertContains( $this->Another_msg, $result['errors']->get_error_messages( $this->Another_code ) );
		
		// site editor
		wp_set_current_user( $this->site3_admin );
		
		$result = wpmu_validate_user_signup( 'white=list', 'whitelist@whitelist.com' );
		$this->assertContains( $this->Another_code, $result['errors']->get_error_codes() );
		$this->assertContains( $this->Another_msg, $result['errors']->get_error_messages( $this->Another_code ) );
		
	}
}
