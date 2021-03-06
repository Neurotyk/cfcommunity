<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RTMediaAttributesModel
 *
 * @author ritz
 */
if( !class_exists( 'RTMediaAttributesModel' ) ) {
	class RTMediaAttributesModel extends RTDBModel {
		public function __construct() {
			parent::__construct( 'rtm_attribute_taxonomies', false, 10, true );
		}

		function attribute_exists( $attribute_name ) {
			$attributes = $this->get_all_attributes();
			foreach ( $attributes as $attribute ) {
				if ( $attribute_name == $attribute->attribute_name ) {
					return true;
				}
			}
			return false;
		}

		function get_all_attributes() {
			return parent::get( array() );
		}

		function get_attribute( $attribute_id ) {
			$args = array( 'id' => $attribute_id );
			$attribute = parent::get( $args );
			if ( empty( $attribute ) ) {
				return false;
			}
			return $attribute[0];
		}

		function get_attribute_name( $attribute_id ) {
			$attribute = $this->get_attribute( $attribute_id );
			if ( empty( $attribute ) ) {
				return false;
			}
			return $attribute->attribute_name;
		}

		function add_attribute( $data ) {
			return parent::insert( $data );
		}

		function update_attribute( $data, $where ) {
			return parent::update( $data, $where );
		}

		function delete_attribute( $where ) {
			return parent::delete( $where );
		}
	}
}
