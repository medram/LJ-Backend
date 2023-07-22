<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Page;


class PagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                "title"     => "Privacy Policy",
                "slug"      => "privacy-policy",
                "content"   => "",
                "status"    => "1"
            ],
            [
                "title"     => "Terms of Use",
                "slug"      => "terms",
                "content"   => "",
                "status"    => "1"
            ],
            [
                "title"     => "FAQ",
                "slug"      => "faq",
                "content"   => "",
                "status"    => "1"
            ],
        ];

        Page::insert($data);
    }
}
