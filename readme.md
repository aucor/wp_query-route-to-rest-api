# WP_Query Route To REST API

**Contributors:** [Teemu Suoranta](https://github.com/TeemuSuoranta)

**Tags:** WordPress, REST API, WP_Query

**License:** GPLv2+

<!-- MarkdownTOC autolink="true" autoanchor="true" -->

- [Description](#description)
- [How to use](#how-to-use)
  - [Basic usage](#basic-usage)
  - [Use with PHP](#use-with-php)
  - [Use with JS](#use-with-js)
- [Advanced examples](#advanced-examples)
  - [Advanced example: tax_query](#advanced-example-taxquery)
  - [Advanced example: tax_query with relation](#advanced-example-taxquery-with-relation)
  - [Advanced example: modifying existing WP_Query \(post archive, term archive, search etc\)](#advanced-example-modifying-existing-wpquery-post-archive-term-archive-search-etc)
- [Restrictions](#restrictions)
  - [Allowed args](#allowed-args)
  - [Post types](#post-types)
  - [Post status](#post-status)
  - [Restriction fail-safe](#restriction-fail-safe)
  - [Default WP_Query](#default-wpquery)
- [Extra plugin compatibility features](#extra-plugin-compatibility-features)
- [Filters](#filters)
- [Hooks](#hooks)
- [Install](#install)
- [Issues and feature whishlist](#issues-and-feature-whishlist)
- [Changelog](#changelog)
  - [1.1.1](#111)
  - [1.1](#11)

<!-- /MarkdownTOC -->


<a name="description"></a>
## Description

Adds new route `/wp-json/wp_query/args/` to REST API. You can query content with WP_Query args. There's extensive filters and actions to limit or extend functionality.

<a name="how-to-use"></a>
## How to use

<a name="basic-usage"></a>
### Basic usage

**Route**: `/wp-json/wp_query/args/` 

**Get three projects**: `/wp-json/wp_query/args/?post_type=project&posts_per_page=3`

**You shoudn't write query args by hand!** It gets very complicated when you want to pass arrays for example with meta_query.

<a name="use-with-php"></a>
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

<a name="use-with-js"></a>
### Use with JS
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
[query-string](https://www.npmjs.com/package/query-string) handles most use cases, but as query strings aren't really standardized, YMMV. 

One example of where it falls short:
```javascript
const params = {
  "paged": 1,
  "order": "desc",
  "posts_per_page": 1,
  "tax_query": [
    {
      "taxonomy": "category",
      "field": "term_id",
      "terms": [
        1
      ]
    },
    {
      "taxonomy": "category",
      "field": "term_id",
      "terms": [
        2
      ]
    }
  ]
}
```

One possible solution, ES2015:
```javascript
let qsAdditions = ''

if (params.tax_query) {
  // Define a helper method for getting a querystring part
  const part = (i, key, value) => Array.isArray(value)
    ? value.reduce((acc, v, i2) => (
      acc += `&tax_query[${i}][${key}][${i2}]=${v}`
    ), '')
    : `&tax_query[${i}][${key}]=${value}`
    
  // Loop the params and glue pieces of querystrings together
  qsAdditions += params_tax_query.reduce((acc, cond, i) => (
    acc += part(i, 'taxonomy', cond.taxonomy || 'category') +
      part(i, 'field', cond.field || 'term_id') +
      part(i, 'terms', cond.terms)
  ), '')
  
  // Delete value from object so query-string won't parse it
  delete params.tax_query
}

const query_str = querystring.stringify(params) + qsAdditions
```
**3. Make the call**
```js
$.ajax({
  url: 'https://your.site.local/wp-json/wp_query/args/?' + query_str,
}).done(function( data ) {
  console.log( data );
});

```

<a name="advanced-examples"></a>
## Advanced examples

<a name="advanced-example-taxquery"></a>
### Advanced example: tax_query

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

<a name="advanced-example-taxquery-with-relation"></a>
### Advanced example: tax_query with relation

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

<a name="advanced-example-modifying-existing-wpquery-post-archive-term-archive-search-etc"></a>
### Advanced example: modifying existing WP_Query (post archive, term archive, search etc)

Sometimes you need to create features that add small tweaks to current query that WordPress, theme or plugins has already defined. These include "load more" buttons, filters etc. You can create that query from scratch if you want, but there is a neat way to get the current query for JS.

You can add this to your `archive.php` or whatever PHP template you need:

```php
<?php
// Get the main WP_Query for archive, term, single-post etc
global $wp_query;
?>
<script>var wp_query = <?php echo json_encode( $wp_query->query ) ?>;</script>
```


Now you can access the query in JS from this var `wp_query`. Props @timiwahalahti for this idea.

<a name="restrictions"></a>
## Restrictions

The route `/wp-json/wp_query/args/` sets some restrictions by default for queries. These restrictions can be lifted or hardened with filters and actions.

<a name="allowed-args"></a>
### Allowed args
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

<a name="post-types"></a>
### Post types

By default all the post types marked `'show_in_rest' => true` are available. `'post_type' => 'any'` falls back to these post types. You can change post types with filter to what you want.

<a name="post-status"></a>
### Post status

By default, only "publish" is allowed. Add other post_status as needed with filter.

<a name="restriction-fail-safe"></a>
### Restriction fail-safe

Addition to restriction of WP_Query args, there is check after the query that queried posts will not be forbidden post types or post_status.

<a name="default-wpquery"></a>
### Default WP_Query

```php
$default_args = array(
  'post_status'     => 'publish',
  'posts_per_page'  => 10,
  'has_password'    => false
);
```
 In addition to the normal defaults from WP_Query.
 
<a name="extra-plugin-compatibility-features"></a>
## Extra plugin compatibility features

This plugin has built-in compatibility for [Relevanssi ('s' argument)](https://wordpress.org/plugins/relevanssi/) and [Polylang ('lang' argument)](https://wordpress.org/plugins/polylang/)
 
<a name="filters"></a>
## Filters

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
  $post_status[] = 'draft';
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


<a name="hooks"></a>
## Hooks

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

<a name="install"></a>
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

<a name="issues-and-feature-whishlist"></a>
## Issues and feature whishlist

This is a WordPress plugin by 3rd party developer. WordPress.org or Automattic has nothing to do with this plugin. There's no warranty or quarantees. Thread carefully.

If you see a critical functionality missing, please contribute!

<a name="changelog"></a>
## Changelog

<a name="111"></a>
### 1.1.1

Added advanced example in readme for getting PHP WP_Query for JS. Added table of contents. Made the title hierarchy more logical.

<a name="11"></a>
### 1.1

Make the return data structure same as /wp-json/wp/posts/. The data schema was missing some data before. Now the structure is inherited from the WP_REST_Posts_Controller as it should have from the start.
