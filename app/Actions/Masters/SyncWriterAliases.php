<?php

namespace App\Actions\Masters;

use App\Models\Writer;

/**
 * Replaces the pseudonyms of a writer. Positions follow the submitted order.
 */
class SyncWriterAliases
{
    /**
     * @param  list<array{name: string, ipi_base_number?: string|null}>  $aliases
     */
    public function __invoke(Writer $writer, array $aliases): void
    {
        $writer->aliases()->delete();

        foreach ($aliases as $index => $alias) {
            $writer->aliases()->create([
                'name' => $alias['name'],
                'ipi_base_number' => $alias['ipi_base_number'] ?? null,
                'position' => $index + 1,
            ]);
        }
    }
}
