# Gigaom Taxonomy #

* Contributors: borkweb, methnen, Camwyn, zbtirrell, okredo, misterbisson
* Tags: wordpress, taxonomies, terms
* Requires at least: 3.6.1
* Tested up to: 4.0
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Description ##

Registers taxonomies programtically from a config array and provides other helper functions.

We needed a way to add taxonomies that we rely on accross sites and managing those was becomeing cumbersome.  This allows us to manage them via a config array.

The plugin also provides workarounds for limitations in WordPress handling of terms in feeds and post term sorting.

### Sorted Terms ###

There's also a `sorted_terms` helper function you can use in plugins and templates like this:
		
```php
go_taxonomy()->sorted_terms( $post_id, $args );
```

### Parameters ###

**$post_id** 

_(int) (optional)_ The ID of the post you want to get sorted terms for

### Argument Options ###
	
**taxonomies**

_(array)_

* array( 'post_tag' ) - Default

**number**

_(int)_

* 99 - Default

**format**

_(string)_

* list - Default
* array
* name

**orderby**

_(string)_

* name - Default
* count : terms ordered by their usage count

**order**

_(string)_

* ASC - Default
* DESC

### Report Issues, Contribute Code, or Fix Stuff ###

https://github.com/GigaOM/go-taxonomy/

## Installation ##

1. Place the plugin folder in your `wp-content/plugins/` directory and activate it.
2. Follow the configuration instructions

### Configuration ###

1. Add a filter on the `go_config` hook that returns an array of taxonomies when the the 2nd paramter is `go-taxonomy`
2. Config array format example:

	```php
	array(
		'register_taxonomies' => array(
			'company' => array(
				'object_type' => 'post',
				'args' => array(
					'label'     => 'Companies',
					'query_var' => TRUE,
					'rewrite'   => array(
						'slug'    => 'company',
						'with_front' => TRUE,
						'ep_mask' => EP_TAGS,
					),
					'show_ui'   => TRUE,
				),
			),
			'person' => array(
				'object_type' => 'post',
				'args' => array(
					'label'     => 'People',
					'query_var' => TRUE,
					'rewrite'   => array(
						'slug'    => 'person',
						'with_front' => TRUE,
						'ep_mask' => EP_TAGS,
					),
					'show_ui'   => TRUE,
				),
			),
			'technology' => array(
				'object_type' => 'post',
				'args' => array(
					'label'     => 'Technologies and Products',
					'query_var' => TRUE,
					'rewrite'   => array(
						'slug'    => 'technology',
						'with_front' => TRUE,
						'ep_mask' => EP_TAGS,
					),
					'show_ui'   => TRUE,
				),
			),
		),
		// Taxonomies you want returned as category meta in feeds
		'the_category_rss_taxonomies' => array(
			'post_tag',
			'company',
			'person',
			'technology',
		),
	);
	```
