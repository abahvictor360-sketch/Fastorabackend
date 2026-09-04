<?php

namespace App\Filament\Resources\TeamMembers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                ImageColumn::make('photo.url')->label('Photo')->circular(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('role')->searchable(),
                TextColumn::make('slug')
                    ->label('Address')
                    ->formatStateUsing(fn (?string $state): string => '/'.$state)
                    ->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('order')->numeric()->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
