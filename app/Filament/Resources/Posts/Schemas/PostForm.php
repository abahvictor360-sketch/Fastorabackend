<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Concerns\HasMediaSelect;
use App\Filament\Concerns\HasSeoFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PostForm
{
    use HasMediaSelect;
    use HasSeoFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Post')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Content')
                        ->schema([
                            TextInput::make('title')->required()->columnSpanFull(),
                            TextInput::make('slug')->required()->columnSpanFull(),
                            static::mediaSelect('heroImage', 'Hero image')->columnSpanFull(),
                            RichEditor::make('content')->required()->columnSpanFull(),
                        ]),

                    Tab::make('Meta')
                        ->schema([
                            Select::make('categories')
                                ->relationship('categories', 'title')
                                ->multiple()
                                ->searchable()
                                ->preload(),
                            Select::make('authors')
                                ->relationship('authors', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload(),
                            Repeater::make('tags')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('tag')->required(),
                                ])
                                ->addActionLabel('Add tag')
                                ->defaultItems(0)
                                ->helperText('Free-form keywords shown on the post and used for grouping.'),
                        ])
                        ->columns(2),

                    Tab::make('Publishing & SEO')
                        ->schema([
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'scheduled' => 'Scheduled',
                                    'published' => 'Published',
                                ])
                                ->default('draft')
                                ->required()
                                ->live(),
                            DateTimePicker::make('published_at')
                                ->helperText('For scheduled posts, this is when it goes live automatically.'),
                            Toggle::make('featured')
                                ->label('Pin to top of Insights')
                                ->helperText('Only one pinned post is shown. If more than one is toggled on, the most recently published wins.')
                                ->columnSpanFull(),
                            Toggle::make('show_journal_note')
                                ->label('Show the Journal note')
                                ->default(true)
                                ->helperText("Prints \"This article is part of Fastora's Journal…\" under the article, with links to the Journal and to Contact. On for Journal pieces, which is most of them; turn it off for anything that is not part of the Journal.")
                                ->columnSpanFull(),
                            ...static::seoFields(),
                        ])
                        ->columns(2),
                ]),
        ]);
    }
}
