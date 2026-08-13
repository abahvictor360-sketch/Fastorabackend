<?php

namespace App\Filament\Concerns;

use App\Models\Media;
use App\Support\MediaDownloader;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Storage;

/**
 * Shared "pick an image" field, used by every resource that references one
 * (icon, cover, avatar, hero, logo, favicon, meta).
 *
 * Picks from the media library and can also take a file straight off the
 * editor's computer: the "+" beside the field opens an upload form, registers the
 * file in the library, and selects it. Before this, adding an image meant leaving
 * the record, uploading under Media, then coming back — easy to lose a draft to.
 */
trait HasMediaSelect
{
    /**
     * For a real Eloquent relationship on the record: heroMedia, coverImage,
     * icon, featuredImage, metaImage, heroImage.
     */
    protected static function mediaSelect(string $relationshipName, string $label): Select
    {
        return Select::make($relationshipName)
            ->relationship($relationshipName, 'filename')
            ->getOptionLabelFromRecordUsing(fn (Media $record) => $record->alt ?: $record->filename)
            ->searchable()
            ->preload()
            ->label($label)
            ->createOptionForm(static::uploadForm())
            ->createOptionUsing(fn (array $data): int => static::registerUploadedMedia($data));
    }

    /**
     * For an image stored as a bare id inside JSON — the page-layout blocks.
     *
     * These are not relationships, and using mediaSelect() for them threw
     * "The relationship [media] does not exist on the model [App\Models\Page]",
     * which 500'd the whole Pages edit screen: every block's image field tried to
     * hydrate from a relationship that cannot exist on a JSON column. So page
     * content could not be edited at all.
     *
     * Same picker and same upload flow, but the options are listed directly and
     * the chosen id is written straight into the JSON.
     */
    protected static function mediaPicker(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn (): array => Media::query()
                ->orderByDesc('id')
                ->get()
                ->mapWithKeys(fn (Media $media): array => [
                    $media->id => $media->alt ?: $media->filename,
                ])
                ->all())
            ->searchable()
            ->createOptionForm(static::uploadForm())
            ->createOptionUsing(fn (array $data): int => static::registerUploadedMedia($data));
    }

    /**
     * The "upload from your computer" form behind the picker's create button.
     *
     * @return array<int, \Filament\Forms\Components\Field>
     */
    protected static function uploadForm(): array
    {
        return [
            FileUpload::make('file')
                ->label('Upload an image')
                ->image()
                ->disk('public')
                ->directory('media')
                ->visibility('public')
                ->maxSize(8192)
                ->requiredWithout('url')
                ->helperText('JPG, PNG, WebP or SVG, up to 8 MB.'),
            TextInput::make('url')
                ->label('...or paste an image URL')
                ->url()
                ->requiredWithout('file')
                ->helperText("We'll download and store a copy, so the image keeps working even if the original goes away."),
            // Required for the same reason as the media resource's own form:
            // a blank one silently ships alt="" to the live site.
            TextInput::make('alt')
                ->label('Alt text')
                ->required()
                ->helperText('What the image shows, for screen readers and search engines.'),
        ];
    }

    /**
     * Turns an uploaded file — or a pasted URL, downloaded first — into a
     * media library row, and returns its id.
     *
     * FileUpload has already written a picked file to the public disk, so
     * that branch only records it. Dimensions are read from the stored file
     * rather than trusted from the request, and are left null for formats
     * getimagesize cannot read, such as SVG.
     */
    protected static function registerUploadedMedia(array $data): int
    {
        $path = filled($data['file'] ?? null) ? $data['file'] : MediaDownloader::downloadToPublicDisk($data['url']);

        $dimensions = @getimagesize(Storage::disk('public')->path($path));

        return Media::create([
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'mime_type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
            'size' => Storage::disk('public')->size($path),
            'alt' => $data['alt'] ?? null,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ])->id;
    }
}
