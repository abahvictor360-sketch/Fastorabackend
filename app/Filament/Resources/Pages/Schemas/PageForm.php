<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Concerns\HasMediaSelect;
use App\Filament\Concerns\HasSeoFields;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * The flexible page-builder — the direct equivalent of Payload's `blocks`
 * field. Each Builder block below mirrors one of the Next.js block
 * components (src/blocks/*), so content authored here renders through the
 * matching component once the frontend is wired to this API.
 */
class PageForm
{
    use HasMediaSelect;
    use HasSeoFields;

    protected static function linksRepeater(string $name = 'links'): Repeater
    {
        return Repeater::make($name)
            ->schema([
                TextInput::make('label')->required(),
                TextInput::make('url')->required(),
                Select::make('appearance')
                    ->options(['default' => 'Default', 'outline' => 'Outline'])
                    ->default('default'),
            ])
            ->columns(3)
            ->addActionLabel('Add link')
            ->defaultItems(0)
            ->columnSpanFull();
    }

    protected static function eyebrowHeading(): array
    {
        return [
            TextInput::make('eyebrow'),
            TextInput::make('heading')->required(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Page')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Hero')
                        ->schema([
                            TextInput::make('title')->required(),
                            TextInput::make('slug')->required(),
                            Select::make('hero_type')
                                ->label('Hero type')
                                ->options([
                                    'none' => 'None',
                                    'highImpact' => 'High impact',
                                    'mediumImpact' => 'Medium impact',
                                    'lowImpact' => 'Low impact',
                                ])
                                ->default('lowImpact')
                                ->required()
                                ->live(),
                            static::mediaSelect('heroMedia', 'Hero media')
                                ->visible(fn ($get) => in_array($get('hero_type'), ['highImpact', 'mediumImpact'])),
                            TextInput::make('hero_eyebrow')
                                ->label('Hero eyebrow')
                                ->helperText('Small line above the hero headline, e.g. "Communications & Digital Strategy".'),
                            RichEditor::make('hero_rich_text')->label('Hero text')->columnSpanFull(),
                            static::linksRepeater('hero_links'),
                        ])
                        ->columns(2),

                    Tab::make('Page Header')
                        ->schema([
                            TextInput::make('page_header_eyebrow')->label('Eyebrow'),
                            TextInput::make('page_header_heading')->label('Heading'),
                            Textarea::make('page_header_description')
                                ->label('Description')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    Tab::make('FAQs')
                        ->schema([
                            Repeater::make('faqs')
                                ->label('Frequently asked questions')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('question')->required(),
                                    Textarea::make('answer')->required()->rows(3),
                                ])
                                ->addActionLabel('Add question')
                                ->reorderable()
                                ->collapsed()
                                ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                ->defaultItems(0)
                                ->helperText('Shown at the bottom of this page, and published as FAQ structured data so search engines and AI assistants can quote the answers.'),
                        ])
                        ->columnSpanFull(),

                    Tab::make('Content')
                        ->schema([
                            Builder::make('layout')
                                ->columnSpanFull()
                                ->addActionLabel('Add block')
                                ->blocks([
                                    Block::make('cta')
                                        ->label('Call to Action')
                                        ->schema([
                                            RichEditor::make('richText')->label('Text')->columnSpanFull(),
                                            static::linksRepeater(),
                                        ]),

                                    Block::make('content')
                                        ->label('Content')
                                        ->schema([
                                            RichEditor::make('richText')->label('Text')->columnSpanFull(),
                                            static::mediaPicker('image', 'Image')
                                                ->helperText('Optional. Adds a two-column layout with the text beside it.'),
                                            Select::make('imagePosition')
                                                ->label('Image position')
                                                ->options(['right' => 'Right', 'left' => 'Left'])
                                                ->default('right')
                                                ->visible(fn ($get) => filled($get('image'))),
                                        ])
                                        ->columns(2),

                                    Block::make('visionMission')
                                        ->label('Vision & Mission')
                                        ->schema([
                                            TextInput::make('visionHeading')
                                                ->label('Vision heading')
                                                ->default('Our vision')
                                                ->required(),
                                            RichEditor::make('visionBody')
                                                ->label('Vision text')
                                                ->columnSpanFull(),
                                            TextInput::make('missionHeading')
                                                ->label('Mission heading')
                                                ->default('Our mission')
                                                ->required(),
                                            RichEditor::make('missionBody')
                                                ->label('Mission text')
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2),

                                    Block::make('mediaBlock')
                                        ->label('Media')
                                        ->schema([
                                            static::mediaPicker('media', 'Image'),
                                            TextInput::make('caption'),
                                        ]),

                                    Block::make('aboutFastora')
                                        ->label('About Fastora')
                                        ->schema([
                                            TextInput::make('heading')->required(),
                                            RichEditor::make('richText')
                                                ->label('Body')
                                                ->columnSpanFull(),
                                            static::mediaPicker('image', 'Image')
                                                ->helperText('A team, workshop or workspace photo works best here.'),
                                            TextInput::make('linkLabel')
                                                ->label('Link label')
                                                ->default('More about Fastora'),
                                            TextInput::make('linkUrl')
                                                ->label('Link URL')
                                                ->default('/about'),
                                            Repeater::make('stats')
                                                ->label('Supporting figures')
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextInput::make('value')->required()->helperText('e.g. "10"'),
                                                    TextInput::make('label')->required()->helperText('e.g. "Services"'),
                                                ])
                                                ->columns(2)
                                                ->addActionLabel('Add figure')
                                                ->defaultItems(0)
                                                ->maxItems(3),
                                        ])
                                        ->columns(2),

                                    Block::make('trustedBy')
                                        ->label('Trusted By (clients)')
                                        ->schema([
                                            TextInput::make('heading')
                                                ->default('Trusted by')
                                                ->helperText('Kept short. The client names carry the credibility, not the wording.'),
                                            Repeater::make('logos')
                                                ->label('Clients')
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->helperText('Shown as text until a logo is uploaded.'),
                                                    TextInput::make('industry')
                                                        ->helperText('Shown under the name, e.g. "Oil & Gas". Hidden once a logo is set.'),
                                                    static::mediaPicker('media', 'Logo')
                                                        ->helperText('Optional. Replaces the name once uploaded.'),
                                                ])
                                                ->columns(2)
                                                ->addActionLabel('Add client')
                                                ->reorderable()
                                                ->defaultItems(0)
                                                ->helperText('The section stays hidden while this list is empty, so it is never shown bare. A client with no logo shows as its name, so a confirmed list can go live before the logo files arrive.'),
                                        ]),

                                    Block::make('team')
                                        ->label('Meet the Team')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Textarea::make('description')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->helperText('Optional sentence below the heading.'),
                                            Repeater::make('members')
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextInput::make('name')->required(),
                                                    TextInput::make('role'),
                                                    Textarea::make('bio')->rows(3),
                                                    static::mediaPicker('photo', 'Photo')
                                                        ->helperText('Optional. Shows as initials until a photo is uploaded.'),
                                                    TextInput::make('profilePath')
                                                        ->label('Profile link')
                                                        ->placeholder('/genesis')
                                                        ->helperText('Optional. Leave empty and the card links to the Team profile with the same name, if there is one. Set it when the two differ — Emmanuel is introduced here by his full name but his page is /genesis.'),
                                                ])
                                                ->columns(2)
                                                ->addActionLabel('Add team member')
                                                ->reorderable()
                                                ->collapsed()
                                                ->collapsible()
                                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                                ->defaultItems(0),
                                        ]),

                                    Block::make('servicesOverview')
                                        ->label('Services Overview')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Textarea::make('description')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->helperText('Optional sentence below the heading.'),
                                            TextInput::make('limit')->numeric()->default(6),
                                        ]),

                                    Block::make('whyFastora')
                                        ->label('Why Fastora')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Textarea::make('description')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->helperText('Optional sentence below the heading.'),
                                            Repeater::make('points')
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextInput::make('stat')->required()->helperText('e.g. "89%"'),
                                                    TextInput::make('title')
                                                        ->helperText('Optional. Left blank on the "Be Understood" / "Think First" style cards.'),
                                                    TextInput::make('description')->required(),
                                                ])
                                                ->columns(3)
                                                ->addActionLabel('Add point')
                                                ->defaultItems(0),
                                        ]),

                                    Block::make('selectedWork')
                                        ->label('Selected Work / Case Studies')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Textarea::make('description')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->helperText('Optional sentence below the heading.'),
                                            TextInput::make('limit')->numeric()->default(3),
                                        ]),

                                    Block::make('testimonialsBlock')
                                        ->label('Testimonials')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            TextInput::make('limit')->numeric()->default(3),
                                        ]),

                                    Block::make('faq')
                                        ->label('FAQ')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Repeater::make('items')
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextInput::make('question')->required(),
                                                    TextInput::make('answer')->required(),
                                                ])
                                                ->addActionLabel('Add question')
                                                ->defaultItems(0),
                                        ]),

                                    Block::make('consultationForm')
                                        ->label('Consultation Request Form')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Textarea::make('description')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->helperText('Sentence below the heading, e.g. what the session covers.'),
                                            Repeater::make('idealFor')
                                                ->label('Ideal for')
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextInput::make('label')->required(),
                                                ])
                                                ->addActionLabel('Add audience')
                                                ->defaultItems(0)
                                                ->helperText('Who the session suits. Shown as a checklist beside the form.'),
                                            TextInput::make('submitLabel')
                                                ->label('Button label')
                                                ->default('Request a session'),
                                            Textarea::make('reassurance')
                                                ->label('Line under the button')
                                                ->rows(2)
                                                ->default("We'll confirm one of your preferred times by email within one business day."),
                                        ]),

                                    Block::make('latestInsights')
                                        ->label('Latest Insights')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Textarea::make('description')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->helperText('Optional sentence below the heading.'),
                                            TextInput::make('limit')->numeric()->default(3),
                                        ]),

                                    Block::make('ourProcess')
                                        ->label('Our Process')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Textarea::make('intro')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->helperText('Optional sentence below the heading.'),
                                            Repeater::make('steps')
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextInput::make('title')->required(),
                                                    TextInput::make('description')->required(),
                                                ])
                                                ->addActionLabel('Add step')
                                                ->defaultItems(0),
                                        ]),

                                    Block::make('audienceGrid')
                                        ->label('Audience Grid')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Textarea::make('description')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->helperText('Optional sentence below the heading.'),
                                            // Each entry renders as a pill, so it is one short
                                            // label rather than a title and a paragraph.
                                            Repeater::make('items')
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextInput::make('label')
                                                        ->required()
                                                        ->helperText('Short, e.g. "Startups".'),
                                                ])
                                                ->addActionLabel('Add item')
                                                ->defaultItems(0),
                                        ]),

                                    Block::make('archive')
                                        ->label('Archive')
                                        ->schema([
                                            ...static::eyebrowHeading(),
                                            Select::make('relationTo')
                                                ->options(['posts' => 'Posts', 'case-studies' => 'Case Studies', 'services' => 'Services'])
                                                ->default('posts'),
                                            TextInput::make('limit')->numeric()->default(6),
                                        ]),
                                ]),
                        ]),

                    Tab::make('SEO')
                        ->schema([
                            Select::make('status')
                                ->options(['draft' => 'Draft', 'published' => 'Published'])
                                ->default('draft')
                                ->required(),
                            DateTimePicker::make('published_at'),
                            ...static::seoFields(),
                        ])
                        ->columns(2),
                ]),
        ]);
    }
}
