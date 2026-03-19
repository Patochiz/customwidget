
## Constantes Dolibarr (fichier conf.php) 
dolibarr_main_url_root = https://diamant-industrie.com:443/doli (currently overwritten by autodetected value: https://diamant-industrie.com/doli)
dolibarr_main_url_root_alt = /custom 
dolibarr_main_document_root = /home/diamanti/www/doli 
dolibarr_main_document_root_alt = /home/diamanti/www/doli/custom 
dolibarr_main_data_root = /home/diamanti/www/doli/documents

En règle générale, essayer de séparer les fonctions et procédures dans des fichiers séparés pour avoir de petits fichiers et faciliter le débogage.

## Méthode d'inclusion de main.inc.php
/ // Try main.inc.php using relative path
   if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
   if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
   if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
   if (!$res) die("Include of main fails");

Voici l'arborescence à respecter pour l'organisation des fichiers d'un module (cette arborescence sera celle prise d'office si vous générer les fichiers à l'aide de ModuleBuilder).

mymodule/* contains php pages (note that you can also add any other subdir of your choice). Note: If your module is a metapackage (a module that will embed other modules in same zip, you must put here a file metapackage.conf)
mymodule/build/ can contains any file you develop for compiling or building package
mymodule/core/modules/ contains module descriptor file modMyModule.class.php
mymodule/core/triggers contains triggers provided by module
mymodule/admin/ contains pages to setup module
mymodule/class/ contains PHP class files provided by module
mymodule/css contains CSS files provided by module
mymodule/js contains javascript files provided by module to add new functions
mymodule/docs to provide doc and licence files
mymodule/img contains images files provided by module
mymodule/langs/xx_XX contains language files for language xx_XX (try to put at least en_US)
mymodule/lib contains libraries provided and used by module
mymodule/scripts to provide command line tools or scripts. Note: Command lines script must start with line #!/usr/bin/env php
mymodule/sql contains SQL file provided by module to add new tables or indexes
mymodule/theme/mytheme if module provide its own theme/skin

## AJAX Dolibarr
- **Utiliser FormData plutôt que JSON pur pour POST**
- Toujours vérifier `isModEnabled()` et permissions
- Support FormData + JSON fallback côté PHP
- Token CSRF automatique avec FormData

## HOOKS Dolibarr
Les hooks doivent toujours être déclarés dans le fichier actions_mymodule.class.php même s'ils sont dans un fichier annexe.
Exemple de action_mymodule.class.php : 
require_once __DIR__.'/../core/hooks/mymodule.class.php';
