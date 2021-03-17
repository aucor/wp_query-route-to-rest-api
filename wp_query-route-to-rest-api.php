<?php
/**
 * Plugin Name: WP_Query Route To REST API
 * Description: Adds new route /wp-json/wp_query/args/ to REST API
 * Author: Aucor
 * Author URI: https://www.aucor.fi/
 * Version: 1.1.1
 * License: GPL2+
 **/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class WP_Query_Route_To_REST_API extends WP_REST_Posts_Controller {

  /**
   * Constructor
   */

  public function __construct() {

    // Plugin compatibility
    add_filter( 'wp_query_route_to_rest_api_allowed_args', array( $this, 'plugin_compatibility_args' ) );
    add_action( 'wp_query_route_to_rest_api_after_query', array( $this, 'plugin_compatibility_after_query' ) );

    // register REST route
    $this->register_routes();

  }

  /**
   * Register read-only /wp_query/args/ route
   */

  public function register_routes() {
    register_rest_route( 'wp_query', 'args', array(
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => array( $this, 'get_items' ),
      'permission_callback' => array( $this, 'get_items_permissions_check' ),
    ) );
  }

  /**
   * Check if a given request has access to get items
   *
   * @param WP_REST_Request $request Full data about the request.
   *
   * @return WP_Error|bool
   */

  public function get_items_permissions_check( $request ) {
    return apply_filters( 'wp_query_route_to_rest_api_permissions_check', true, $request );
  }

  /**
   * Get a collection of items
   *
   * @param WP_REST_Request $request Full data about the request.
   */

  public function get_items( $request ) {

    $parameters = $request->get_query_params();

    $default_args = array(
      'post_status'     => 'publish',
      'posts_per_page'  => 10,
      'has_password'    => false
    );
    $default_args = apply_filters( 'wp_query_route_to_rest_api_default_args', $default_args );

    // allow these args => what isn't explicitly allowed, is forbidden
    $allowed_args = array(
      'p',
      'name',
      'title',
      'page_id',
      'pagename',
      'post_parent',
      'post_parent__in',
      'post_parent__not_in',
      'post__in',
      'post__not_in',
      'post_name__in',
      'post_type', // With restrictions
      'posts_per_page', // With restrictions
      'offset',
      'paged',
      'page',
      'ignore_sticky_posts',
      'order',
      'orderby',
      'year',
      'monthnum',
      'w',
      'day',
      'hour',
      'minute',
      'second',
      'm',
      'date_query',
      'inclusive',
      'compare',
      'column',
      'relation',
      'post_mime_type',
      'lang', // Polylang
    );
    

    // Allow filtering by author: default yes
    if( apply_filters( 'wp_query_toute_to_rest_api_allow_authors', true ) ) {
      $allowed_args[] = 'author';
      $allowed_args[] = 'author_name';
      $allowed_args[] = 'author__in';
      $allowed_args[] = 'author__not_in';
    }

    // Allow filtering by meta: default yes
    if( apply_filters( 'wp_query_toute_to_rest_api_allow_meta', true ) ) {
      $allowed_args[] = 'meta_key';
      $allowed_args[] = 'meta_value';
      $allowed_args[] = 'meta_value_num';
      $allowed_args[] = 'meta_compare';
      $allowed_args[] = 'meta_query';
    }

    // Allow search: default yes
    if( apply_filters( 'wp_query_toute_to_rest_api_allow_search', true ) ) {
      $allowed_args[] = 's';
    }

    // Allow filtering by taxonomies: default yes
    if( apply_filters( 'wp_query_toute_to_rest_api_allow_taxonomies', true ) ) {
      $allowed_args[] = 'cat';
      $allowed_args[] = 'category_name';
      $allowed_args[] = 'category__and';
      $allowed_args[] = 'category__in';
      $allowed_args[] = 'category__not_in';
      $allowed_args[] = 'tag';
      $allowed_args[] = 'tag_id';
      $allowed_args[] = 'tag__and';
      $allowed_args[] = 'tag__in';
      $allowed_args[] = 'tag__not_in';
      $allowed_args[] = 'tag_slug__and';
      $allowed_args[] = 'tag_slug__in';
      $allowed_args[] = 'tax_query';
    }

    // let themes and plugins ultimately decide what to allow
    $allowed_args = apply_filters( 'wp_query_route_to_rest_api_allowed_args', $allowed_args );

    // args from url
    $query_args = array();

    foreach ( $parameters as $key => $value ) {

      // skip keys that are not explicitly allowed
      if( in_array( $key, $allowed_args ) ) {

        switch ( $key ) {

          // Posts type restrictions
          case 'post_type':

            // Multiple values
            if( is_array( $value ) ) { 
              foreach ( $value as $sub_key => $sub_value ) {
                // Bail if there's even one post type that's not allowed
                if( !$this->check_is_post_type_allowed( $sub_value ) ) {
                  $query_args[ $key ] = 'post';
                  break;
                }
              }

            // Value "any"
            } elseif ( $value == 'any' ) {
              $query_args[ $key ] = $this->_get_allowed_post_types();
              break;

            // Single value
            } elseif ( !$this->check_is_post_type_allowed( $value ) ) {
              $query_args[ $key ] = 'post';
              break;
            }

            $query_args[ $key ] = $value;
            break;

          // Posts per page restrictions
          case 'posts_per_page':

            $max_pages = apply_filters( 'wp_query_route_to_rest_api_max_posts_per_page', 50 );
            if( $value <= 0 || $value > $max_pages ) {
              $query_args[ $key ] = $max_pages;
              break;
            }
            $query_args[ $key ] = $value;
            break;

          // Posts per page restrictions
          case 'posts_status':

            // Multiple values
            if( is_array( $value ) ) { 
              foreach ( $value as $sub_key => $sub_value ) {
                // Bail if there's even one post status that's not allowed
                if( !$this->check_is_post_status_allowed( $sub_value ) ) {
                  $query_args[ $key ] = 'publish';
                  break;
                }
              }

            // Value "any"
            } elseif ( $value == 'any' ) {
              $query_args[ $key ] = $this->_get_allowed_post_status();
              break;

            // Single value
            } elseif ( !$this->check_is_post_status_allowed( $value ) ) {
              $query_args[ $key ] = 'publish';
              break;
            }

            $query_args[ $key ] = $value;
            break;

          // Set given value
          default:
            $query_args[ $key ] = $value;
            break;
        }
      }
    }

    // Combine defaults and query_args
    $args = wp_parse_args( $query_args, $default_args );

    // Make all the values filterable
    foreach ($args as $key => $value) {
      $args[$key] = apply_filters( 'wp_query_route_to_rest_api_arg_value', $value, $key, $args );
    }

    // Before query: hook your plugins here
    do_action( 'wp_query_route_to_rest_api_before_query', $args );

    // Run query
    $wp_query = new WP_Query( $args );

    // After query: hook your plugins here
    do_action( 'wp_query_route_to_rest_api_after_query', $wp_query );

    $data = array();
    $data = apply_filters( 'wp_query_route_to_rest_api_default_data', $data );

    while ( $wp_query->have_posts() ) : $wp_query->the_post();
      
      // Extra safety check for unallowed posts
      if ( $this->check_is_post_allowed( $wp_query->post ) ) {
        // After loop hook
        apply_filters( 'wp_query_route_to_rest_api_after_loop', $data, $wp_query );

        // Update properties post_type and meta to match current post_type
        // This is kind of hacky, but the parent WP_REST_Posts_Controller
        // does all kinds of assumptions from properties $post_type and
        // $meta so we need to update it several times.
        // Allow filtering by meta: default yes
        if( apply_filters( 'wp_query_route_to_rest_api_update_post_type_meta', true ) ) {
          $this->post_type = $wp_query->post->post_type;
          $this->meta = new WP_REST_Post_Meta_Fields( $wp_query->post->post_type );
        }

        // Use parent class functions to prepare the post
        if( apply_filters( 'wp_query_route_to_rest_api_use_parent_class', true ) ) {
          $itemdata = parent::prepare_item_for_response( $wp_query->post, $request );
          $data[]   = parent::prepare_response_for_collection( $itemdata );
        }
      }

    endwhile;
 
    return $this->get_response( $request, $args, $wp_query, $data );
  }

  /**
   * Get response
   *
   * @access protected
   *
   * @param WP_REST_Request $request Full details about the request
   * @param array $args WP_Query args
   * @param WP_Query $wp_query
   * @param array $data response data
   *
   * @return WP_REST_Response
   */

  protected function get_response( $request, $args, $wp_query, $data ) {

    // Prepare data
    $response = new WP_REST_Response( $data, 200 );
  
    // Total amount of posts
    $response->header( 'X-WP-Total', intval( $wp_query->found_posts ) );
    
    // Total number of pages
    $max_pages = ( absint( $args[ 'posts_per_page' ] ) == 0 ) ? 1 : ceil( $wp_query->found_posts / $args[ 'posts_per_page' ] );
    $response->header( 'X-WP-TotalPages', intval( $max_pages ) );

    return $response;
  }

  /**
   * Get allowed post status
   *
   * @access protected
   *
   * @return array $post_status
   */

  protected function _get_allowed_post_status() {
    $post_status = array( 'publish' );
    return apply_filters( 'wp_query_route_to_rest_api_allowed_post_status', $post_status );
  }

  /**
   * Check is post status allowed
   *
   * @access protected
   *
   * @return abool
   */

  protected function check_is_post_status_allowed( $post_status ) {
    return in_array( $post_status, $this->_get_allowed_post_status() );
  }

  /**
   * Get allowed post types
   *
   * @access protected
   *
   * @return array $post_types
   */

  protected function _get_allowed_post_types() {
    $post_types = get_post_types( array( 'show_in_rest' => true ) );
    return apply_filters( 'wp_query_route_to_rest_api_allowed_post_types', $post_types );
  }

  /**
   * Check is post type allowed
   *
   * @access protected
   *
   * @return abool
   */

  protected function check_is_post_type_allowed( $post_type ) {
    return in_array( $post_type, $this->_get_allowed_post_types() );
  }

  /**
   * Post is allowed
   *
   * @access protected
   *
   * @return bool
   */

  protected function check_is_post_allowed( $post ) {
    
    // Is allowed post_status
    if( !$this->check_is_post_status_allowed( $post->post_status ) ) {
      return false;
    }

    // Is allowed post_type
    if( !$this->check_is_post_type_allowed( $post->post_type ) ) {
      return false;
    }

    return apply_filters( 'wp_query_route_to_rest_api_post_is_allowed', true, $post );

  }

  /**
   * Plugin compatibility args
   *
   * @param array $args 
   *
   * @return array $args
   */

  public function plugin_compatibility_args( $args ) {

    // Polylang compatibility
    $args[] = 'lang';

    return $args;
  }

  /**
   * Plugin compatibility after query
   *
   * @param WP_Query $wp_query 
   */

  public function plugin_compatibility_after_query( $wp_query ) {

    // Relevanssi compatibility
    if( function_exists( 'relevanssi_do_query' ) && !empty( $wp_query->query_vars[ 's' ] ) ) {
      relevanssi_do_query( $wp_query );
    }

  }

}

/**
 * Init only when needed
 */

function wp_query_route_to_rest_api_init() {
  new WP_Query_Route_To_REST_API();
}
add_action( 'rest_api_init', 'wp_query_route_to_rest_api_init' );

