<?php
/**
 * Plugin Name:         Ultimate Member - Decimal Number Step
 * Description:         Extension to Ultimate Member for managing decimal number fields in 0.1 steps.
 * Version:             1.0.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Text Domain:         ultimate-member
 * Domain Path:         /languages
 * UM version:          2.8.5
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Decimal_Number_Step {

    public $number_values = array();
    public $number_keys   = array();
    public $settings      = 'decimal_number_step_meta_key';

    function __construct() {

        $settings = UM()->options()->get( $this->settings );
        if ( is_array( $settings )) {

            $this->number_keys = array_map( 'sanitize_text_field', $settings );

            foreach( $this->number_keys as $number_key ) {
                add_filter( "um_{$number_key}_form_edit_field", array( $this, 'um_number_step_form_edit_field' ), 10, 2 );
            }

            add_filter( 'um_submit_post_form',                  array( $this, 'um_submit_post_form_number_step' ), 10, 1 );
            add_filter( 'um_change_usermeta_for_update',        array( $this, 'um_before_save_filter_number_step' ), 10, 4);
            add_filter( 'um_submit_form_data',                  array( $this, 'um_submit_form_data_number_step' ), 10, 3 );
        }

        add_filter( 'um_settings_structure',                    array( $this, 'um_settings_structure_number_step' ), 10, 1 );
    }

    public function um_number_step_form_edit_field( $output, $set_mode ) {

        if ( $set_mode == 'profile' ) {
            foreach( $this->number_keys as $number_key ) {
                if ( strpos( $output, 'data-key="' . $number_key . '"' ) !== false ) {
                    $output = str_replace( ' type="number" ', ' type="number" step="0.1" ', $output );
                    break;
                }
            }
        }

        return $output;
    }

    public function um_submit_post_form_number_step( $post ) {
        
        foreach( $this->number_keys as $number_key ) {

            $post_key = $number_key . '-' . $post['form_id'];
            if ( isset( $post[$post_key] )) {
                $this->number_values[$number_key] = sanitize_text_field( $post[$post_key] );
            }
        }

        return $post;
    }

    public function um_submit_form_data_number_step( $post_form, $form_data_mode, $all_cf_metakeys ) {

        if ( $form_data_mode == 'profile' ) {
            foreach( $this->number_keys as $number_key ) {

                if ( isset( $post_form[$number_key] )) {
                    $post_form[$number_key] = $this->number_values[$number_key];
                    if ( isset( $post_form['submitted'][$number_key] )) {
                        $post_form['submitted'][$number_key] = $this->number_values[$number_key];
                    }
                }
            }
        }

        return $post_form;
    }

    public function um_before_save_filter_number_step( $to_update, $args, $fields, $key ) {

        if ( in_array( $key, $this->number_keys ) && isset( $to_update[$key] )) {
            $to_update[$key] = $this->number_values[$key];
        }

        return $to_update;
    }

    public function get_all_um_forms_numbers() {

        $um_forms = get_posts( array( 'post_type'   => 'um_form',
                                      'numberposts' => -1, 
                                      'post_status' => array( 'publish' )
                                    )
                            );

        $form_field_numbers = array();
        foreach ( $um_forms as $form ) {

            $um_post_meta = get_post_meta( $form->ID, '_um_custom_fields', true );

            foreach( $um_post_meta as $meta_key => $value ) {
                if ( $value['type'] == 'number' ) {
                    $form_field_numbers[$meta_key] = $value['label'] . ' - ' . $meta_key;
                }
            }
        }

        return $form_field_numbers;
    }

    public function um_settings_structure_number_step( $settings_structure ) {

        $settings_structure['appearance']['sections']['']['form_sections']['decimal_number_step']['title'] = __( 'Decimal Number Steps', 'ultimate-member' );
        $settings_structure['appearance']['sections']['']['form_sections']['decimal_number_step']['description'] = __( 'Plugin version 1.0.0 - tested with UM 2.8.5', 'ultimate-member' );

        $settings_structure['appearance']['sections']['']['form_sections']['decimal_number_step']['fields'][] =

                                                        array(
                                                            'id'            => $this->settings,
                                                            'type'          => 'select',
                                                            'multi'         => true,
                                                            'size'          => 'medium',
                                                            'options'       => $this->get_all_um_forms_numbers(),
                                                            'label'         => __( 'Select decimal number meta_key names', 'ultimate-member' ),
                                                            'description'   => __( 'Select the meta_key names for Decimal Number 0.1 Steps', 'ultimate-member' )
                                                        );

        return $settings_structure;
    }


}

new UM_Decimal_Number_Step();
