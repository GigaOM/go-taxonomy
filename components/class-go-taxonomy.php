<?php

class GO_Taxonomy
{
	public $config = array();

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ), 1 );
		$this->config( apply_filters( 'go_config', false, 'go-taxonomy' ) );
		add_filter( 'the_category_rss', array( $this, 'the_category_rss' ), 10, 2 );
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
	 * @uses apply_filters() Calls 'the_category_rss' with categories & type parameter
	 * adds domain (or scheme/label/term in case of atom) attributes to a category element in the rss output
	 */
	public function the_category_rss( $categories, $type )
	{
		// get the taxonomy from the post:
		$post_id = get_the_ID();

		if ( $post_id < 1 )
		{	// get_the_ID returned a dodgy post ID for this item, return nothing:
			return $categories;
		}

		// get the taxonomies to find terms for, from current config:
		$taxonomies = array_keys($this->config);
		// use these to obtain term-taxonomy objects:
		$terms = wp_get_object_terms( $post_id, $taxonomies );

		if( empty( $terms ) || is_wp_error( $terms ) )
		{
			return $categories;
		}
		
		// if we reach here, then rather than amending and/or returning existing $categories, we're going to rewrite them instead, due to issues with how WP is crafting the output:
		$categories = '';

		foreach( $terms as $term )
		{
			if ( ! isset( $scheme_url[ $term->taxonomy ] ) )
			{
				$term_link_url = get_term_link( $term );
				$scheme_url[ $term->taxonomy ] = preg_replace( '#' . $term->slug . '/?#', '', $term_link_url );
			}

			// return in the rss in spec'd format:
			if ( 'atom' == $type )
			{
				$categories .= "\t\t" . sprintf( 
					'<category scheme="%1$s" term="%2$s" label="%3$s"><![CDATA[%4$s]]></category>',
					esc_url_raw( $scheme_url[ $term->taxonomy ] ),
					esc_url_raw( $term_link_url ),
					esc_attr( $term->name ),
					esc_attr( $term->name ) . "\n"
				);
			}
			elseif ( 'rdf' == $type )
			{
				$categories .= "\t\t" . sprintf( 
					'<dc:subject><![CDATA["%1$s"]]></dc:subject>',
					esc_attr( $term->name ) . "\n"
				);
			}
			else 
			{
				$categories .= "\t\t" . '<category domain="' . esc_url_raw( $scheme_url[ $term->taxonomy ] ) . '">' . '<![CDATA[' . esc_html( $term->name ) . ']]>' . "</category>\n";
			}
		}// end foreach

		return $categories;
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
