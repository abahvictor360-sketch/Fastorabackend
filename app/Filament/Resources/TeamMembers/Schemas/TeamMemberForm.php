<?php

namespace App\Filament\Resources\TeamMembers\Schemas;

use App\Filament\Concerns\HasMediaSelect;
use App\Filament\Concerns\HasSeoFields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class TeamMemberForm
{
    use HasMediaSelect;
    use HasSeoFields;

    /** The platforms the site has an icon and a label for. */
    public const PLATFORMS = [
        'linkedin' => 'LinkedIn',
        'twitter' => 'X (Twitter)',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'threads' => 'Threads',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'whatsapp' => 'WhatsApp',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Team member')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Profile')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->helperText('Shown on the page and matched against the About page team grid, which links to this profile when the names agree.'),
                            TextInput::make('slug')
                                ->required()
                                ->helperText('The address: fastora.africa/kator. Keep it short — these get printed on cards and pasted into signatures. Changing it breaks any link already handed out.'),
                            TextInput::make('role')
                                ->helperText('e.g. "Operations Associate". Shown under the name.'),
                            Textarea::make('bio')
                                ->rows(6)
                                ->columnSpanFull()
                                ->helperText('A short paragraph or two. This is the body of the page.'),
                            static::mediaSelect('photo', 'Photo')
                                ->helperText('A headshot. Without one the page shows their initials, the same as the About page grid.'),
                            TextInput::make('email')
                                ->email()
                                ->helperText('Optional. Adds an "Email" button to the page.'),
                            TextInput::make('order')
                                ->numeric()
                                ->default(0)
                                ->helperText('Lower numbers first, where members are listed together.'),
                        ])
                        ->columns(2),

                    Tab::make('Socials')
                        ->schema([
                            Repeater::make('socials')
                                ->label('Social links')
                                ->schema([
                                    Select::make('platform')
                                        ->options(static::PLATFORMS)
                                        ->required()
                                        ->helperText('Each gets its own icon and label.'),
                                    TextInput::make('url')
                                        ->label('Profile URL')
                                        ->url()
                                        ->required()
                                        ->placeholder('https://www.linkedin.com/in/…'),
                                ])
                                ->columns(2)
                                ->addActionLabel('Add a social link')
                                ->reorderable()
                                ->defaultItems(0)
                                ->columnSpanFull()
                                ->helperText('Shown as buttons on the profile page, in this order. A row missing either field is ignored rather than rendering a button that goes nowhere.'),
                        ]),

                    Tab::make('SEO')
                        ->schema([
                            Select::make('status')
                                ->options(['draft' => 'Draft', 'published' => 'Published'])
                                ->default('draft')
                                ->required()
                                ->helperText('A draft profile 404s until published.'),
                            ...static::seoFields(),
                        ])
                        ->columns(2),
                ]),
        ]);
    }
}
