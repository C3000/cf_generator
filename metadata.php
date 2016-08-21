<?php
/**
 * @copyright Markus Lehmann 2016
 * @author Markus Lehmann <mar_lehmann@hotmail.com>
 * @module cf_generator
 * @since 21.08.16
 */

$sMetadataVersion = '1.0';

$aModule = array(
	'id'			=>	'cf_generator',
	'title'			=>	'cf_generator',
	'description'	=>	'Generates Modules and Classes (Core, Controllers, ...)',
	'version'		=>	'1.0.0',
	'author'		=>	'Markus Lehmann',
	'email'			=>	'mar_lehmann@hotmail.com',

	'extend'		=>	array(
	),

	'files'			=>	array(
	    'cf_generator'          =>  'cf_generator/core/cf_generator.php',
        'cf_generator_events'   =>  'cf_generator/events/cf_generator_events.php',
	),

	'templates'		=>	array(
	),

	'blocks'		=>	array(
	),

	'settings'		=>	array(
        array(
            'group' => 'cf_generator_signature',
            'name'  => 'cf_generator_signature_author',
            'type'  => 'str',
            'value' => ''
        ),
        array(
            'group' => 'cf_generator_signature',
            'name'  => 'cf_generator_signature_mail',
            'type'  => 'str',
            'value' => ''
        ),
        array(
            'group' => 'cf_generator_signature',
            'name'  => 'cf_generator_signature_link',
            'type'  => 'str',
            'value' => ''
        ),
        array(
            'group' => 'cf_generator_signature',
            'name'  => 'cf_generator_signature_desc',
            'type'  => 'str',
            'value' => ''
        ),
        array(
            'group' => 'cf_generator_signature',
            'name'  => 'cf_generator_signature_copyright',
            'type'  => 'str',
            'value' => ''
        )
	),

	'events'		=>	array(
        'onActivate'        =>  'cf_generator_events::onActivate',
        'onDeactivate'      =>  'cf_generator_events::onDeActivate'
	),
);