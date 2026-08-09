<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class InquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options(['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed', 'spam' => 'Spam'])
                    ->default('new')
                    ->required(),
                Select::make('kind')
                    ->label('Type')
                    ->options(['general' => 'General enquiry', 'consultation' => 'Consultation request'])
                    ->default('general')
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('company')
                    ->default(null),
                Select::make('service_needed_id')
                    ->relationship('serviceNeeded', 'title')
                    ->default(null),
                // Free text rather than fixed bands: the bands forced people into
                // the wrong one, and "not sure" told us nothing.
                TextInput::make('budget_range')
                    ->label('Budget')
                    ->helperText('Whatever the enquirer typed, in their own words.'),
                Select::make('timeline')
                    ->options([
                        'asap' => 'ASAP',
                        '1-month' => 'Within 1 month',
                        '1-3-months' => '1–3 months',
                        'exploring' => 'Just exploring',
                    ]),
                Textarea::make('brief')
                    ->required()
                    ->columnSpanFull(),
                // Only meaningful on a consultation request, so hidden on a
                // general enquiry rather than shown permanently empty.
                Textarea::make('preferred_times')
                    ->label('Times they can make')
                    ->rows(3)
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('kind') === 'consultation'),
                TextInput::make('timezone')
                    ->label('Their timezone')
                    ->visible(fn ($get) => $get('kind') === 'consultation'),
            ]);
    }
}
