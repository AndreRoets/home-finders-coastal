<?php

namespace App\Http\Requests\Admin;

use App\Support\PageRoutes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalise the slug before validation: trim, strip surrounding slashes
     * (except the homepage "/") and lowercase it.
     */
    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));
        $slug = $slug === '/' ? '/' : strtolower(trim($slug, '/'));

        $this->merge(['slug' => $slug]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pageId = $this->route('page')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                'regex:/^(\/|[a-z0-9]+(?:[a-z0-9\-\/]*[a-z0-9])?)$/',
                Rule::unique('pages', 'slug')->ignore($pageId),
                Rule::notIn(PageRoutes::reservedSlugs()),
            ],
            'is_active' => ['boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots_index' => ['boolean'],
            'robots_follow' => ['boolean'],

            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'url', 'max:255'],
            'og_type' => ['nullable', 'string', 'max:50'],
            'twitter_card' => ['nullable', 'string', 'max:50'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
            'twitter_image' => ['nullable', 'url', 'max:255'],

            'json_ld' => ['nullable', 'string', 'json'],
            'head_scripts' => ['nullable', 'string'],

            'sitemap_priority' => ['required', 'string', Rule::in(['0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0'])],
            'sitemap_frequency' => ['required', 'string', Rule::in(['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, hyphens and slashes.',
            'slug.not_in' => 'That slug is reserved by the application and cannot be used.',
            'json_ld.json' => 'The structured data must be valid JSON.',
        ];
    }
}
