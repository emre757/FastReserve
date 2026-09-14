<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasBroadcastVersion
{
    public function incrementBroadcastVersion(): int
    {
        $this->increment('broadcast_version');

        return (int) $this->getAttribute('broadcast_version');
    }
}
