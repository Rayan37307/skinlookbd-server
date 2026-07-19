<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Label;
use App\Models\Product;
use App\Models\SkinType;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds a real, recognizable skincare/hair-care catalog (actual brands, products,
     * ingredients and Bangladesh-market pricing) instead of randomly generated text,
     * so the storefront and admin panel look like a real shop out of the box.
     */
    public function run(): void
    {
        $skinTypes = SkinType::all();
        $tags = Tag::all();
        $labels = Label::all();

        $sku = 1;

        foreach ($this->catalog() as $categorySlug => $products) {
            $category = Category::where('slug', $categorySlug)->firstOrFail();

            foreach ($products as $data) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']),
                    'sku' => 'SLB-'.str_pad((string) $sku++, 4, '0', STR_PAD_LEFT),
                    'brand' => $data['brand'],
                    'short_description' => $data['short_description'],
                    'description' => $data['description'],
                    'ingredients' => $data['ingredients'],
                    'additional_information' => array_values(array_filter([
                        isset($data['size']) ? ['label' => 'Size', 'value' => $data['size']] : null,
                        ['label' => 'Country of Origin', 'value' => $data['origin']],
                    ])),
                    'base_price' => $data['price'],
                    'sale_price' => $data['sale_price'] ?? null,
                    'status' => 'active',
                    'track_inventory' => true,
                    'stock_quantity' => $data['stock'] ?? 0,
                    'free_shipping' => $data['free_shipping'] ?? ($data['price'] >= 1000),
                    'meta_title' => $data['name'].' | Buy Online in Bangladesh - SkinLookBD',
                    'meta_description' => $data['short_description'],
                    'focus_keyword' => Str::lower($data['name']),
                ]);

                $product->images()->createMany([
                    ['type' => 'image', 'path' => 'products/'.$product->slug.'-1.jpg', 'alt' => $data['name'], 'sort_order' => 0],
                    ['type' => 'image', 'path' => 'products/'.$product->slug.'-2.jpg', 'alt' => $data['name'], 'sort_order' => 1],
                ]);

                // Every product needs at least one variant: the cart/checkout flow only
                // ever adds a ProductVariant, so a variant-less product couldn't be bought.
                // Single-size products get one variant with no price override, so it simply
                // follows the product's own (possibly on-sale) price.
                $variants = $data['variants'] ?? [[
                    'size' => $data['size'] ?? 'Standard',
                    'price' => null,
                    'stock' => $data['stock'] ?? 0,
                ]];

                foreach ($variants as $variant) {
                    $product->variants()->create([
                        'sku' => $product->sku.'-'.Str::upper(Str::slug($variant['size'], '')),
                        'size_label' => $variant['size'],
                        'price_override' => $variant['price'],
                        'stock_quantity' => $variant['stock'],
                    ]);
                }

                if (! empty($data['skin_types'])) {
                    $product->skinTypes()->attach(
                        $skinTypes->whereIn('name', $data['skin_types'])->pluck('id')
                    );
                }

                if (! empty($data['tags'])) {
                    $product->tags()->attach(
                        $tags->whereIn('name', $data['tags'])->pluck('id')
                    );
                }

                if (! empty($data['labels'])) {
                    $product->labels()->attach(
                        $labels->whereIn('name', $data['labels'])->pluck('id')
                    );
                }
            }
        }
    }

    /**
     * Real products grouped by leaf category slug (see CategorySeeder). Prices are in BDT
     * and approximate real Bangladesh retail pricing for each product.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function catalog(): array
    {
        return [
            'cleansers' => [
                [
                    'name' => 'CeraVe Foaming Facial Cleanser',
                    'brand' => 'CeraVe',
                    'origin' => 'USA',
                    'size' => '236ml',
                    'short_description' => 'A gel-to-foam daily cleanser with three essential ceramides and niacinamide that removes excess oil without disrupting the skin barrier.',
                    'description' => "Developed with dermatologists, CeraVe Foaming Facial Cleanser combines a gentle foaming formula with three essential ceramides (1, 3, 6-II) and niacinamide to cleanse normal to oily skin while helping restore and maintain the skin's natural barrier. MVE Delivery Technology releases these ingredients continuously, so skin feels clean without feeling tight or stripped.",
                    'ingredients' => 'Ceramides 1, 3 & 6-II, Niacinamide, Hyaluronic Acid, MVE Technology',
                    'price' => 1350,
                    'stock' => 80,
                    'skin_types' => ['Normal', 'Combination', 'Oily'],
                    'tags' => ['Bestseller'],
                    'labels' => ['Hot'],
                ],
                [
                    'name' => 'Simple Kind To Skin Refreshing Facial Wash',
                    'brand' => 'Simple',
                    'origin' => 'United Kingdom',
                    'size' => '150ml',
                    'short_description' => 'A soap-free, everyday facial wash with no artificial perfume or colour, formulated for sensitive skin.',
                    'description' => "Simple Kind To Skin Refreshing Facial Wash gently removes dirt, make-up and impurities without leaving skin feeling tight. Formulated with skin-loving Vitamins B5 and E, and free from harsh chemicals, artificial perfume and colour, it's dermatologically tested for sensitive skin.",
                    'ingredients' => 'Vitamin B5, Vitamin E, Glycerin — no artificial perfume or colour',
                    'price' => 520,
                    'stock' => 120,
                    'skin_types' => ['Sensitive', 'Normal'],
                    'tags' => ['Fragrance-Free'],
                ],
                [
                    'name' => 'COSRX Low pH Good Morning Gel Cleanser',
                    'brand' => 'COSRX',
                    'origin' => 'South Korea',
                    'size' => '150ml',
                    'short_description' => 'A low pH, tea-tree infused gel cleanser that refreshes skin each morning without over-stripping it.',
                    'description' => 'Formulated at skin-friendly low pH, COSRX Low pH Good Morning Gel Cleanser uses BHA and tea tree leaf water to gently clear away overnight build-up and excess sebum, leaving skin calm and balanced rather than tight or dry.',
                    'ingredients' => 'Tea Tree Leaf Water, Betaine Salicylate (BHA), Panthenol',
                    'price' => 980,
                    'stock' => 90,
                    'skin_types' => ['Oily', 'Combination', 'Acne-Prone'],
                    'tags' => ['Vegan', 'Cruelty-Free'],
                ],
                [
                    'name' => 'Cetaphil Gentle Skin Cleanser',
                    'brand' => 'Cetaphil',
                    'origin' => 'USA',
                    'size' => '250ml',
                    'short_description' => 'A soap-free, fragrance-free cleanser trusted by dermatologists for sensitive and dry skin.',
                    'description' => "Cetaphil Gentle Skin Cleanser has been a dermatologist-recommended staple for decades. Its soap-free, non-foaming formula cleanses without disturbing the skin's natural moisture balance, making it suitable for even the most sensitive and dry skin types.",
                    'ingredients' => 'Glycerin, Panthenol, Niacinamide — soap-free and fragrance-free',
                    'price' => 890,
                    'sale_price' => 750,
                    'stock' => 100,
                    'skin_types' => ['Sensitive', 'Dry', 'Normal'],
                    'tags' => ['Fragrance-Free', 'Bestseller', 'Sale'],
                    'labels' => ['Sale'],
                ],
            ],

            'toners' => [
                [
                    'name' => 'Klairs Supple Preparation Unscented Toner',
                    'brand' => 'Klairs',
                    'origin' => 'South Korea',
                    'size' => '180ml',
                    'short_description' => 'A hydrating, fragrance-free toner that preps skin for the rest of the routine without any stinging.',
                    'description' => 'Klairs Supple Preparation Unscented Toner is a lightweight, deeply hydrating toner formulated without fragrance or essential oils, making it a favourite for sensitive and reactive skin. It softens and preps skin so the products that follow absorb more effectively.',
                    'ingredients' => 'Beta-Glucan, Sodium Hyaluronate, Panthenol',
                    'price' => 1450,
                    'stock' => 70,
                    'skin_types' => ['Sensitive', 'Dry', 'Normal'],
                    'tags' => ['Fragrance-Free'],
                ],
                [
                    'name' => 'COSRX AHA/BHA Clarifying Treatment Toner',
                    'brand' => 'COSRX',
                    'origin' => 'South Korea',
                    'size' => '150ml',
                    'short_description' => 'An exfoliating toner with AHA and BHA that clears pores and smooths texture for acne-prone skin.',
                    'description' => 'This cult-favourite toner combines gentle AHA and BHA exfoliation with soothing Centella Asiatica extract to clear congested pores, refine texture, and calm breakout-prone skin without over-drying it.',
                    'ingredients' => 'Betaine Salicylate (BHA), Glycolic Acid (AHA), Centella Asiatica Extract',
                    'price' => 1400,
                    'stock' => 65,
                    'skin_types' => ['Oily', 'Acne-Prone', 'Combination'],
                    'tags' => ['Bestseller'],
                ],
                [
                    'name' => 'Thayers Witch Hazel Alcohol-Free Toner',
                    'brand' => 'Thayers',
                    'origin' => 'USA',
                    'size' => '355ml',
                    'short_description' => 'An alcohol-free witch hazel toner with aloe vera and rose petal that tones without drying.',
                    'description' => 'A long-time American skincare staple, Thayers Witch Hazel Toner combines witch hazel with soothing aloe vera and rose petal water. The alcohol-free formula tones and refreshes without the dryness typical of older-style toners.',
                    'ingredients' => 'Witch Hazel, Aloe Vera, Rose Petal Water — alcohol-free',
                    'price' => 1650,
                    'stock' => 55,
                    'skin_types' => ['Normal', 'Combination', 'Oily'],
                ],
                [
                    'name' => 'Simple Soothing Facial Toner',
                    'brand' => 'Simple',
                    'origin' => 'United Kingdom',
                    'size' => '200ml',
                    'short_description' => 'A gentle, alcohol-free toner that removes the last traces of make-up and refreshes skin.',
                    'description' => "Simple Soothing Facial Toner sweeps away the last traces of cleanser and make-up while refreshing skin. Free from artificial perfume, colour and harsh chemicals, it's formulated to be kind even to easily-irritated skin.",
                    'ingredients' => 'Vitamin E, Cucumber Extract — alcohol-free',
                    'price' => 480,
                    'stock' => 110,
                    'skin_types' => ['Sensitive', 'Normal'],
                    'tags' => ['Fragrance-Free'],
                ],
            ],

            'serums' => [
                [
                    'name' => 'The Ordinary Niacinamide 10% + Zinc 1%',
                    'brand' => 'The Ordinary',
                    'origin' => 'Canada',
                    'size' => '30ml',
                    'short_description' => 'A high-strength vitamin and mineral blemish formula that visibly targets the appearance of blemishes and congestion.',
                    'description' => "This popular water-based serum combines 10% niacinamide with 1% zinc PCA to help balance visible sebum activity and support a clearer-looking complexion. Part of The Ordinary's clinical-formulation range, it's designed to be layered into an existing routine.",
                    'ingredients' => 'Niacinamide 10%, Zinc PCA 1%',
                    'price' => 990,
                    'sale_price' => 850,
                    'stock' => 95,
                    'skin_types' => ['Oily', 'Combination', 'Acne-Prone'],
                    'tags' => ['Bestseller', 'Vegan', 'Sale'],
                    'labels' => ['Hot', 'Sale'],
                ],
                [
                    'name' => 'The Ordinary Hyaluronic Acid 2% + B5',
                    'brand' => 'The Ordinary',
                    'origin' => 'Canada',
                    'size' => '30ml',
                    'short_description' => 'A multi-weight hyaluronic acid serum that hydrates skin at multiple layers for a plumper look.',
                    'description' => 'This serum combines several forms of hyaluronic acid with vitamin B5 to attract and retain moisture at different depths of the skin, supporting a smoother, more hydrated appearance.',
                    'ingredients' => 'Hyaluronic Acid 2%, Vitamin B5',
                    'price' => 950,
                    'stock' => 100,
                    'skin_types' => ['Dry', 'Normal', 'Combination', 'Mature'],
                    'tags' => ['Bestseller', 'Vegan'],
                ],
                [
                    'name' => 'COSRX Advanced Snail 96 Mucin Power Essence',
                    'brand' => 'COSRX',
                    'origin' => 'South Korea',
                    'size' => '100ml',
                    'short_description' => 'A lightweight essence with 96% snail secretion filtrate that hydrates and helps repair the look of damaged skin.',
                    'description' => "One of COSRX's signature products, this essence is built around 96% snail secretion filtrate, known for its hydrating and skin-repairing reputation in K-beauty routines. Its sticky-to-silky texture absorbs to leave skin dewy and comfortable.",
                    'ingredients' => 'Snail Secretion Filtrate 96%, Betaine, Sodium Hyaluronate',
                    'price' => 1550,
                    'stock' => 60,
                    'skin_types' => ['Dry', 'Combination', 'Normal'],
                    'tags' => ['Bestseller'],
                    'labels' => ['Hot'],
                ],
                [
                    'name' => 'La Roche-Posay Pure Vitamin C10 Serum',
                    'brand' => 'La Roche-Posay',
                    'origin' => 'France',
                    'size' => '30ml',
                    'short_description' => 'A pure vitamin C serum that brightens the look of skin and supports a more even tone.',
                    'description' => "Formulated with 10% pure vitamin C and La Roche-Posay's thermal spring water, this serum is designed to visibly brighten skin and soften the look of fine lines, while being suitable for sensitive skin thanks to its dermatologically-tested formula.",
                    'ingredients' => 'Pure Vitamin C 10%, Salicylic Acid, Neurosensine, LRP Thermal Spring Water',
                    'price' => 3400,
                    'stock' => 35,
                    'skin_types' => ['Normal', 'Combination', 'Mature'],
                    'labels' => ['Limited'],
                ],
            ],

            'moisturizers' => [
                [
                    'name' => 'CeraVe Moisturizing Cream',
                    'brand' => 'CeraVe',
                    'origin' => 'USA',
                    'size' => '340g',
                    'short_description' => 'A rich, non-greasy 24-hour moisturiser with three ceramides and hyaluronic acid for normal to dry skin.',
                    'description' => 'CeraVe Moisturizing Cream provides 24-hour hydration using a blend of three essential ceramides, hyaluronic acid, and MVE Delivery Technology, which releases ingredients over time. Its rich yet fast-absorbing formula is developed with dermatologists for normal to dry skin.',
                    'ingredients' => 'Ceramides 1, 3 & 6-II, Hyaluronic Acid, MVE Technology',
                    'price' => 1750,
                    'sale_price' => 1450,
                    'stock' => 85,
                    'skin_types' => ['Dry', 'Normal', 'Sensitive'],
                    'tags' => ['Bestseller', 'Sale'],
                    'labels' => ['Sale'],
                ],
                [
                    'name' => 'Neutrogena Hydro Boost Water Gel',
                    'brand' => 'Neutrogena',
                    'origin' => 'USA',
                    'size' => '50g',
                    'short_description' => 'An oil-free water gel with hyaluronic acid that instantly quenches dry skin and plumps it with hydration.',
                    'description' => "Neutrogena Hydro Boost Water Gel uses hyaluronic acid within a unique gel texture that mimics skin's own reservoir for hydration, instantly quenching dryness and plumping skin without a heavy or greasy after-feel.",
                    'ingredients' => 'Hyaluronic Acid, Glycerin — oil-free',
                    'price' => 1500,
                    'stock' => 75,
                    'skin_types' => ['Dry', 'Combination', 'Normal'],
                    'tags' => ['Bestseller'],
                ],
                [
                    'name' => 'La Roche-Posay Toleriane Double Repair Face Moisturizer',
                    'brand' => 'La Roche-Posay',
                    'origin' => 'France',
                    'size' => '40ml',
                    'short_description' => 'A daily moisturiser with ceramide-3 and niacinamide that hydrates while helping restore the skin barrier.',
                    'description' => 'Formulated with prebiotic thermal spring water, ceramide-3 and niacinamide, this daily moisturiser hydrates for 48 hours while supporting the skin\'s natural barrier. Its lightweight, fast-absorbing texture suits normal to dry, sensitive skin.',
                    'ingredients' => 'Ceramide-3, Niacinamide, Glycerin, LRP Thermal Spring Water',
                    'price' => 2450,
                    'stock' => 45,
                    'skin_types' => ['Sensitive', 'Dry', 'Normal'],
                ],
                [
                    'name' => 'Simple Water Boost Hydrating Gel Cream',
                    'brand' => 'Simple',
                    'origin' => 'United Kingdom',
                    'size' => '50ml',
                    'short_description' => 'A light gel-cream that delivers a burst of hydration without clogging pores.',
                    'description' => "Simple Water Boost Hydrating Gel Cream absorbs quickly to lock in moisture without a heavy or sticky finish. Free from artificial perfume and colour, it's built for sensitive skin that still wants a hydration boost.",
                    'ingredients' => 'Glycerin, Vitamin B3, Vitamin E',
                    'price' => 780,
                    'stock' => 90,
                    'skin_types' => ['Sensitive', 'Combination', 'Normal'],
                    'tags' => ['Fragrance-Free'],
                ],
            ],

            'sunscreens' => [
                [
                    'name' => 'La Roche-Posay Anthelios UVMune 400 Invisible Fluid SPF50+',
                    'brand' => 'La Roche-Posay',
                    'origin' => 'France',
                    'size' => '50ml',
                    'short_description' => 'A broad-spectrum SPF50+ fluid with an invisible, non-greasy finish for daily UV protection.',
                    'description' => 'Anthelios UVMune 400 offers very high, broad-spectrum protection including extended UVA coverage, in an ultra-light fluid texture that leaves no white cast and absorbs quickly, making it comfortable enough for daily wear under make-up.',
                    'ingredients' => 'Mexoryl 400 Filter System, Glycerin — SPF50+',
                    'price' => 3000,
                    'stock' => 60,
                    'skin_types' => ['Normal', 'Combination', 'Sensitive'],
                    'tags' => ['Bestseller'],
                    'labels' => ['Exclusive'],
                ],
                [
                    'name' => 'Neutrogena Ultra Sheer Dry-Touch Sunscreen SPF50+',
                    'brand' => 'Neutrogena',
                    'origin' => 'USA',
                    'size' => '88ml',
                    'short_description' => 'A lightweight, fast-absorbing sunscreen with a dry-touch finish and broad-spectrum SPF50+.',
                    'description' => 'A long-standing favourite, Neutrogena Ultra Sheer Dry-Touch Sunscreen provides broad-spectrum SPF50+ protection in a light, non-greasy formula that absorbs quickly, leaving skin with a dry, comfortable, non-shiny finish.',
                    'ingredients' => 'Avobenzone, Homosalate, Octisalate — SPF50+',
                    'price' => 1750,
                    'sale_price' => 1500,
                    'stock' => 70,
                    'skin_types' => ['Normal', 'Combination', 'Oily'],
                    'tags' => ['Bestseller', 'Sale'],
                    'labels' => ['Sale'],
                ],
                [
                    'name' => 'Beauty of Joseon Relief Sun: Rice + Probiotics SPF50+',
                    'brand' => 'Beauty of Joseon',
                    'origin' => 'South Korea',
                    'size' => '50ml',
                    'short_description' => 'A lightweight Korean sunscreen with rice extract and probiotics that layers well under make-up with no white cast.',
                    'description' => 'This widely-loved Korean sunscreen combines SPF50+ PA+++ broad-spectrum protection with rice extract and probiotics, leaving a natural, dewy finish with no white cast.',
                    'ingredients' => 'Rice Extract, Probiotics Extract — SPF50+ PA+++',
                    'price' => 1500,
                    'stock' => 65,
                    'skin_types' => ['Normal', 'Combination', 'Dry'],
                    'tags' => ['Bestseller', 'Vegan'],
                ],
                [
                    'name' => 'Some By Mi 100 Vegan Ultra-Light Sun Essence SPF50+',
                    'brand' => 'Some By Mi',
                    'origin' => 'South Korea',
                    'size' => '50ml',
                    'short_description' => 'A vegan, ultra-light sun essence with SPF50+ PA++++ that feels weightless on skin.',
                    'description' => 'Formulated as a 100% vegan essence-type sunscreen, this lightweight formula provides SPF50+ PA++++ protection while feeling almost weightless, making it suited to oily and combination skin.',
                    'ingredients' => 'Centella Asiatica Extract, Niacinamide — SPF50+ PA++++, Vegan',
                    'price' => 1350,
                    'stock' => 55,
                    'skin_types' => ['Oily', 'Combination'],
                    'tags' => ['Vegan', 'Cruelty-Free', 'New Arrival'],
                    'labels' => ['New'],
                ],
            ],

            'after-sun' => [
                [
                    'name' => 'Nivea After Sun Soothing Moisturising Lotion',
                    'brand' => 'Nivea',
                    'origin' => 'Germany',
                    'size' => '200ml',
                    'short_description' => 'A cooling after-sun lotion that soothes and moisturises skin following sun exposure.',
                    'description' => 'Nivea After Sun Soothing Moisturising Lotion cools and hydrates skin after time in the sun, replenishing moisture lost to UV exposure and helping skin feel calm and comfortable.',
                    'ingredients' => 'Provitamin B5, Glycerin',
                    'price' => 750,
                    'stock' => 80,
                    'skin_types' => ['Normal', 'Dry'],
                ],
                [
                    'name' => 'Aloe Pura Organic Aloe Vera Gel',
                    'brand' => 'Aloe Pura',
                    'origin' => 'United Kingdom',
                    'size' => '200ml',
                    'short_description' => 'A soothing, organic aloe vera gel ideal for calming skin after sun exposure.',
                    'description' => 'Made with a high concentration of organic aloe vera, this lightweight gel absorbs quickly to cool and soothe skin, making it a popular after-sun option as well as an everyday moisturiser for sensitive skin.',
                    'ingredients' => 'Organic Aloe Vera (99.5%)',
                    'price' => 950,
                    'stock' => 60,
                    'skin_types' => ['Sensitive', 'Normal'],
                    'tags' => ['Organic'],
                ],
                [
                    'name' => 'Vaseline Intensive Care Aloe Soothe Body Lotion',
                    'brand' => 'Vaseline',
                    'origin' => 'USA',
                    'size' => '400ml',
                    'short_description' => 'An everyday moisturising body lotion with aloe vera that soothes and hydrates dry skin.',
                    'description' => "Vaseline Intensive Care Aloe Soothe Body Lotion combines Vaseline's signature moisture-locking technology with aloe vera extract to soothe and hydrate dry skin, making it a practical everyday lotion that also works well after sun exposure.",
                    'ingredients' => 'Aloe Vera Extract, Glycerin',
                    'price' => 480,
                    'sale_price' => 400,
                    'stock' => 100,
                    'skin_types' => ['Normal', 'Dry'],
                    'tags' => ['Bestseller', 'Sale'],
                    'labels' => ['Sale'],
                ],
                [
                    'name' => 'Banana Boat Soothing Aloe After Sun Gel',
                    'brand' => 'Banana Boat',
                    'origin' => 'USA',
                    'size' => '230g',
                    'short_description' => 'A cooling aloe gel formulated specifically to soothe skin after sun exposure.',
                    'description' => 'Purpose-built as an after-sun treatment, Banana Boat Soothing Aloe Gel cools on contact and moisturises with aloe vera, helping skin recover comfort after a day outdoors.',
                    'ingredients' => 'Aloe Vera Gel, Vitamin E',
                    'price' => 980,
                    'stock' => 50,
                    'skin_types' => ['Normal', 'Combination'],
                ],
            ],

            'shampoos' => [
                [
                    'name' => 'Head & Shoulders Anti-Dandruff Menthol Cool Shampoo',
                    'brand' => 'Head & Shoulders',
                    'origin' => 'USA',
                    'short_description' => 'A menthol-cool anti-dandruff shampoo that clears flakes and leaves scalp feeling refreshed.',
                    'description' => 'Head & Shoulders Menthol Cool delivers up to 100% dandruff protection with a cooling sensation, clinically proven to reduce flakes from the first wash while caring for scalp health with regular use.',
                    'ingredients' => 'Pyrithione Zinc, Menthol',
                    'price' => 580,
                    'tags' => ['Bestseller'],
                    'variants' => [
                        ['size' => '180ml', 'price' => 380, 'stock' => 60],
                        ['size' => '340ml', 'price' => 580, 'stock' => 90],
                        ['size' => '650ml', 'price' => 950, 'stock' => 50],
                    ],
                ],
                [
                    'name' => 'Pantene Pro-V Hair Fall Control Shampoo',
                    'brand' => 'Pantene',
                    'origin' => 'USA',
                    'short_description' => 'A Pro-V formula shampoo that strengthens hair from root to tip to help reduce hair fall due to breakage.',
                    'description' => 'Pantene Pro-V Hair Fall Control Shampoo is formulated with Pro-Vitamin B5 to nourish and strengthen hair, helping reduce hair fall caused by breakage with regular use, leaving hair smooth and manageable.',
                    'ingredients' => 'Pro-Vitamin B5, Niacinamide',
                    'price' => 480,
                    'tags' => ['Bestseller'],
                    'variants' => [
                        ['size' => '185ml', 'price' => 320, 'stock' => 70],
                        ['size' => '340ml', 'price' => 480, 'stock' => 85],
                        ['size' => '650ml', 'price' => 820, 'stock' => 40],
                    ],
                ],
                [
                    'name' => 'Sunsilk Lusciously Thick & Long Shampoo',
                    'brand' => 'Sunsilk',
                    'origin' => 'United Kingdom',
                    'short_description' => 'A shampoo formulated to help hair look thicker and grow longer with regular use.',
                    'description' => 'Sunsilk Lusciously Thick & Long Shampoo is infused with a nourishing formula designed to strengthen strands, reduce breakage, and support the appearance of thicker, longer-looking hair over time.',
                    'ingredients' => 'Keratin Amino Acids, Vitamin E',
                    'price' => 340,
                    'variants' => [
                        ['size' => '160ml', 'price' => 180, 'stock' => 90],
                        ['size' => '350ml', 'price' => 340, 'stock' => 110],
                    ],
                ],
                [
                    'name' => 'TRESemmé Keratin Smooth Shampoo',
                    'brand' => 'TRESemmé',
                    'origin' => 'USA',
                    'short_description' => 'A keratin-infused shampoo that smooths frizz for up to 48 hours of salon-smooth hair.',
                    'description' => 'TRESemmé Keratin Smooth Shampoo, with a keratin-infused formula, helps tame frizz and smooth hair for up to 48 hours, giving a salon-fresh finish that\'s easy to manage.',
                    'ingredients' => 'Keratin, Silicone Blend',
                    'price' => 620,
                    'tags' => ['Bestseller'],
                    'variants' => [
                        ['size' => '190ml', 'price' => 380, 'stock' => 60],
                        ['size' => '340ml', 'price' => 620, 'stock' => 70],
                    ],
                ],
            ],

            'conditioners' => [
                [
                    'name' => 'TRESemmé Keratin Smooth Conditioner',
                    'brand' => 'TRESemmé',
                    'origin' => 'USA',
                    'short_description' => 'A keratin conditioner that detangles and smooths hair, pairing with the Keratin Smooth Shampoo.',
                    'description' => 'This keratin-infused conditioner detangles and smooths hair after washing, helping fight frizz and leaving hair soft, silky and easier to style.',
                    'ingredients' => 'Keratin, Shea Butter',
                    'price' => 620,
                    'variants' => [
                        ['size' => '190ml', 'price' => 380, 'stock' => 55],
                        ['size' => '340ml', 'price' => 620, 'stock' => 65],
                    ],
                ],
                [
                    'name' => 'Sunsilk Lusciously Thick & Long Conditioner',
                    'brand' => 'Sunsilk',
                    'origin' => 'United Kingdom',
                    'short_description' => 'A conditioner that nourishes hair to support thicker, longer-looking strands.',
                    'description' => 'Formulated to complement the Lusciously Thick & Long shampoo, this conditioner nourishes and detangles hair, supporting the look of thicker, longer strands with continued use.',
                    'ingredients' => 'Keratin Amino Acids, Vitamin E',
                    'price' => 350,
                    'variants' => [
                        ['size' => '160ml', 'price' => 190, 'stock' => 85],
                        ['size' => '350ml', 'price' => 350, 'stock' => 95],
                    ],
                ],
                [
                    'name' => 'Pantene Pro-V 2-in-1 Conditioner',
                    'brand' => 'Pantene',
                    'origin' => 'USA',
                    'short_description' => 'A Pro-V conditioner that nourishes and detangles hair for smooth, manageable results.',
                    'description' => 'Pantene Pro-V 2-in-1 Conditioner nourishes hair with Pro-Vitamin B5, detangling strands and leaving hair soft and manageable after every wash.',
                    'ingredients' => 'Pro-Vitamin B5',
                    'price' => 450,
                    'variants' => [
                        ['size' => '180ml', 'price' => 280, 'stock' => 75],
                        ['size' => '340ml', 'price' => 450, 'stock' => 60],
                    ],
                ],
                [
                    'name' => 'Dove Intense Repair Conditioner',
                    'brand' => 'Dove',
                    'origin' => 'United Kingdom',
                    'short_description' => 'A repairing conditioner formulated to nourish visibly damaged hair.',
                    'description' => 'Dove Intense Repair Conditioner is formulated with a Fibre Actives blend to help nourish and rebuild visibly damaged hair from within, leaving it noticeably smoother and stronger.',
                    'ingredients' => 'Fibre Actives, Keratin Actives',
                    'price' => 560,
                    'tags' => ['Bestseller'],
                    'variants' => [
                        ['size' => '180ml', 'price' => 340, 'stock' => 70],
                        ['size' => '330ml', 'price' => 560, 'stock' => 50],
                    ],
                ],
            ],

            'hair-oils' => [
                [
                    'name' => 'Parachute Advansed Coconut Hair Oil',
                    'brand' => 'Parachute',
                    'origin' => 'Bangladesh',
                    'short_description' => '100% pure coconut hair oil that nourishes hair and scalp from root to tip.',
                    'description' => 'A household name across South Asia, Parachute Advansed Coconut Hair Oil is 100% pure and refined coconut oil that deeply nourishes hair and scalp, helping reduce dryness and breakage with regular oiling.',
                    'ingredients' => '100% Pure Coconut Oil',
                    'price' => 560,
                    'free_shipping' => true,
                    'tags' => ['Bestseller'],
                    'labels' => ['Hot'],
                    'variants' => [
                        ['size' => '100ml', 'price' => 150, 'stock' => 150],
                        ['size' => '200ml', 'price' => 260, 'stock' => 140],
                        ['size' => '500ml', 'price' => 560, 'stock' => 80],
                    ],
                ],
                [
                    'name' => 'Dabur Amla Hair Oil',
                    'brand' => 'Dabur',
                    'origin' => 'India',
                    'short_description' => 'A traditional amla-based hair oil that strengthens hair and nourishes the scalp.',
                    'description' => 'Dabur Amla Hair Oil is enriched with amla (Indian gooseberry), long used in traditional hair care to strengthen hair from root to tip, add shine, and support a healthy scalp.',
                    'ingredients' => 'Amla (Indian Gooseberry) Extract, Coconut Oil Base',
                    'price' => 420,
                    'free_shipping' => true,
                    'variants' => [
                        ['size' => '200ml', 'price' => 220, 'stock' => 90],
                        ['size' => '450ml', 'price' => 420, 'stock' => 60],
                    ],
                ],
                [
                    'name' => 'Kesh King Ayurvedic Hair Oil',
                    'brand' => 'Kesh King',
                    'origin' => 'India',
                    'short_description' => 'An Ayurvedic hair oil blended with multiple herbs to help reduce hair fall and dandruff.',
                    'description' => 'Kesh King Ayurvedic Hair Oil is formulated with a blend of Ayurvedic herbs traditionally used to nourish the scalp and strengthen hair, helping address concerns like hair fall and dandruff with regular use.',
                    'ingredients' => 'Ayurvedic Herbal Blend, Sesame Oil Base',
                    'price' => 380,
                    'free_shipping' => true,
                    'tags' => ['Organic'],
                    'variants' => [
                        ['size' => '200ml', 'price' => 380, 'stock' => 50],
                    ],
                ],
                [
                    'name' => 'WOW Skin Science Onion Black Seed Hair Oil',
                    'brand' => 'WOW Skin Science',
                    'origin' => 'India',
                    'short_description' => 'An onion and black seed oil blend formulated to nourish scalp and support healthier-looking hair.',
                    'description' => 'This blend combines red onion seed oil with black seed oil and other botanical extracts, a popular modern formulation aimed at nourishing the scalp and supporting stronger, healthier-looking hair.',
                    'ingredients' => 'Red Onion Seed Oil, Black Seed Oil, Almond Oil',
                    'price' => 1100,
                    'free_shipping' => true,
                    'tags' => ['Bestseller', 'Cruelty-Free', 'New Arrival'],
                    'labels' => ['New'],
                    'variants' => [
                        ['size' => '100ml', 'price' => 650, 'stock' => 45],
                        ['size' => '200ml', 'price' => 1100, 'stock' => 35],
                    ],
                ],
            ],
        ];
    }
}
