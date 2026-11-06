<?php

namespace App\Actions\Imports;

use RuntimeException;

/**
 * The whole file cannot be imported, e.g. a required column is missing.
 */
class InvalidImportFile extends RuntimeException {}
