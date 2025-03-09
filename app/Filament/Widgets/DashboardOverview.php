<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Product;
use App\Models\SubscriptionPayment;



class DashboardOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTransaction = 0;
        $totalAmount = 0;

        $totalTransaction = Transaction::where('user_id', Auth::user()->id)
            ->where('status', 'success')
            ->count();
        $totalAmount = Transaction::where('user_id', Auth::user()->id)
            ->where('status', 'success')
            ->sum('total_price');

        if (Auth::user()->role == 'admin') {
            return [
                Stat::make('Total Pengguna', User::count())
                    ->description('Jumlah pengguna terdaftar')
                    ->icon('heroicon-o-users'),
                Stat::make('Total Pendapatan Langganan', 'Rp' .number_format(SubscriptionPayment::where('status', 'success')->count() * 50000))
                    ->description('Jumlah pendapatan dari langganan')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Total Produk', Product::count())
                    ->description('Jumlah produk terdaftar')
                    ->icon('heroicon-o-shopping-bag'),
            ];
        } else {
            return [
                Stat::make('Total Transaksi', $totalTransaction)
                    ->description('Jumlah transaksi berhasil')
                    ->icon('heroicon-o-shopping-cart'),
                Stat::make('Total Pendapatan', 'Rp' . number_format($totalAmount))
                    ->description('Jumlah pendapatan dari transaksi')
                    ->icon('heroicon-o-banknotes'),
                Stat::make('Rata-Rata Pendapatan', $totalTransaction > 0 ? 'Rp' . number_format($totalAmount / $totalTransaction) : 'Rp 0')
                    ->description('Rata-rata pendapatan per transaksi')
                    ->icon('heroicon-o-banknotes'),
            ];
        }
    }
}
