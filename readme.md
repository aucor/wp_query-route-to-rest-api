# WP_Query Route To REST API

**Contributors:** [Teemu Suoranta](https://github.com/TeemuSuoranta)


**Tags:** WordPress, REST API, WP_Query


**License:** GPLv2+

## Description

Adds new route `/wp-json/wp_query/args/` to REST API. You can query content with WP_Query args. There's extensive filters and actions to limit or extend functionality.

## How to use

### Basic usage

**Route**: `/wp-json/wp_query/args/` 

**Get three projects**: `/wp-json/wp_query/args/?post_type=project&posts_per_page=3`

**You shoudn't write query args by hand!** It gets very complicated when you want to pass arrays for example with meta_query.

### Use with PHP
**1. Create $args**
```php
$args = array(
  'post_type' => 'post',
  'orderby' => 'title',
  'order' => 'ASC'
); 
```
**2. Turn $args into query string** [(Reference)](https://codex.wordpress.org/Function_Reference/build_query)
```php
$query_str = build_query( $args );
```

**3. Make the call**
```php
$response = wp_remote_get( 'https://your-site.local/wp-json/wp_query/args/?' . $query_str );

// Get array of "post objects"
$posts = json_decode( wp_remote_retrieve_body( $response ) );
```

## Use with JS
**1. Create args**
```js
var args = {
  'post_type': 'post',
  'orderby': 'title',
  'order': 'ASC'
}; 
```
**2 a) Create params with jQuery**
```js
var query_str = jQuery.param( args );
```
**2 b) Some other JS solution**

You tell me... There's `.toQueryString` but it might still be in prototype.

**3. Make the call**
```js
$.ajax({
  url: 'https://your.site.local/wp-json/wp_query/args/?' + query_str,
}).done(function( data ) {
  console.log( data );
});

```

## Advanced example: tax_query

Get posts that have **both** tags "wordpress" and "woocommerce"

**PHP:**
```php
$args = array(
  'post_type' => 'post',
  'tax_query' => array(
    array(
      'taxonomy' => 'post_tag',
      'field'    => 'slug',
      'terms'    => array( 'wordpress' ),
    ),
    array(
      'taxonomy' => 'post_tag',
      'field'    => 'slug',
      'terms'    => array( 'woocommerce' ),
    ),
  ),
); 
```

**JS:**

```js
var args = {
  'post_type': 'post',
  'tax_query': [
    {
      'taxonomy': 'post_tag',
      'field': 'slug',
      'terms': [ 'wordpress' ]
    },
    {
      'taxonomy': 'post_tag',
      'field': 'slug',
      'terms': [ 'woocommerce' ]
    }
  ]
}; 
```

## Advanced example: tax_query with relation

Get posts that have **either** "wordpress" **or** "woocommerce" tag. This gets tricky because JS doesn't support completely the same array structure as PHP. If you only need PHP, this is a piece of cake.

**PHP:**
```php
$args = array(
  'post_type' => 'post',
  'tax_query' => array(
    'relation' => 'OR',
    array(
      'taxonomy' => 'post_tag',
      'field'    => 'slug',
      'terms'    => array( 'wordpress' ),
    ),
    array(
      'taxonomy' => 'post_tag',
      'field'    => 'slug',
      'terms'    => array( 'woocommerce' ),
    ),
  ),
);
```

**JS:**
```js
var args = {
  'post_type': 'post',
  'tax_query': {
    'relation': 'OR',
    0: {
      'taxonomy': 'post_tag',
      'field': 'slug',
      'terms': [ 'wordpress' ]
    },
    1: {
      'taxonomy': 'post_tag',
      'field': 'slug',
      'terms': [ 'woocommerce' ]
    }
  }
};
```

For other uses, keep in mind JS object/array syntax. If there's key + value, use object `{}`. If theres only value, use array `[]`.

##Restrictions

The route `/wp-json/wp_query/args/` sets some restrictions by default for queries. These restrictions can be lifted or hardened with filters and actions.

