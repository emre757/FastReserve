<?php

namespace App\Data;

use Illuminate\Database\Eloquent\Model;

final readonly class AuditData {
    public function __construct(
        public Model $subject,
        public string $action,
        public ?int $teamId = null,
        public ?Model $actor = null,
        public array $old_values = [],
        public array $new_values = [],
        public array $metadata = [],
    ) {}
}
