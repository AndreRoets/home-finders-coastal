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
            // Only the bare meta-tag content code (letters, digits, - and _);
            // an HTML-file token belongs in /public, not in the meta tag.
            'google_search_console_verification' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'google_ads_conversion_id' => ['nullable', 'string', 'max:50'],
            'google_ads_conversion_label' => ['nullable', 'string', 'max:100'],

            'head_scripts' => ['nullable', 'string'],
            'body_scripts' => ['nullable', 'string'],
            'robots_txt' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'google_search_console_verification.regex' => 'Enter only the content code from the google-site-verification meta tag (e.g. "AbC123_xyz"), not an HTML file name or the whole tag.',
        ];
    }
}
