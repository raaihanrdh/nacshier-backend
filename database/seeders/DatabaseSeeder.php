<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ➜ USERS
        $users = [
            ['name'=>'Muhammad Raihan Ridho','username'=>'admin','password'=>Hash::make('raihan123'),'level'=>'admin'],
            ['name'=>'Cashier User','username'=>'cashier','password'=>Hash::make('cashierpassword'),'level'=>'kasir']
        ];
        foreach ($users as $idx => $u) {
            $id = 'US'.str_pad($idx+1,6,'0',STR_PAD_LEFT);
            DB::table('users')->insert(array_merge($u, [
                'user_id'=>$id,
                'remember_token'=>Str::random(10),
                'created_at'=>now(),'updated_at'=>now()
            ]));
        }

        // ➜ CATEGORIES
        $cats = [
            ['name'=>'Makanan','description'=>'Makanan'],
            ['name'=>'Minuman','description'=>'Minuman']
        ];
        foreach ($cats as $idx => $c) {
            $id = 'CT'.str_pad($idx+1,6,'0',STR_PAD_LEFT);
            DB::table('categories')->insert(array_merge($c, [
                'category_id'=>$id,
                'created_at'=>now(),'updated_at'=>now()
            ]));
        }

        // ambil kategori
        $makanan = DB::table('categories')->where('name','Makanan')->value('category_id');
        $minuman = DB::table('categories')->where('name','Minuman')->value('category_id');

        // ➜ PRODUCTS
        $prods = [
            ['name'=>'Kue Coklat','description'=>'Kue coklat lezat, cocok untuk hadiah','selling_price'=>25000,'capital_price'=>15000,'category_id'=>$makanan,'stock'=>100],
            ['name'=>'Es Teh','description'=>'Minuman teh manis yang segar','selling_price'=>10000,'capital_price'=>5000,'category_id'=>$minuman,'stock'=>200]
        ];
        foreach ($prods as $idx => $p) {
            $id = 'PR'.str_pad($idx+1,6,'0',STR_PAD_LEFT);
            DB::table('products')->insert(array_merge($p, [
                'product_id'=>$id,
                'created_at'=>now(),'updated_at'=>now()
            ]));
        }

        // ➜ CASHIER SHIFTS
        $shifts = [
            ['user'=>'US000001','start'=>now()->subDays(1),'end'=>now()->subDays(1)->addHours(8)],
            ['user'=>'US000002','start'=>now(),'end'=>now()->addHours(8)]
        ];
        foreach ($shifts as $idx => $s) {
            $id = 'SF'.str_pad($idx+1,6,'0',STR_PAD_LEFT);
            DB::table('cashier_shifts')->insert([
                'shift_id'=>$id,
                'user_id'=>$s['user'],
                'start_time'=>$s['start'],
                'end_time'=>$s['end'],
                'created_at'=>now(),'updated_at'=>now()
            ]);
        }

        // ➜ TRANSACTIONS
        $trans = [
            ['shift'=>'SF000001','amount'=>45000,'time'=>now()->subDays(1),'method'=>'Cash'],
            ['shift'=>'SF000002','amount'=>60000,'time'=>now(),'method'=>'QRIS'],
            ['shift'=>'SF000001','amount'=>75000,'time'=>now()->subDays(3),'method'=>'Cash'],
            ['shift'=>'SF000002','amount'=>40000,'time'=>now()->subDays(7),'method'=>'Cash'],
            ['shift'=>'SF000001','amount'=>25000,'time'=>now()->subDays(14),'method'=>'QRIS'],
            ['shift'=>'SF000002','amount'=>120000,'time'=>now()->subDays(30),'method'=>'Cash'],
            ['shift'=>'SF000001','amount'=>50000,'time'=>now()->subDays(60),'method'=>'QRIS']
        ];
        foreach ($trans as $idx => $t) {
            $id = 'TR'.str_pad($idx+1,6,'0',STR_PAD_LEFT);
            DB::table('transactions')->insert([
                'transaction_id'=>$id,
                'shift_id'=>$t['shift'],
                'total_amount'=>$t['amount'],
                'transaction_time'=>$t['time'],
                'payment_method'=>$t['method'],
                'created_at'=>now(),'updated_at'=>now()
            ]);
        }

        // ambil produk dan transaksi untuk items
        $productMap = DB::table('products')->pluck('product_id','name')->toArray();
        $transMap = DB::table('transactions')->pluck('transaction_id')->toArray();

        // ➜ TRANSACTION ITEMS (contoh mapping acak)
        DB::table('transaction_items')->insert([
            ['item_id'=>'TI000001','transaction_id'=>$transMap[0],'product_id'=>$productMap['Kue Coklat'],'quantity'=>2,'selling_price'=>25000,'created_at'=>now(),'updated_at'=>now()],
            ['item_id'=>'TI000002','transaction_id'=>$transMap[0],'product_id'=>$productMap['Es Teh'],'quantity'=>1,'selling_price'=>10000,'created_at'=>now(),'updated_at'=>now()],
            ['item_id'=>'TI000003','transaction_id'=>$transMap[2],'product_id'=>$productMap['Kue Coklat'],'quantity'=>3,'selling_price'=>25000,'created_at'=>now()->subDays(3),'updated_at'=>now()->subDays(3)],
            ['item_id'=>'TI000004','transaction_id'=>$transMap[3],'product_id'=>$productMap['Es Teh'],'quantity'=>4,'selling_price'=>10000,'created_at'=>now()->subDays(7),'updated_at'=>now()->subDays(7)],
            ['item_id'=>'TI000005','transaction_id'=>$transMap[4],'product_id'=>$productMap['Kue Coklat'],'quantity'=>1,'selling_price'=>25000,'created_at'=>now()->subDays(14),'updated_at'=>now()->subDays(14)],
            ['item_id'=>'TI000006','transaction_id'=>$transMap[5],'product_id'=>$productMap['Es Teh'],'quantity'=>12,'selling_price'=>10000,'created_at'=>now()->subDays(30),'updated_at'=>now()->subDays(30)],
            ['item_id'=>'TI000007','transaction_id'=>$transMap[6],'product_id'=>$productMap['Kue Coklat'],'quantity'=>2,'selling_price'=>25000,'created_at'=>now()->subDays(60),'updated_at'=>now()->subDays(60)],
        ]);

        // ➜ CASHFLOWS
        DB::table('cashflows')->insert([
            ['cashflow_id'=>'CF000001','transaction_id'=>$transMap[0],'date'=>now()->subDays(1)->toDateString(),'description'=>'Pendapatan penjualan harian (Cash)','amount'=>45000,'type'=>'income','category'=>'sales','method'=>'Cash','created_at'=>now(),'updated_at'=>now()],
            ['cashflow_id'=>'CF000002','transaction_id'=>$transMap[1],'date'=>now()->toDateString(),'description'=>'Pendapatan penjualan harian (QRIS)','amount'=>60000,'type'=>'income','category'=>'sales','method'=>'QRIS','created_at'=>now(),'updated_at'=>now()],
            ['cashflow_id'=>'CF000003','transaction_id'=>null,'date'=>now()->subDays(2)->toDateString(),'description'=>'Pembayaran supplier bahan makanan','amount'=>500000,'type'=>'expense','category'=>'inventory','method'=>'Cash','created_at'=>now(),'updated_at'=>now()],
            ['cashflow_id'=>'CF000004','transaction_id'=>null,'date'=>now()->subDays(1)->toDateString(),'description'=>'Biaya listrik bulanan','amount'=>250000,'type'=>'expense','category'=>'operational','method'=>'Transfer Bank','created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
