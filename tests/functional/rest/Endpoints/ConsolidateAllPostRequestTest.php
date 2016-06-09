<?php

namespace idlikethis\Endpoints;


use Codeception\TestCase\WPTestCase;

class ConsolidateAllPostRequestTest extends WPTestCase {

	/**
	 * @var \tad_DI52_Container
	 */
	protected static $container;

	public static function setUpBeforeClass() {
		self::$container = include codecept_root_dir( 'bootstrap.php' );

		return parent::setUpBeforeClass(); // TODO: Change the autogenerated stub
	}

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		wp_set_current_user(1);
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should not consolidate comments if post id is missing from POST request
	 */
	public function it_should_not_consolidate_comments_if_post_id_is_missing_from_post_request() {
		/** @var \idlikethis_Endpoints_ConsolidateAllHandlerInterface $endpoint */
		$endpoint = self::$container->make( 'idlikethis_Endpoints_ConsolidateAllHandlerInterface' );

		$request = new \WP_REST_Request();
		/** @var \WP_REST_Response $out */
		$out = $endpoint->handle( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $out );
		$this->assertEquals( 403, $out->get_status() );
	}

	/**
	 * @test
	 * it should not consolidate comments if post id is not valid
	 */
	public function it_should_not_consolidate_comments_if_post_id_is_not_valid() {
		$post_id = 4455;
		$this->factory()->comment->create_many( 5, [ 'comment_post_ID' => $post_id, 'comment_type' => 'idlikethis' ] );
		$this->factory()->comment->create_many( 5, [ 'comment_post_ID' => $post_id ] );

		/** @var \idlikethis_Endpoints_ConsolidateAllHandlerInterface $endpoint */
		$endpoint = self::$container->make( 'idlikethis_Endpoints_ConsolidateAllHandlerInterface' );

		$request = new \WP_REST_Request();
		$request->set_param( 'post_id', $post_id );
		/** @var \WP_REST_Response $out */
		$out = $endpoint->handle( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $out );
		$this->assertEquals( 403, $out->get_status() );
		$this->assertCount( 5, get_comments( [ 'post_id' => $post_id, 'type' => 'idlikethis' ] ) );
		$this->assertCount( 10, get_comments( [ 'post_id' => $post_id ] ) );
	}

	/**
	 * @test
	 * it should consolidate comments when post id is valid
	 */
	public function it_should_consolidate_comments_when_post_id_is_valid() {
		$post_id = $this->factory()->post->create();
		$ideas   = [ 'idea one', 'idea two', 'idea three' ];
		foreach ( $ideas as $idea ) {
			for ( $i = 0; $i < 5; $i ++ ) {
				$this->factory()->comment->create( [ 'comment_post_ID' => $post_id, 'comment_type' => 'idlikethis', 'comment_content' => $i . ' - ' . $idea ] );
			}
		}
		$this->factory()->comment->create_many( 5, [ 'comment_post_ID' => $post_id ] );

		/** @var \idlikethis_Endpoints_ConsolidateAllHandlerInterface $endpoint */
		$endpoint = self::$container->make( 'idlikethis_Endpoints_ConsolidateAllHandlerInterface' );

		$request = new \WP_REST_Request();
		$request->set_param( 'post_id', $post_id );
		/** @var \WP_REST_Response $out */
		$out = $endpoint->handle( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $out );
		$this->assertEquals( 200, $out->get_status() );
		$this->assertCount( 0, get_comments( [ 'post_id' => $post_id, 'type' => 'idlikethis' ] ) );
		$this->assertCount( 5, get_comments( [ 'post_id' => $post_id ] ) );
		$votes = get_post_meta( $post_id, '_idlikethis_votes', true );
		$this->assertCount( 3, $votes );
		foreach ( $ideas as $idea ) {
			$this->assertArrayHasKey( $idea, $votes );
			$this->assertEquals( 5, $votes[ $idea ] );
		}
	}
}