<?php
// Testes BR sem banco: so o autoload. Ver br-customizations/README.md.
define('ENVIRONMENT', 'test');
date_default_timezone_set('UTC');
require '/app/src/vendor/autoload.php';
