<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

function cfc_delete_plugin() {
    global $wpdb;

    $options = cfc_get_plugin_options();

    $option_file = dirname( __FILE__ ) . '/' . 'cfccf7_options.txt';
    if ( file_exists( $option_file ) ) {
        unlink( $option_file );
        foreach( $options as $key=>$valuve ) {
            delete_option( $key );
        }
    }
}

function cfc_get_plugin_options() {
    global $wpdb;

    $values = array();
    $results = $wpdb->get_results( "
        SELECT *
          FROM $wpdb->options
         WHERE 1 = 1
           AND option_name like 'mcf_cf7_%'
         ORDER BY option_name
    " );

    foreach ( $results as $result ) {
        $values[ $result->option_name ] = $result->option_value;
    }

    return $values;
}

cfc_delete_plugin();

