#cf_generator

cf_generator is a php srcipt for OXID Shops to generate modules and classes.

##Usage

* Enable the Module in the master shop
* Then a file called generator.php is created in the bin folder
* With it you can generate modules and classes

###Module
    `php bin/generator.php generator cf_test` 
Creates module structure and a clean metadata.php

###Override-Oxid-Class
    `php bin/generator.php generator cf_test oxconfig`
1. Creates module structure if not exists.
2. Search for a oxid class oxconfig and creates an override for it called core/cf_test__oxconfig.
3. Creates a clean metadata.php or update it if already exists.

Possible classes in core, admin, controllers, models. 

###Blocks
    `php bin/generator.php generator cf_test base_js`
1. Creates module structure if not exists.
2. Search for a block in the templates and creates a template with parent call.
3. Creates a clean metadata.php or update it if already exists.

###Custom Template
    `php bin/generator.php generator cf_test test.tpl`
Creates module structure if not exists, a template under views/tpl/test.tpl and creates or updates metadata.php

###Custom Admintemplate
    `php bin/generator.php generator cf_test admin/tpl/test.tpl`
Creates module structure if not exists, a template under views/admin/tpl/test.tpl and creates or updates metadata.php

###Custom PHP-Class
    `php bin/generator.php generator cf_test controller/admin/test.php`
Creates module structure if not exists, a class under controller/admin/test.php and creates or updates metadata.php