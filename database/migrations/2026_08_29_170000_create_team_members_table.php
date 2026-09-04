<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * People with a profile page of their own, at fastora.africa/<slug>.
 *
 * Separate from the About page's team block, which stays as it is: that block
 * is the grid on one page, this is the person. The frontend links the two by
 * name, so adding a row here turns a card in that grid into a link without the
 * block needing to be touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // The URL, and short by design: these get printed on business cards
            // and pasted into email signatures, so "kator" rather than
            // "kator-tarkaa" is the point of the field being editable at all.
            $table->string('slug')->unique();
            $table->string('role')->nullable();
            $table->text('bio')->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('email')->nullable();
            // [{platform: 'linkedin', url: 'https://…'}, …], in display order.
            $table->json('socials')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('status')->default('published');

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_canonical_url')->nullable();
            $table->boolean('meta_noindex')->default(false);
            $table->foreignId('meta_image_media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
