<?php

namespace App\Providers;

use App\Models\CaseStudy;
use App\Models\Inquiry;
use App\Models\NavFooter;
use App\Models\NavHeader;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Notifications\FastoraResetPassword;
use App\Observers\CaseStudyObserver;
use App\Observers\InquiryObserver;
use App\Observers\PageObserver;
use App\Observers\PostObserver;
use App\Observers\ServiceObserver;
use App\Observers\SiteWideSettingsObserver;
use App\Observers\TeamMemberObserver;
use App\Support\MailSettings;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Filament resolves this class from the container when sending a
        // password-reset email — binding our branded subclass here swaps the
        // email's content without touching Filament's request/reset flow.
        $this->app->bind(ResetPassword::class, FastoraResetPassword::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Notify the Next.js frontend to revalidate its cached pages whenever
        // content changes here — see app/Support/RevalidatesFrontend.php.
        Service::observe(ServiceObserver::class);
        CaseStudy::observe(CaseStudyObserver::class);
        Post::observe(PostObserver::class);
        Page::observe(PageObserver::class);
        SiteSetting::observe(SiteWideSettingsObserver::class);
        NavHeader::observe(SiteWideSettingsObserver::class);
        NavFooter::observe(SiteWideSettingsObserver::class);
        Inquiry::observe(InquiryObserver::class);
        TeamMember::observe(TeamMemberObserver::class);

        MailSettings::apply();

        $this->configureRichEditors();
    }

    /**
     * Lets every rich-text field take images in among the text.
     *
     * Configured here rather than on each field: there are nine RichEditors
     * across five resources, and an editor that accepts images on the Insights
     * tab but not on a page block would be a trap rather than a feature.
     *
     * Attachments land on the public disk, whose URL is built from APP_URL, so
     * the HTML carries absolute links to the API host. That matters because the
     * frontend renders this HTML on its own domain — a relative /storage path
     * would resolve against fastora.africa and 404.
     *
     * The frontend passes rich text straight to the DOM rather than through
     * next/image, so these images need no entry in the image-optimizer allowlist.
     */
    protected function configureRichEditors(): void
    {
        RichEditor::configureUsing(function (RichEditor $editor): void {
            $editor
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory('content')
                ->fileAttachmentsVisibility('public');
        });
    }
}