###Allowed args
```
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
'author',
'author_name',
'author__in',
'author__not_in',
'meta_key',
'meta_value',
'meta_value_num',
'meta_compare',
'meta_query',
's',
'cat',
'category_name',
'category__and',
'category__in',
'category__not_in',
'tag',
'tag_id',
'tag__and',
'tag__in',
'tag__not_in',
'tag_slug__and',
'tag_slug__in',
'tax_query',
'lang', // Polylang
```
So biggest ones missing have something to do with getting content that you might not want to get like `post_status` drafts (add this argument to the list with filter if you need it). By default, no querying `post_passwords` or having your way with cache settings.

### Post types

By default all the post types marked "public" are available. `'post_type' => 'any'` falls back to these available post types. You can change post types with filter to what you want.

### Post status

By default, only "publish" is allowed. Add other post_status as needed with filter.

### Restriction fail-safe

Addition to restriction of WP_Query args, there is check after the query that queried posts will not be forbidden post types or post_status.

### Default WP_Query

```php
$default_args = array(
  'post_status'     => 'publish',
  'posts_per_page'  => 10,
  'has_password'    => false
);
```
 In addition to the normal defaults from WP_Query.
 
##Filters

**Add more allowed args:**
```php
function my_allowed_args($args) {
  $args[] = 'post_status';
  return $args;
}
add_filter( 'wp_query_route_to_rest_api_allowed_args', 'my_allowed_args' );

```

**Add more default args:**
```php
function my_default_args($args) {
  $args['posts_per_page'] = 5;
  return $args;
}
add_filter( 'wp_query_route_to_rest_api_default_args', 'my_default_args' );

```

**Add allowed post types:**

You can also add post types by setting `'show_in_rest' => true` when registering post type.
```php
function my_allowed_post_types($post_types) {
  $post_types[] = 'projects';
  return $post_types;
}
add_filter( 'wp_query_route_to_rest_api_allowed_post_types', 'my_allowed_post_types' );

```

**Add allowed post status:**

```php
function my_allowed_post_status($post_status) {
  $post_types[] = 'draft';
  return $post_status;
}
add_filter( 'wp_query_route_to_rest_api_allowed_post_status', 'my_allowed_post_status' );

```

**Is current post allowed:**
```php
function my_post_is_allowed($is_allowed, $post) {
  if($post->ID == 123) {
    $is_allowed = false;
  }
  return $is_allowed;
}
add_filter( 'wp_query_route_to_rest_api_post_is_allowed', 'my_post_is_allowed', 10, 2 );
```
**Alter any argument value:**
```php
function my_arg_value($value, $key, $args) {
  if($key == 'posts_per_page' && $value > 10) {
    $value = 10;
  }
  return $value;
}
add_filter( 'wp_query_route_to_rest_api_arg_value', 'my_arg_value', 10, 3 );
```

**Check permissions:**
```php
function my_permission_check($is_allowed, $request) {
  return true;
}
add_filter( 'wp_query_route_to_rest_api_permissions_check', 'my_permission_check', 10, 2 );

```

**Limit max posts per page:**
```php
function my_max_posts_per_page($max) {
  return 100; // Default 50
}
add_filter( 'wp_query_route_to_rest_api_max_posts_per_page', 'my_max_posts_per_page' );

```


##Hooks

**Before WP_Query:**
```php
function my_before_query($args) {
  // do whatever
}
add_action( 'wp_query_route_to_rest_api_before_query', 'my_before_query' );
```
**After WP_Query:**
```php
function my_after_query($wp_query) {
  // do whatever
}
add_action( 'wp_query_route_to_rest_api_after_query', 'my_after_query' );
```

## Install

Download and activate. That's it.

**Composer:**
```
$ composer aucor/wp_query-route-to-rest-api
```
**With composer.json:**
```
{
  "require": {
    "aucor/wp_query-route-to-rest-api": "*"
  },
  "extra": {
    "installer-paths": {
      "htdocs/wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
    }
  }
}
```

## Issues and feature whishlist

This is a WordPress plugin by 3rd party developer. WordPress.org or Automattic has nothing to do with this plugin. There's no warranty or quarantees. Thread carefully.

If you see a critical functionality missing, please contribute!