<?php

namespace App\Http\Requests\Reservations;

use App\Models\Offering;
use App\Models\Reservation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $offering = $this->route('offering');

        return $offering instanceof Offering
            && $this->user()->can('create', [
                Reservation::class,
                $offering,
            ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'spots' => ['required', 'integer', 'min:1'],
        ];
    }
}
