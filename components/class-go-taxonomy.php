<?php

class GO_Taxonomy
{
	public $config = array();

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ), 1 );
		$this->config( apply_filters( 'go_config', false, 'go-taxonomy' ) );
		add_filter( 'the_category_rss', array( $this, 'the_category_rss' ) );
	}//end __construct

	/**
	 * set the plugin's config
	 */
	public function config( $config = array() )
	{
		if ( $config )
		{
			$this->config = $config;
		}//end if

		return $this->config;
	}//end config

	/**
	 * hooked into the init action
	 */
	public function init()
	{
		$this->register();
	}//end init

	/**
	 * register taxonomies based on config data
	 */
	public function register()
	{
		if ( ! $this->config || ! is_array( $this->config ) )
		{
			return new WP_Error( 'invalid_config', 'GO_Taxonomy config is empty or malformed' );
		}//end if

		foreach ( $this->config as $slug => $taxonomy )
		{
			register_taxonomy(
				$slug,
				$taxonomy['object_type'],
				$taxonomy['args']
			);
		}//end foreach
	}//end register

	/**
	 * sort array of terms by count
	 */
	public function sort_terms( $terms )
	{
		if ( ! is_array( $terms ) )
		{
			return;
		}// end if

		usort( $terms, array( $this, 'count_compare' ) );
	}//end sort_terms

	/**
	 * compare the count attributes on two objects
	 */
	private function count_compare( $a, $b )
	{
		if ( ! is_object( $a ) || ! is_object( $b ) )
		{
			return 0;
		}// end if

		if ( $a->count == $b->count )
		{
			return 0;
		}// end if

		return ( $a->count > $b->count ) ? -1 : 1;
	}//end count_compare

	/**
	 * @uses apply_filters() Calls 'the_category_rss' with category parameter
	 * adds domain attributes to category element
	 */
	public function the_category_rss( $category, $type )
	{
		// get the taxonomy from the post:
		$post_id = get_the_ID();

		if ( $post_id < 1 )
		{	// get_the_ID returned a dodgy post ID for this item, return nothing:
			return '';
		}
		else
		{
			$out = '';

			// get the taxonomies to find terms for, from current config:
			$taxonomies = array('company', 'technology', 'vertical', 'analystservices', 'go-type');
			// use these to obtain term-taxonomy objects:
			$term_tax_objs = wp_get_object_terms( $post_id, $taxonomies );

			if( ! empty( $term_tax_objs ) && ! is_wp_error( $term_tax_objs ) )
			{
				foreach( $term_tax_objs as $term_tax_obj )
				{
					$url = get_term_link( $term_tax_obj );
					$url_holder = explode('/', $url );
					array_pop($url_holder); // remove trailing space
					$label_slug = array_pop($url_holder);
					$protocol = array_shift($url_holder); // get protocol (future proofing - not nec. required now)
					array_shift($url_holder); // remove space
					$url_domain = implode('/', $url_holder);

					// return in the rss in spec'd format:
					$final_url = '<category domain="' . $protocol . '://' . $url_domain . '/' . '">' . '<![CDATA[' . $term_tax_obj->name . ']]>' . '</category>';
					$out .= $final_url;
				}// end foreach
			}// end if
		}// end if

		return $out;
	}//end the_category_rss
}//end class

function go_taxonomy()
{
	global $go_taxonomy;

	if ( ! isset( $go_taxonomy ) || ! $go_taxonomy )
	{
		$go_taxonomy = new GO_Taxonomy;
	}//end if

	return $go_taxonomy;
}//end go_taxonomy
