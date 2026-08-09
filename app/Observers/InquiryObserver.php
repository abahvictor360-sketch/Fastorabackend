<?php

namespace App\Observers;

use App\Mail\NewInquiryMail;
use App\Models\Inquiry;
use App\Support\MailSettings;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Notifies the admin by email whenever the contact or consultation form
 * creates an Inquiry — both post through the same ContactController, so one
 * observer covers every form on the site rather than each having to
 * remember to send its own notification.
 */
class InquiryObserver
{
    public function created(Inquiry $inquiry): void
    {
        // Still saved and visible in the admin — just not emailed, so an
        // obvious agency pitch (see ContactController::looksLikeSpam) doesn't
        // land in the inbox that only wants genuine enquiries.
        if ($inquiry->status === 'spam') {
            return;
        }

        $recipient = MailSettings::notificationRecipient();

        if (! $recipient) {
            return;
        }

        try {
            Mail::to($recipient)->send(new NewInquiryMail($inquiry));
        } catch (Throwable $e) {
            // The enquiry is already saved — a mail outage must never turn
            // into a failed form submission for the visitor.
            report($e);
        }
    }
}
