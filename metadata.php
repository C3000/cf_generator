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
    'description'	=>	'Generates modules and classes (Core, Controllers, ...)',
    'version'		=>	'1.0.0',
    'author'		=>	'Markus Lehmann',
    'email'			=>	'mar_lehmann@hotmail.com',

    'extend'		=>	array(
        'cf_command'    =>  array(
                                    'cf_generator/core/cf_metadata__cf_command',
                                    'cf_generator/core/cf_generator__cf_command'
                            )
    ),

    'files'			=>	array(
        // core
        'cf_command'            =>  'cf_generator/core/cf_command.php',
        'cf_oxid_data_provider' =>  'cf_generator/core/cf_oxid_data_provider.php',

        // events
        'cf_generator_events'   =>  'cf_generator/events/cf_generator_events.php',
    ),

    'templates'		=>	array(
    ),

    'blocks'		=>	array(
        array('template'  => 'news_main.tpl',
              'block'     => 'admin_news_main_form',
              'file'      => 'test1.tpl'),
        array('template'  => 'page/account/dashboard.tpl',
              'block'     => 'head_link_favicon',
              'file'      => 'test2.tpl'),
        array('template'  => 'layout/base.tpl',
              'block'     => 'head_link_favicon',
              'file'      => 'views/blocks/test4.tpl'),
        array('template'  => 'news_main.tpl',
              'block'     => 'admin_news_main_form',
              'file'      => 'views/test3.tpl'),
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