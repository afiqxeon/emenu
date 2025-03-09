<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Product;
use Filament\Forms\Components\Get;
use Filament\Forms\Components\Set;


class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Manajemen Transaksi';
    protected static ?string $label = 'Transaksi';

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
                Forms\Components\TextInput::make('code')
                    ->label('Kode Transaksi')                    
                    ->default(fn() => 'TRX/' . date('Ymd') . '/' . strtoupper(Str::random(5)))
                    ->readonly()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Customer')
                    ->required(),
                Forms\Components\TextInput::make('phone_number')
                    ->label('Nomor HP Customer')
                    ->required(),
                Forms\Components\TextInput::make('table_number')
                    ->label('Nomor Meja')
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'midtrans' => 'Midtrans',                        
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Berhasil',
                        'failed' => 'Gagal',                        
                    ])                    
                    ->required(),
                Forms\Components\Repeater::make('transactionDetails')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('product_id')                            
                            ->relationship('product', 'name')
                            ->options(function (callable $get) {
                                if (Auth::user()->role === 'admin') {
                                    return Product::all()->mapWithKeys(function ($product) {
                                        return [$product->id => "$product->name (Rp " . number_format($product->price, 0, ',', '.') . ")"];
                                    });
                                }
                                return Product::where('user_id', Auth::user()->id)->get()->mapWithKeys(function ($product) {
                                    return [$product->id => "$product->name (Rp " . number_format($product->price, 0, ',', '.') . ")"];
                                });
                            })
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Forms\Components\TextInput::make('note')
                            ->label('Catatan')
                            ->nullable(),
                    ])->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $get, callable $set){
                    self::updateTotals($get, $set);
                    })
                    ->reorderable(false),
                Forms\Components\TextInput::make('total_price')
                    ->label('Total Harga')                    
                    ->readonly()
                    ->required(),                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Merchant')
                    ->hidden(fn() => Auth::user()->role === 'store')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Transaksi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('No Hp Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('table_number')
                    ->label('Nomor Meja')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Pembayaran')
                    ->formatStateUsing(function (string $state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Pembayaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Transaksi')
                    ->dateTime()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Merchant')
                    ->hidden(fn() => Auth::user()->role === 'store'),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
    public static function updateTotals(callable $get, callable $set): void
    {
        $selectedProducts = collect($get('transactionDetails'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));
        $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');
        $total = $selectedProducts->reduce(function ($total, $product) use ($prices) {
            return $total + ($prices[$product['product_id']] * $product['quantity']);
        }, 0);
        $set('total_price', (string) $total);
    }
}
