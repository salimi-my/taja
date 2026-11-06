<?php

namespace App\Actions\Imports;

/**
 * How the text of a CSV cell is turned into a value before it is validated.
 *
 * A cell that cannot be understood is passed on as written, so the normal
 * validation rules reject it and name the column.
 */
enum ImportCast
{
    case Text;
    case Boolean;
    case Territories;
    case RightsTypes;
    case Status;
    case Role;
}
