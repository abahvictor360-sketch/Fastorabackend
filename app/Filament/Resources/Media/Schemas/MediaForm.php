<?php

namespace App\Filament\Resources\Media\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('File')
                    ->disk('public')
                    ->directory('media')
                    ->image()
                    ->requiredWithout('url')
                    ->columnSpanFull(),
                TextInput::make('url')
                    ->label('...or paste an image URL')
                    ->url()
                    ->requiredWithout('path')
                    ->helperText("We'll download and store a copy, so the image keeps working even if the original goes away.")
                    ->columnSpanFull(),
                // Required: an image saved without it renders alt="" on the
                // live site, which is invisible in the admin but shows up as an
                // accessibility failure in a search engine's site scan.
                TextInput::make('alt')
                    ->label('Alt text')
                    ->required()
                    ->helperText('Describe the image for accessibility and SEO.')
                    ->columnSpanFull(),
                Hidden::make('disk')->default('public'),
            ]);
    }
}
