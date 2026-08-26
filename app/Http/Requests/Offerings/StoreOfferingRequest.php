<?php

namespace App\Http\Requests\Offerings;

use App\Enums\Currency;
use App\Models\Offering;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // make sure its the current team, otherwise the form is outdated and reject it
        $user = $this->user();
        $team = $user?->currentTeam;

        return $team !== null
            && $team->slug === $this->input('team_context') // check if form is outdated
            && $user->can('create', [Offering::class, $team]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // check if form is outdated
            'team_context' => [
                'required',
                'string',
            ],
            'name' => [
                'required',
                'string',
                'max:254',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'starts_at' => [
                'required',
                'date_format:Y-m-d\TH:i',
            ],
            'ends_at' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
                'after:starts_at',
            ],
            'timezone' => [
                'required',
                'timezone',
            ],
            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],
            'price' => [
                'required',
                'decimal:0,2',
                'min:0',
                'max:999999.99',
            ],
            'currency' => [
                Rule::excludeIf(
                    fn (): bool => (float) $this->input('price', 0) === 0.0,
                ),
                Rule::requiredIf(
                    fn (): bool => (float) $this->input('price', 0) > 0,
                ),
                'nullable',
                Rule::enum(Currency::class),
            ],
            'booking_deadline_at' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
                'before_or_equal:starts_at',
            ],
            'cancellation_deadline_at' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
                'before_or_equal:starts_at',
            ],
            'hold_duration_minutes' => [
                Rule::excludeIf(
                    fn (): bool => $this->input('hold_duration_minutes') === null,
                ), // field cannot be nullable so exclude it if its null
                'integer',
                'min:1',
                'max:60',
            ],
        ];
    }
}
