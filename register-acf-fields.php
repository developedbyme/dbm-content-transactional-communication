<?php
if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5c44c568e0c68',
	'title' => 'Email template',
	'fields' => array(
		array(
			'key' => 'field_5c44c595d2ad5',
			'label' => 'Email subject',
			'name' => 'dbmtc_email_subject',
			'type' => 'text',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_taxonomy',
				'operator' => '==',
				'value' => 'dbm_type:transactional-template',
			),
			array(
				'param' => 'post_taxonomy',
				'operator' => '==',
				'value' => 'dbm_relation:email',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => 1,
	'description' => '',
));

endif;
?>