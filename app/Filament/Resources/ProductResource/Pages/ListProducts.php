<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;
use App\Models\Product;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        if (Auth::user()->role === 'admin') {
            return [
                Actions\CreateAction::make(),
            ];
        }
        $subscription = Subscription::where('user_id', Auth::user()->id)
            ->where('is_active', true)
            ->where('end_date', '>', now())
            ->latest()
            ->first();
        $countProduct = Product::where('user_id', Auth::user()->id)->count();
        return [
            Actions\Action::make('alert')
                ->label('Produk kamu melebihi batas pengguna Gratis, silahkan berlanganan')
                ->color('danger')
                ->icon('heroicon-o-exclamation-circle')
                ->visible(!$subscription && $countProduct >= 5),
            Actions\CreateAction::make(),              
        ];        
    }
}
