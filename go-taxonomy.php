<?php
/**
 * Plugin Name: Gigaom Taxonomy Registration
 * Plugin URI: http://gigaom.com/
 * Description: Registers taxonomies based on data from go-config
 * Version: 0.1
 * Author: Gigaom <support@gigaom.com>
 * Author URI: http://gigaom.com/
 * License: All Rights Reserved.
 */

require_once __DIR__ . '/components/class-go-taxonomy.php';
go_taxonomy();
