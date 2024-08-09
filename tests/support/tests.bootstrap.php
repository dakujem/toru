<?php

declare(strict_types=1);

use Dakujem\Toru\Dash;

require_once __DIR__ . '/../../vendor/autoload.php';

//
// Put common test stuff into this script (autoloaded via composer).
//
function _dash(iterable $input): Dash
{
    return Dash::collect($input);
}

//(function () {
//    //
//})();
