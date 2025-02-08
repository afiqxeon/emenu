<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductCategory;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Produk';
    protected static ?string $label = 'Produk';
    protected static ?string $navigationGroup = 'Manajemen Produk';

     public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return parent::getEloquentQuery();
        }
        return parent::getEloquentQuery()->where('user_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Merchant')
                    ->relationship('user', 'name')
                    ->required()
                    ->reactive()
                    ->hidden(fn() => Auth::user()->role === 'store'),
                Forms\Components\Select::make('product_category_id')
                    ->label('Kategori Produk')
                    ->relationship('productCategory', 'name')
                    ->disabled(fn(callable $get) => $get('user_id') === null)
                    ->options(function (callable $get) {
                        $userId = $get('user_id');
                        if (!$userId) {
                            return [];
                        }
                        return ProductCategory::where('user_id', $userId)
                        ->pluck('name', 'id');
                    })
                    ->hidden(fn() => Auth::user()->role === 'store')
                    ->required(),
                Forms\Components\Select::make('product_category_id')
                    ->label('Kategori Produk')
                    ->relationship('productCategory', 'name')                    
                    ->options(function (callable $get) {
                        return ProductCategory::where('user_id', Auth::user()->id)
                        ->pluck('name', 'id');
                    })
                    ->hidden(fn() => Auth::user()->role === 'admin')
                    ->required(),
                    Forms\Components\FileUpload::make('image')
                    ->label('Gambar Produk')
                    ->image()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Produk')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi Produk')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Harga Produk')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('rating')
                    ->label('Rating Produk')
                    ->numeric()
                    ->required(),
                Forms\Components\Toggle::make('is_popular')
                    ->label('Produk Populer')
                    ->required(),
                Forms\Components\Repeater::make('productIngredients')
                    ->label('Bahan Produk')
                    ->relationship('productIngredients')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Bahan')
                            ->required(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Merchant')
                    ->hidden(fn() => Auth::user()->role === 'store'),
                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Kategori Produk'),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar Produk'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga Produk')
                    ->formatStateUsing(function (string $state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    }),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating Produk'),
                Tables\Columns\ToggleColumn::make('is_popular')
                    ->label('Produk Populer'),
                    
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Merchant')
                    ->hidden(fn() => Auth::user()->role === 'store'),
                Tables\Filters\SelectFilter::make('product_category_id')
                    ->options(function () {
                        if (Auth::user()->role === 'admin') {
                            return ProductCategory::pluck('name', 'id');                                                        
                        }
                        return ProductCategory::where('user_id', Auth::user()->id)
                            ->pluck('name', 'id');
                    })
                    ->label('Kategori Produk'),
            ])
            ->actions([
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
