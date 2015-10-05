<?php

include './vendor/autoload.php';

from('./lib.php')->import('hello');

// is Ok
hello('Fabien');

// throw an fatal error
by('Fabien');
