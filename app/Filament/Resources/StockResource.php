<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Models\Stock;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Stocks';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 10;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('fitem')->required()->maxLength(120),
            Forms\Components\TextInput::make('fdesc')->maxLength(500),
            Forms\Components\TextInput::make('fbranch')->required()->maxLength(10),
            Forms\Components\TextInput::make('fqty')->numeric()->default(0),
            Forms\Components\TextInput::make('fprice')->numeric()->default(0),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fitem')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('fdesc')->limit(60)->searchable(),
                Tables\Columns\TextColumn::make('fbranch')->sortable(),
                Tables\Columns\TextColumn::make('fqty')->numeric(4)->sortable(),
                Tables\Columns\TextColumn::make('fprice')->money('SAR', true)->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
        ];
    }
}
