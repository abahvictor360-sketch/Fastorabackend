<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Phrases from the outbound-agency-pitch template ("we can assist you
     * with...", "check out our portfolio") rather than anything a business
     * would write about its own project — the direction is what tells the
     * two apart, not general words like "website" or "SEO" that a genuine
     * enquiry uses just as often.
     */
    private const SPAM_PHRASES = [
        'we can assist you',
        'we can help you with your',
        'we specialize in',
        'we offer the following services',
        'we provide the following services',
        'interested in our proposal',
        'kindly get back to us',
        'follow up on my previous email',
        'following up on my previous email',
        'quick response either way',
        'check out our portfolio',
        'increase your website traffic',
        'boost your online presence',
        'grow your business with our',
        'affordable web design',
    ];

    /** True if the brief reads like an unsolicited agency pitch rather than an enquiry. */
    private function looksLikeSpam(string $brief): bool
    {
        $haystack = strtolower($brief);

        foreach (self::SPAM_PHRASES as $phrase) {
            if (str_contains($haystack, $phrase)) {
                return true;
            }
        }

        return false;
    }

    public function store(Request $request)
    {
        // Honeypot — real users never fill this hidden field.
        if (filled($request->input('website'))) {
            return response()->json(['success' => true]);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'websiteUrl' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'serviceNeeded' => ['nullable', 'integer', 'exists:services,id'],
            // Free text: a fixed set of bands forced people into the wrong one, and
            // 'not sure' told us nothing. Whatever they type is more useful.
            'budgetRange' => ['nullable', 'string', 'max:255'],
            'timeline' => ['nullable', 'in:asap,1-month,1-3-months,exploring'],
            'brief' => ['required', 'string'],
            // Consultation requests post to this same endpoint, so they land in
            // one inbox with one notification path and the same status workflow.
            'kind' => ['nullable', 'in:general,consultation'],
            'preferredTimes' => ['nullable', 'string', 'max:2000'],
            'timezone' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        Inquiry::create([
            'status' => $this->looksLikeSpam($data['brief']) ? 'spam' : 'new',
            'kind' => $data['kind'] ?? 'general',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'website_url' => $data['websiteUrl'] ?? null,
            'company' => $data['company'] ?? null,
            'service_needed_id' => $data['serviceNeeded'] ?? null,
            'budget_range' => $data['budgetRange'] ?? null,
            'timeline' => $data['timeline'] ?? null,
            'brief' => $data['brief'],
            'preferred_times' => $data['preferredTimes'] ?? null,
            'timezone' => $data['timezone'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }
}
