<?php
/**
 * @copyright Valiton GmbH
 * @author Markus Lehmann
 * @module cf_generator
 * @since 31.10.16
 */

$sMetadataVersion   =  '1.0';

$aModule            =  array(
    'id'          =>  'cf_generator',
    'title'       =>  'cf_generator',
    'description' =>  'Generates modules and classes (Core, Controllers, ...)',
    'version'     =>  '1.0.0',
    'author'      =>  'Markus Lehmann',
    'email'       =>  'mar_lehmann@hotmail.com',

    'extend'      =>  array(
        //

        'cf_command'  =>  array(
            'cf_generator/core/cf_metadata__cf_command',
            'cf_generator/core/cf_generator__cf_command',
        ),
    ),

    'files'       =>  array(
        'cf_command'              =>  'cf_generator/core/cf_command.php',
        'cf_oxid_data_provider'   =>  'cf_generator/core/cf_oxid_data_provider.php',
        'cf_generator_events'     =>  'cf_generator/events/cf_generator_events.php',
    ),

    'templates'   =>  array(
    ),

    'blocks'      =>  array(
    ),

    'settings'    =>  array(
        array(
            'group'   =>  'cf_generator_signature',
            'name'    =>  'cf_generator_signature_author',
            'type'    =>  'str',
            'value'   =>  '',
        ),
        array(
            'group'   =>  'cf_generator_signature',
            'name'    =>  'cf_generator_signature_mail',
            'type'    =>  'str',
            'value'   =>  '',
        ),
        array(
            'group'   =>  'cf_generator_signature',
            'name'    =>  'cf_generator_signature_link',
            'type'    =>  'str',
            'value'   =>  '',
        ),
        array(
            'group'   =>  'cf_generator_signature',
            'name'    =>  'cf_generator_signature_desc',
            'type'    =>  'str',
            'value'   =>  '',
        ),
        array(
            'group'   =>  'cf_generator_signature',
            'name'    =>  'cf_generator_signature_copyright',
            'type'    =>  'str',
            'value'   =>  '',
        ),
    ),

    'events'      =>  array(
        'onActivate'      =>  'cf_generator_events::onActivate',
        'onDeactivate'    =>  'cf_generator_events::onDeActivate',
    ),
);
