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
	public function the_category_rss( $category )
	{
		// get the taxonomy from the post:
		$post_id = get_the_ID();

		if ( $post_id > 1 )
		{
			$out = '';
			// get url to use in <category domain> attribute, per this format <category domain="$base_url_for_taxonomy/$tax_name">$term_name</category>
			$url = untrailingslashit( go_local_scriblio_authority()->options['search_host'] );
			// get the taxonomies to find terms for, from current config:
			foreach ( array_keys( $this->config ) as $tax ) 
			{
				$term_objs = wp_get_object_terms( $post_id, $tax );
				if( ! empty( $term_objs ) )
				{
					if( ! is_wp_error( $term_objs ) )
					{
						foreach( $term_objs as $term_obj )
						{
							// then get tax term_name:
							$terms[] = $term_obj->name;
							// return in the rss in spec'd format:
							$final_url = '<category domain="' . $url . '/' . $tax . '/' . $term_obj->slug . '">' . $term_obj->name . '</category>';
							$out .= $final_url;
						}// end foreach
					}
					else 
					{
						// no op
						//wlog('wp_get_object_terms( $post_id, category ) returned error...');
					}
				}
				else 
				{
					// no op
					//wlog('wp_get_object_terms( $post_id, category ) empty...');
				}
			}
		}
		else
		{	// get_the_ID returned a dodgy post ID for this item, return nothing:
			return '';
		}

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
