<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\Enquiries\ChannelClassifier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    /**
     * Site-wide Google marketing / analytics configuration.
     */
    public function edit(): Response
    {
        return Inertia::render('admin/marketing', [
            'settings' => SiteSetting::current(),
            // What the enquiry channel rules use while a list is left blank.
            'attributionDefaults' => [
                'attribution_window_days' => SiteSetting::DEFAULT_ATTRIBUTION_WINDOW_DAYS,
                'channel_search_sources' => implode("\n", ChannelClassifier::DEFAULT_SEARCH_SOURCES),
                'channel_social_sources' => implode("\n", ChannelClassifier::DEFAULT_SOCIAL_SOURCES),
                'channel_paid_mediums' => implode("\n", ChannelClassifier::DEFAULT_PAID_MEDIUMS),
                'channel_email_mediums' => implode("\n", ChannelClassifier::DEFAULT_EMAIL_MEDIUMS),
                'channel_social_mediums' => implode("\n", ChannelClassifier::DEFAULT_SOCIAL_MEDIUMS),
            ],
        ]);
    }

    public function update(UpdateSiteSettingRequest $request): RedirectResponse
    {
        SiteSetting::current()->update($request->validated());

        return back()->with('success', 'Marketing settings updated.');
    }
}
