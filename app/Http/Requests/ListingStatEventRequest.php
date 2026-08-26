<?php

namespace App\Http\Requests;

use App\Services\Stats\ListingStatEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingStatEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only the browser-reportable events are accepted here. Views, impressions
     * and enquiries are counted server-side, so no client can inflate them.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'listing_id' => ['required', 'string', 'max:64'],
            'event' => ['required', 'string', Rule::in(ListingStatEvent::reportableValues())],
        ];
    }

    /**
     * The validated event as its enum case.
     */
    public function event(): ListingStatEvent
    {
        return ListingStatEvent::from($this->validated('event'));
    }
}
