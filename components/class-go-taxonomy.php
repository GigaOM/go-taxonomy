<?php

class GO_Taxonomy
{
	public $config = array();

	public function __construct()
	{
		$this->config( apply_filters( 'go_config', false, 'go-taxonomy' ) );

		add_action( 'init', array( $this, 'init' ), 1 );
		add_filter( 'the_category_rss', array( $this, 'the_category_rss' ), 10, 2 );
		add_filter( 'go_taxonomy_sorted_terms', array( $this, 'sorted_terms_filter' ), 1, 3 );
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
		//assuming if we get an array, go-config has populated it correctly
		if ( ! is_array( $this->config['register_taxonomies'] ) )
		{
			return new WP_Error( 'invalid_config', 'GO_Taxonomy config is empty or malformed' );
		}//end if

		foreach ( $this->config['register_taxonomies'] as $slug => $taxonomy )
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
	 * Hooks to the_category_rss filter hook and returns new category/subject meta with scheme/domain/term depending on feed type
	 *
	 * @param string $categories The existing category meta for hte post
	 * @param string $type The type of feed being generated (i.e. atom, rdf, rss2 )
	 */
	public function the_category_rss( $categories, $type )
	{
		if ( ! isset( $this->config['the_category_rss_taxonomies'] ) )
		{
			return $categories;
		}//end if

		// get the taxonomy from the post:
		$post_id = get_the_ID();

		if ( $post_id < 1 )
		{
			// get_the_ID returned a dodgy post ID for this item, return nothing:
			return $categories;
		}// end if

		// get the taxonomies to find terms for, from current config:
		$taxonomies = array_values( $this->config['the_category_rss_taxonomies'] );

		// use these to obtain term-taxonomy objects:
		$terms = wp_get_object_terms( $post_id, $taxonomies );

		if ( empty( $terms ) || is_wp_error( $terms ) )
		{
			return $categories;
		}// end if

		// if we reach here, then rather than amending and/or returning existing $categories, we're going to rewrite them instead, due to issues with how WP is crafting the output:
		$categories = '';

		foreach ( $terms as $term )
		{
			$term_link_url = get_term_link( $term );

			if ( ! isset( $scheme_url[ $term->taxonomy ][ $term->slug ] ) )
			{
				$scheme_url[ $term->taxonomy ] = preg_replace( '#' . $term->slug . '/?#', '', $term_link_url );
			}// end if

			// return in the rss in spec'd format:
			if ( 'atom' == $type )
			{
				$categories .= sprintf(
					'<category scheme="%1$s" term="%2$s" label="%3$s"><![CDATA[%4$s]]></category>' . "\n\t\t",
					esc_url_raw( $scheme_url[ $term->taxonomy ] ),
					esc_url_raw( $term_link_url ),
					esc_attr( $term->name ),
					esc_attr( $term->name )
				);
			}// end if
			elseif ( 'rdf' == $type )
			{
				$categories .= sprintf(
					'<dc:subject><![CDATA["%1$s"]]></dc:subject>' . "\n\t\t",
					esc_attr( $term->name )
				);
			}// end elseif
			else
			{
				$categories .= sprintf(
					'<category domain="%1$s"><![CDATA[%2$s]]></category>' . "\n\t\t",
					esc_url_raw( $scheme_url[ $term->taxonomy ] ),
					esc_attr( $term->name )
				);
			}//end else
		}// end foreach

		return $categories;
	}//end the_category_rss

	/**
	 * Returns terms for a post sorted by name or by term usage (i.e. count)
	 *
	 * @param int $post_id The ID of the post you want sorted terms for
	 * @param array $args Array of parameters for the function
	 */
	public function sorted_terms( $post_id = NULL, $args = array() )
	{
		if ( ! $post_id && $post = get_post() )
		{
			$post_id = $post->ID;
		} // END if

		if ( ! $post_id )
		{
			return FALSE;
		} // END if

		$post = get_post( $post_id );

		$defaults = array(
			'taxonomies' => array( 'post_tag' ),
			'number'     => 99,
			'format'     => 'list',
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$hash = md5( $post_id . serialize( $args ) );

		// Check the terms cache if it exists and update it if necessary
		if ( $cache = wp_cache_get( $hash, 'go-taxonomy-terms' ) )
		{
			if ( ! isset( $cache['cache_time'] ) || mysql2date( 'U', $post->post_updated_gmt ) > $cache['cache_time'] )
			{
				// The cache was invalid so we delete it and continue
				wp_cache_delete( $hash, 'go-taxonomy-terms' );
			} // END if
			else
			{
				// The cache was valid so we return it
				return $cache['terms'];
			} // END else
		} // END if

		// Get the terms
		$terms = wp_get_object_terms( $post_id, $args['taxonomies'] );

		// Allow terms to be filtered by other scripts
		$terms = apply_filters( 'go_taxonomy_sorted_terms_pre', $terms, $post_id );

		if ( ! $terms )
		{
			switch ( $args['format'] )
			{
				case 'array' :
					return array();
					break;
				default :
					return '';
			}//end switch
		}//end if
		
		// @TODO Split this out into a seperate method so term sorting can be done independent of a post
		$counts = array();
		foreach ( $terms as $term )
		{
			$counts[ $term->slug ]   = $term->count;
			$term_info[ $term->slug ] = $term;
		}//end foreach

		if ( ! $counts )
		{
			return;
		}//end if

		asort( $counts );

		if ( $args['number'] > 0 )
		{
			$counts = array_slice( $counts, -$args['number'], $args['number'], TRUE );
		}//end if

		// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
		if ( 'name' == $args['orderby'] ) // name sort
		{
			uksort( $counts, 'strnatcasecmp' );
		}// end if
		else // sort by term count
		{
			asort( $counts );
		}// end else

		if ( 'DESC' == $args['order'] )
		{
			$counts = array_reverse( $counts, TRUE );
		}//end if

		// Allow sorted terms to be filtered by other scripts
		$counts = apply_filters( 'go_taxonomy_sorted_terms_post', $counts, $post_id );

		$a     = array();
		$names = array();

		foreach ( $counts as $term => $count )
		{
			$link = get_term_link( $term, $term_info[ $term ]->taxonomy );

			// This is a fix for a weird bug on VIP where pages that call this method sometimes fail to load
			if ( is_wp_error( $link ) )
			{
				continue;
			}

			$a[]     = '<a href="' . esc_url( $link ) . '" title="' . esc_attr( $term_info[ $term ]->name ) . '">' . stripslashes( wp_filter_nohtml_kses( $term_info[ $term ]->name ) ) . '</a>';
			$names[] = $term_info[ $term ]->name;
		}//end foreach

		$cache = array();

		switch ( $args['format'] )
		{
			case 'array' :
				$cache['terms'] = $a;
				break;
			case 'name' :
				$cache['terms'] = $names;
				break;
			default :
				$cache['terms'] = "<ul class='breadcrumbs sorted_tags' itemprop='keywords'>\n\t<li>" . join( "</li>\n\t<li>", $a ) . "</li>\n</ul>\n";
		}//end switch

		$cache['cache_time'] = time();
		wp_cache_set( $hash, $cache, 'go-taxonomy-terms' );

		return $cache['terms'];
	}//end sorted_terms

	/**
	 * Returns sorted tags for a post
	 */
	public function sorted_terms_filter( $terms, $post, $args )
	{
		// @TODO Once the sort stuff has been split out of the sorted_terms method this won't need to check for a post anymore
		if ( ! $post )
		{
			return $terms;
		}//end if

		return $go_post->sorted_tags( $post->ID, $args );
	}//end sorted_terms_filter
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
