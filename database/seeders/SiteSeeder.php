<?php

namespace Database\Seeders;

use App\Models\GlobalSettings;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    
    public function run()
    {
        $ins['site_name'] = 'GBS System & Service Private Limited';
        $ins['site_email'] = 'support@gbs.in';
        $ins['site_mobile_no'] = '+91 96003 76222, +91 98416 03332';
        $ins['address'] = '1070A, Munusamy Salai,KK Nagar, Chennai-600078';

        GlobalSettings::updateOrCreate(['id' => 1], $ins);
       
    }
}
