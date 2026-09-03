<?php

namespace App\Actions\Offerings;

use App\Models\Offering;
use App\Models\User;
use Carbon\Carbon;

final class CreateOffering
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(User $user, array $data): Offering
    {
        // convert all dates to the correct format  & to utc
        foreach ([
            'starts_at',
            'ends_at',
            'booking_deadline_at',
            'cancellation_deadline_at',
        ] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Carbon::createFromFormat(
                    '!Y-m-d\TH:i',
                    $data[$field],
                    $data['timezone'],
                )->utc();
            }
        }

        return $user->currentTeam->offerings()->create($data);
    }
}
