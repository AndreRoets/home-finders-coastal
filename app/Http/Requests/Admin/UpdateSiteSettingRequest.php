<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['nullable', 'string', 'max:255'],
            'default_meta_description' => ['nullable', 'string', 'max:500'],
            'default_og_image' => ['nullable', 'url', 'max:255'],
            'default_twitter_handle' => ['nullable', 'string', 'max:255'],

            'ga4_measurement_id' => ['nullable', 'string', 'max:50'],
            'gtm_container_id' => ['nullable', 'string', 'max:50'],
            'google_search_console_verification' => ['nullable', 'string', 'max:255'],
            'google_ads_conversion_id' => ['nullable', 'string', 'max:50'],
            'google_ads_conversion_label' => ['nullable', 'string', 'max:100'],

            'head_scripts' => ['nullable', 'string'],
            'body_scripts' => ['nullable', 'string'],
            'robots_txt' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
