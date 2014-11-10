<?php
// prevent execution if we're not on the command line
if ( 'cli' != php_sapi_name() )
{
	die;
}

// Open README.md
$readme_file    = fopen( dirname( __DIR__ ) . '/README.md', 'r' );
$readme_content = fread( $readme_file, filesize( dirname( __DIR__ ) . '/README.md' ) );
fclose($readme_file);

$offset_adjustment = 0;

// Fix H1s
if ( preg_match_all( '/^#(?!#)(.)+/m', $readme_content, $matches, PREG_OFFSET_CAPTURE ) )
{
	foreach ( $matches[0] as $match )
	{
		$find = $match[0];
		$replace = '=== ' . trim( preg_replace( '/^#+|#+$/', '', $find ) ) . ' ===';

		$readme_content = substr_replace( $readme_content, $replace, $match[1] + $offset_adjustment, strlen( $find ) );

		$offset_adjustment = $offset_adjustment + ( strlen( $replace ) - strlen( $find ) );
	} // END foreach
} // END if

$offset_adjustment = 0;

// Fix H2s
if ( preg_match_all( '/^##(?!#)(.)+/m', $readme_content, $matches, PREG_OFFSET_CAPTURE ) )
{
	foreach ( $matches[0] as $match )
	{
		$find = $match[0];
		$replace = '== ' . trim( preg_replace( '/^#+|#+$/', '', $find ) ) . ' ==';

		$readme_content = substr_replace( $readme_content, $replace, $match[1] + $offset_adjustment, strlen( $find ) );

		$offset_adjustment = $offset_adjustment + ( strlen( $replace ) - strlen( $find ) );
	} // END foreach
} // END if

$offset_adjustment = 0;

// Fix H3s
if ( preg_match_all( '/^###(?!#)(.)+/m', $readme_content, $matches, PREG_OFFSET_CAPTURE ) )
{
	foreach ( $matches[0] as $match )
	{
		$find = $match[0];
		$replace = '= ' . trim( preg_replace( '/^#+|#+$/', '', $find ) ) . ' =';

		$readme_content = substr_replace( $readme_content, $replace, $match[1] + $offset_adjustment, strlen( $find ) );

		$offset_adjustment = $offset_adjustment + ( strlen( $replace ) - strlen( $find ) );
	} // END foreach
} // END if

$offset_adjustment = 0;

// Fix code comments that have a language indicated
if ( preg_match_all( '/```[a-z]+(?!`)\n/m', $readme_content, $matches, PREG_OFFSET_CAPTURE ) )
{
	foreach ( $matches[0] as $match )
	{
		$find = $match[0];
		$replace = "```\n";

		$readme_content = substr_replace( $readme_content, $replace, $match[1] + $offset_adjustment, strlen( $find ) );

		$offset_adjustment = $offset_adjustment + ( strlen( $replace ) - strlen( $find ) );
	} // END foreach
} // END if

return $readme_content;