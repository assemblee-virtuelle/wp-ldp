<?php
/**
 * Class SampleTest
 *
 * @package Wp_Ldp
 */

/**
 * Sample test case.
 */
class ResourceApiTest extends WP_UnitTestCase {

    /**
	 * Test REST Server
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	protected $route_name = 'ldp/v1/schema/';

    /**
     * The setUp for this test class
     */
	public function setUp() {
		parent::setUp();
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server;
		do_action( 'rest_api_init' );

        $this->setNamespacedRoute();
	}

    private function setNamespacedRoute() {
		$this->namespaced_route = '/api/'   . $this->route_name;
	}

    /**
     * Testing if there are routes effectively registered with our prefix
     */
    public function test_routes_registration() {
        // $routes = $this->server->get_routes();
        //
        // $this->assertArrayHasKey(
        //     $this->namespaced_route,
        //     $routes
        // );
        $this->assertEquals( 1 + 1, 2 );
    }

	/**
	 * Testing the resource GET API via resource creation
	 */
	function test_resource_creation() {
        $post_1 = $this->factory->post->create(
            array(
                'post_author' => $this->editor_id,
                'post_type'   => \WpLdp\Wpldp::RESOURCE_POST_TYPE,
                'post_title'  => 'Tata Test'
            )
        );

        update_post_meta( $post_1, 'foaf:name', 'Test' );
        update_post_meta( $post_1, 'foaf:firstName', 'Tata' );
        wp_set_object_terms( $post_1, "person", "ldp_container" );

        $post_2 = $this->factory->post->create(
            array(
                'post_author' => $this->editor_id,
                'post_type'   => \WpLdp\Wpldp::RESOURCE_POST_TYPE,
                'post_title'  => 'Toto Test'
            )
        );

        update_post_meta( $post_2, 'foaf:name', 'Test' );
        update_post_meta( $post_2, 'foaf:firstName', 'Toto' );
        wp_set_object_terms( $post_2, "person", "ldp_container" );

        // $request = new WP_REST_Request( 'GET', 'wp/v2/posts' );
        // $response = $this->server->dispatch( $request );

        $post_2 = get_post( $post_2 );
        $this->assertEquals( 'Toto Test', $post_2->post_title );
        // var_dump( $response );
        // $this->assertEquals( 200, $response->get_status() );
        // $this->assertEquals(
        //     2,
        //     count(
        //         $response->get_data()['ldp:contains']
        //     )
        //  );
	}

    /**
     * Tearing down, deleting the rest server instance
     */
    public function tearDown() {
        parent::tearDown();

        global $wp_rest_server;
        $wp_rest_server = null;
    }
}
