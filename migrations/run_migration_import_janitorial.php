<?php
$pageTitle = 'Import Janitorial Data — Migration';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo 'Admin only.'; exit; }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// ═══════════════════════════════════════════════════════════════
// DATA DEFINITIONS
// ═══════════════════════════════════════════════════════════════

// Task type
$task_type = 'Cleaning';

// 33 Tasks: name => description (instructions)
$tasks = [
    'Breakdown Sunday' => 'Wipe down Tables with 2in1 and put away. Empty Coffee Trash. Roll up Rugs and store above Tables. Put Communion Elements in Refrigerator.',
    'Clean Carpets' => 'Use Carpet Machine to clean Coffee Stains.',
    'Disinfect Changing Table' => 'Spray 2in1 on a Paper Towel and wipe down surfaces.',
    'Disinfect Water Fountain' => 'Spray 2in1 on fountain and wipe off with Paper Towels.',
    'Empty Diaper Genie' => 'Empty from below.',
    'Empty Trash Bathroom' => 'Empty trash Including stall bags.',
    'Empty Trash Large' => 'Empty trash being careful of leaking bags.',
    'Empty Trash Small' => 'Empty trash being careful of leaking bags.',
    'Mop' => 'Mix 6oz floor cleaner and 3gal hot water in mop bucket.',
    'Pickup Trash' => 'Pickup communion cups and other trash on floor and seats, and in seat backs.',
    'Scrub Showers' => 'If used, spray shower with 2in1, scrub with shower brush, rinse with water.',
    'Scrub Sinks' => 'Use Barkeepers Friend in bowl, Scrub with sink brush. Finish with 2in1 and Paper Towels.',
    'Scrub Toilets' => 'Scrub Toilet Bowls with Toilet Bowl Cleaner and Toilet Brush. Spray entire Toilet with 2in1 and wipe with Paper Towels.',
    'Scrub Urinals' => 'Scrub toilet bowls and urinals with brush and disinfect under seats.',
    'Spot Clean Door Glass' => 'Clean handprints off glass doors.',
    'Spot Clean Glass' => 'Clean visibly dirty spots.',
    'Spot Clean Mirrors' => 'Clean visibly dirty spots.',
    'Stock Bathroom Supplies' => 'Refill Paper Towels, hand soap, Toilet Paper.',
    'Stock Kitchen Supplies' => 'Refill Paper Towels and hand soap.',
    'Straighten Chairs' => 'Arrange chairs in straight rows on floor marks.',
    'Sweep' => 'Sweep Floors with broom.',
    'Sweep Stairs' => 'Sweep Stairs with broom.',
    'Vacuum Light Traffic' => 'Use Backpack Vacuum.',
    'Vacuum High Traffic' => 'Use corded Vacuum.',
    'Vacuum Rugs' => 'Use corded Vacuum.',
    'Vacuum Stairs' => 'Use handheld Vacuum from janitorial closet.',
    'Wash Towels' => 'Collect Towels from kitchens and janitorial closets in both buildings. Wash in Laundry Room.',
    'Wipe down Counters' => 'Spray with 2in1, wipe with Paper Towel.',
    'Wipe down Sink' => 'Spray with 2in1, wipe with Paper Towel.',
    'Wipe down Stair Rails' => 'Spray 2in1 on a Paper Towel and wipe down surfaces.',
    'Wipe down Tables' => 'Spray with 2in1, wipe with Paper Towel.',
    'Wipe down Toilets' => 'Spray Seat, Rim, and Handle with 2in1, wipe with Paper Towel.',
    'Wipe down Urinals' => 'Spray Rim and Handle with 2in1, wipe with Paper Towel.',
];

// 31 Real supplies from Supplies.xlsx: [name, supplier, part#, rack, shelf, slot, unit, low, high, qty_to_order, order_unit]
$supplies_data = [
    ['Plastic Bottles with Sprayers - 16 oz', 'ULINE', 'S-11686', 1,1,1, 'Bottles', 2, 8, 1, 'Case'],
    ['Plastic Bottles with Sprayers - 24 oz', 'ULINE', 'S-7272', 1,1,2, 'Bottles', 2, 8, 1, 'Case'],
    ['Plastic Bottles with Sprayers - 32 oz', 'ULINE', 'S-7273', 1,1,3, 'Bottles', 2, 8, 1, 'Case'],
    ['Uline Aloe Hand Soap - 1 Gallon', 'ULINE', 'S-17081', 2,3,1, 'Bottles', 2, 6, 1, 'Case'],
    ['CLR PRO Calcium, Lime and Rust Remover - 1 Gallon', 'ULINE', 'S-18419', 2,3,2, 'Bottles', 2, 6, 1, 'Case'],
    ['Uline 2in1 Cleaner and Disinfectant - 1 Gallon', 'ULINE', 'S-19374', 2,4,1, 'Bottles', 2, 6, 1, 'Case'],
    ['Uline Multi-Purpose Cleaner - 1 Gallon', 'ULINE', 'S-20690', 2,4,2, 'Bottles', 2, 6, 1, 'Case'],
    ['Uline Neutral Floor Cleaner - 1 Gallon', 'ULINE', 'S-26142', 2,5,1, 'Bottles', 2, 6, 1, 'Case'],
    ['Betco Carpet Cleaner - 1 Gallon', 'ULINE', 'S-26147', 2,5,2, 'Bottles', 2, 6, 1, 'Case'],
    ['Uline Antibacterial Hand Soap - 7.5 oz Dispenser', 'ULINE', 'S-20661', 3,2,1, 'Bottles', 2, 8, 1, 'Case'],
    ['Lysol Toilet Bowl Cleaner - 32 oz', 'ULINE', 'S-7141', 3,3,5, 'Bottles', 4, 16, 1, 'Case'],
    ['Uline Black Latex Gloves - Small', 'ULINE', 'S-19810S', 2,2,2, 'Boxes', 1, 3, 2, 'Box'],
    ['Uline Black Latex Gloves - Medium', 'ULINE', 'S-19810M', 2,2,3, 'Boxes', 1, 3, 2, 'Box'],
    ['Uline Black Latex Gloves - Large', 'ULINE', 'S-19810L', 2,2,4, 'Boxes', 1, 3, 2, 'Box'],
    ['Uline Black Latex Gloves - XL', 'ULINE', 'S-19810X', 2,2,5, 'Boxes', 1, 3, 2, 'Box'],
    ['Kleenex Boutique Facial Tissue', 'ULINE', 'S-6873', 4,3,1, 'Boxes', 6, 42, 1, 'Case'],
    ['Toilet Seat Covers', 'ULINE', 'S-7276', 4,3,2, 'Boxes', 6, 18, 2, 'Case'],
    ['Uline Kraft Multi-Fold Towels', 'ULINE', 'S-13735', 4,1,1, 'Bundles', 4, 16, 1, 'Case'],
    ['Bar Keepers Friend - 21 oz Powder', 'ULINE', 'S-20095', 3,3,1, 'Cans', 2, 14, 1, 'Case'],
    ['Weiman Stainless Steel Cleaner and Polish - 17 oz', 'ULINE', 'S-21521', 3,3,2, 'Cans', 2, 8, 1, 'Case'],
    ['Goof Off Spray - 12 oz', 'ULINE', 'S-19127', 3,3,3, 'Cans', 2, 8, 1, 'Case'],
    ['Uline Foaming Glass Cleaner - 19 oz', 'ULINE', 'S-22344', 3,3,4, 'Cans', 2, 14, 1, 'Case'],
    ['Uline EZ Pull Center Pull Paper Towels', 'ULINE', 'S-15752', 1,2,1, 'Cases', 4, 12, 8, 'Case'],
    ['Uline Toilet Tissue', 'ULINE', 'S-7131', 1,3,1, 'Cases', 1, 2, 1, 'Case'],
    ['Uline Economy Trash Liners - 12-16 Gallon Clear', 'ULINE', 'S-11671C', 3,4,1, 'Cases', 2, 2, 1, 'Case'],
    ['Sanitary Napkin Receptacle Liners', 'ULINE', 'S-15960', 3,4,2, 'Cases', 1, 2, 1, 'Case'],
    ['Uline Industrial Trash Liners - 40-45 Gallon Black', 'ULINE', 'S-5109', 3,5,1, 'Cases', 1, 2, 1, 'Case'],
    ['Kirkland Signature Paper Towels', 'Costco', '100234271', 4,4,1, 'Rolls', 12, 36, 2, 'Case'],
    ['Spill Kit', null, null, 4,1,2, null, null, null, null, null],
    ['Uline Disinfecting Wipes - Fresh Scent 75 ct', 'ULINE', 'S-19459FRESH', 4,2,1, 'Cans', 2, 14, 2, 'Case'],
    ['Uline Deluxe Urinal Screen - Cotton Blossom', 'Uline', 'S-18729COTTN', 3,2,2, 'Case', 1, 2, 1, 'Case'],
];

// 9 Tools
$tools_data = [
    'Backpack Vacuum',
    'Broom',
    'Corded Vacuum',
    'Hand Towel Refill',
    'Handheld Vacuum',
    'Mop',
    'Shower Brush',
    'Sink Brush',
    'Toilet Brush',
];

// Task -> Supply links (generic name => real supply name)
$supply_name_map = [
    '2in1 Cleaner' => 'Uline 2in1 Cleaner and Disinfectant - 1 Gallon',
    'Barkeepers Friend' => 'Bar Keepers Friend - 21 oz Powder',
    'Carpet Cleaner' => 'Betco Carpet Cleaner - 1 Gallon',
    'Carpet Cleaner Solution' => 'Betco Carpet Cleaner - 1 Gallon',
    'Diaper Genie Refill' => null, // Not in supplies list
    'Feminine Hygiene Bags' => 'Sanitary Napkin Receptacle Liners',
    'Floor Cleaner' => 'Uline Neutral Floor Cleaner - 1 Gallon',
    'Glass Cleaner' => 'Uline Foaming Glass Cleaner - 19 oz',
    'Hand Soap Refill' => 'Uline Aloe Hand Soap - 1 Gallon',
    'Paper Towel' => 'Kirkland Signature Paper Towels',
    'Toilet Bowl Cleaner' => 'Lysol Toilet Bowl Cleaner - 32 oz',
    'Toilet Paper Refill' => 'Uline Toilet Tissue',
    'Trash bag Black' => 'Uline Industrial Trash Liners - 40-45 Gallon Black',
    'Trash bag Clear' => 'Uline Economy Trash Liners - 12-16 Gallon Clear',
];

// Task -> generic supply/tool names (from Janitorial.xlsx Tasks sheet)
$task_supply_links = [
    'Breakdown Sunday' => ['s' => ['2in1 Cleaner','Paper Towel','Trash bag Black'], 't' => []],
    'Clean Carpets' => ['s' => ['Carpet Cleaner','Carpet Cleaner Solution'], 't' => []],
    'Disinfect Changing Table' => ['s' => ['2in1 Cleaner','Paper Towel'], 't' => []],
    'Disinfect Water Fountain' => ['s' => ['2in1 Cleaner','Paper Towel'], 't' => []],
    'Empty Diaper Genie' => ['s' => ['Diaper Genie Refill'], 't' => []],
    'Empty Trash Bathroom' => ['s' => ['Feminine Hygiene Bags','Trash bag Clear'], 't' => []],
    'Empty Trash Large' => ['s' => ['Trash bag Black'], 't' => []],
    'Empty Trash Small' => ['s' => ['Trash bag Clear'], 't' => []],
    'Mop' => ['s' => ['Floor Cleaner'], 't' => ['Mop']],
    'Pickup Trash' => ['s' => ['Trash bag Black'], 't' => []],
    'Scrub Showers' => ['s' => ['2in1 Cleaner'], 't' => ['Shower Brush']],
    'Scrub Sinks' => ['s' => ['Barkeepers Friend'], 't' => ['Sink Brush']],
    'Scrub Toilets' => ['s' => ['2in1 Cleaner','Paper Towel','Toilet Bowl Cleaner'], 't' => ['Toilet Brush']],
    'Scrub Urinals' => ['s' => ['2in1 Cleaner','Paper Towel','Toilet Bowl Cleaner'], 't' => ['Toilet Brush']],
    'Spot Clean Door Glass' => ['s' => ['Glass Cleaner','Paper Towel'], 't' => []],
    'Spot Clean Glass' => ['s' => ['Glass Cleaner','Paper Towel'], 't' => []],
    'Spot Clean Mirrors' => ['s' => ['Glass Cleaner','Paper Towel'], 't' => []],
    'Stock Bathroom Supplies' => ['s' => ['Hand Soap Refill','Toilet Paper Refill'], 't' => ['Hand Towel Refill']],
    'Sweep' => ['s' => [], 't' => ['Broom']],
    'Sweep Stairs' => ['s' => [], 't' => ['Broom']],
    'Vacuum Light Traffic' => ['s' => [], 't' => ['Backpack Vacuum']],
    'Vacuum High Traffic' => ['s' => [], 't' => ['Corded Vacuum']],
    'Vacuum Rugs' => ['s' => [], 't' => ['Corded Vacuum']],
    'Vacuum Stairs' => ['s' => [], 't' => ['Handheld Vacuum']],
    'Wipe down Counters' => ['s' => ['2in1 Cleaner','Paper Towel'], 't' => []],
    'Wipe down Sink' => ['s' => ['2in1 Cleaner','Paper Towel'], 't' => []],
    'Wipe down Stair Rails' => ['s' => ['2in1 Cleaner','Paper Towel'], 't' => []],
    'Wipe down Tables' => ['s' => ['2in1 Cleaner','Paper Towel'], 't' => []],
    'Wipe down Toilets' => ['s' => ['2in1 Cleaner','Paper Towel'], 't' => []],
    'Wipe down Urinals' => ['s' => ['2in1 Cleaner','Paper Towel'], 't' => []],
];

// Room mapping: "Build|Room" => [db_room_ids]
// Rooms that map to multiple DB rooms will have the task duplicated to each
$room_map = [
    'NB|Gym' => [65],
    'NB|Hallway_Downstairs' => [90, 89, 91],       // Corridors A, B, C
    'NB|Hallway_Upstairs' => [215, 216],             // Corridors D, E
    'NB|Sanctuary' => [66],
    'NB|Lobby' => [110],
    'NB|Fireside_Room' => [119],
    'NB|RR_Men_Downstairs_North' => [72],
    'NB|RR_Men_Downstairs_South' => [59],
    'NB|RR_Men_Upstairs' => [128],
    'NB|RR_Nursery' => [73],
    'NB|RR_Preschool' => [80],
    'NB|RR_Women_Downstairs_North' => [71],
    'NB|RR_Women_Downstairs_South' => [60],
    'NB|RR_Women_Upstairs' => [135],
    'NB|Family_Room' => [74],
    // NB|Setup => SKIP
    'NB|Cafe' => [122],
    'NB|Coffee_Prep' => [62],
    'NB|Kitchen' => [61],
    'NB|Showers_Men' => [126, 127],                  // Men's Shower 1 & 2
    'NB|Showers_Women' => [133, 134],                 // Women's Shower 1 & 2
    'NB|Backstage' => [93],                           // Green Room
    "NB|Children's_Lobby" => [88],
    'NB|Counseling_Room' => [56],
    'NB|CR_201' => [131],
    'NB|CR_202' => [132],
    'NB|CR_203' => [125],
    'NB|CR_204' => [123],
    'NB|CR_Preschool_East' => [84],
    'NB|CR_Preschool_West' => [83],
    'NB|Nursery_Lobby' => [67],                       // Now just "Nursery"
    'NB|Nursery_Main' => [67],                        // Same Nursery
    'NB|Office_Downstairs' => [103],
    'NB|Office_Upstairs' => [118],
    'NB|Prayer_Rooms' => [82, 81],                    // East & West
    'NB|Preschool_Lobby' => [79],
    'NB|Preschool_Main' => [85],
    'NB|Security_Room' => [58],                       // CommOpps
    'NB|Staff_Break_Room' => [57],
    "NB|Women's_Lounge_Downstairs" => [68],
    "NB|Women's_Lounge_Upstairs" => [136],
    'NB|Elevator' => [77],
    'NB|Stage' => [168],
    'SB|RR_Men_East' => [180],
    'SB|RR_Men_West' => [192],
    'SB|RR_Women_East' => [181],
    'SB|RR_Women_West' => [191],
    // SB|Setup => SKIP
    'SB|Kitchen' => [182],
    'SB|Chapel' => [170],
    'SB|CR_100' => [179],
    'SB|CR_101' => [185],
    'SB|CR_102' => [193],
    'SB|CR_103' => [194],
    // SB|CR_104 => SKIP (doesn't exist)
    'SB|CR_201' => [211],
    'SB|CR_202' => [205],
    'SB|CR_203' => [203],
    'SB|CR_204' => [202],
    'SB|CR_205' => [204],
    'SB|Fellowship' => [177],
    'SB|Lobby' => [176],                              // Lounge
    'SB|Stairs' => [187, 198],                        // Stair 1 & 2
    'SB|Hallway_Downstairs' => [189],                 // Corridor A
    'SB|Hallway_Upstairs' => [210],                   // Corridor B
    'SB|Stage' => [217],
];

// Weekly schedule: [build, room, task] => true (unique combos only, for task_rooms)
$weekly_assignments = [
    ['SB','Hallway_Downstairs','Vacuum Light Traffic'],
    ['SB','Hallway_Upstairs','Vacuum Light Traffic'],
    ['SB','RR_Men_East','Empty Trash Bathroom'],['SB','RR_Men_East','Stock Bathroom Supplies'],
    ['SB','RR_Men_East','Sweep'],['SB','RR_Men_East','Wipe down Sink'],
    ['SB','RR_Men_East','Wipe down Toilets'],['SB','RR_Men_East','Wipe down Urinals'],
    ['SB','RR_Men_West','Empty Trash Bathroom'],['SB','RR_Men_West','Stock Bathroom Supplies'],
    ['SB','RR_Men_West','Sweep'],['SB','RR_Men_West','Wipe down Sink'],
    ['SB','RR_Men_West','Wipe down Toilets'],['SB','RR_Men_West','Wipe down Urinals'],
    ['SB','RR_Women_East','Empty Trash Bathroom'],['SB','RR_Women_East','Stock Bathroom Supplies'],
    ['SB','RR_Women_East','Sweep'],['SB','RR_Women_East','Wipe down Sink'],
    ['SB','RR_Women_East','Wipe down Toilets'],
    ['SB','RR_Women_West','Empty Trash Bathroom'],['SB','RR_Women_West','Stock Bathroom Supplies'],
    ['SB','RR_Women_West','Sweep'],['SB','RR_Women_West','Wipe down Sink'],
    ['SB','RR_Women_West','Wipe down Toilets'],
    ['SB','Stairs','Vacuum Stairs'],
    ['NB','Backstage','Empty Trash Large'],['NB','Backstage','Vacuum Light Traffic'],
    ['NB','Backstage','Wipe down Tables'],
    ["NB","Children's_Lobby",'Vacuum Light Traffic'],["NB","Children's_Lobby",'Vacuum Stairs'],
    ['NB','Fireside_Room','Spot Clean Glass'],
    ['NB','Gym','Breakdown Sunday'],['NB','Gym','Empty Trash Large'],['NB','Gym','Mop'],
    ['NB','Gym','Sweep'],['NB','Gym','Vacuum Rugs'],['NB','Gym','Vacuum Stairs'],
    ['NB','Hallway_Downstairs','Clean Carpets'],
    ['NB','Hallway_Upstairs','Clean Carpets'],['NB','Hallway_Upstairs','Spot Clean Glass'],
    ['NB','Lobby','Spot Clean Door Glass'],['NB','Lobby','Sweep Stairs'],
    ['NB','Lobby','Vacuum High Traffic'],
    ['NB','Office_Downstairs','Empty Trash Small'],['NB','Office_Downstairs','Vacuum Light Traffic'],
    ['NB','Office_Upstairs','Empty Trash Small'],['NB','Office_Upstairs','Vacuum Light Traffic'],
    ['NB','Sanctuary','Clean Carpets'],['NB','Sanctuary','Pickup Trash'],
    ['NB','Sanctuary','Spot Clean Glass'],['NB','Sanctuary','Straighten Chairs'],
    ['NB','Sanctuary','Vacuum Light Traffic'],['NB','Sanctuary','Vacuum Stairs'],
    ['NB','Sanctuary','Wipe down Stair Rails'],
    ['NB','Stage','Vacuum Light Traffic'],
    ['NB','Cafe','Empty Trash Large'],['NB','Cafe','Mop'],['NB','Cafe','Scrub Sinks'],
    ['NB','Cafe','Stock Kitchen Supplies'],['NB','Cafe','Sweep'],
    ['NB','Cafe','Wipe down Counters'],['NB','Cafe','Wipe down Sink'],
    ['NB','Cafe','Wipe down Tables'],
    ["NB","Children's_Lobby",'Empty Trash Small'],["NB","Children's_Lobby",'Wipe down Stair Rails'],
    ["NB","Children's_Lobby",'Wipe down Tables'],
    ['NB','Coffee_Prep','Empty Trash Large'],['NB','Coffee_Prep','Mop'],['NB','Coffee_Prep','Sweep'],
    ['NB','Counseling_Room','Empty Trash Small'],['NB','Counseling_Room','Vacuum Light Traffic'],
    ['NB','Counseling_Room','Wipe down Tables'],
    ['NB','CR_201','Empty Trash Small'],['NB','CR_201','Vacuum Light Traffic'],['NB','CR_201','Wipe down Tables'],
    ['NB','CR_202','Empty Trash Small'],['NB','CR_202','Vacuum Light Traffic'],['NB','CR_202','Wipe down Tables'],
    ['NB','CR_203','Empty Trash Small'],['NB','CR_203','Vacuum Light Traffic'],['NB','CR_203','Wipe down Tables'],
    ['NB','CR_204','Empty Trash Small'],['NB','CR_204','Vacuum Light Traffic'],['NB','CR_204','Wipe down Tables'],
    ['NB','CR_Preschool_East','Empty Trash Small'],['NB','CR_Preschool_East','Vacuum Light Traffic'],
    ['NB','CR_Preschool_East','Wipe down Counters'],
    ['NB','CR_Preschool_West','Empty Trash Small'],['NB','CR_Preschool_West','Vacuum Light Traffic'],
    ['NB','CR_Preschool_West','Wipe down Counters'],
    ['NB','Elevator','Vacuum Light Traffic'],
    ['NB','Family_Room','Disinfect Changing Table'],['NB','Family_Room','Empty Diaper Genie'],
    ['NB','Family_Room','Empty Trash Small'],['NB','Family_Room','Vacuum Light Traffic'],
    ['NB','Fireside_Room','Empty Trash Large'],['NB','Fireside_Room','Vacuum Light Traffic'],
    ['NB','Fireside_Room','Wipe down Tables'],
    ['NB','Gym','Disinfect Water Fountain'],['NB','Gym','Wipe down Stair Rails'],
    ['NB','Hallway_Downstairs','Disinfect Water Fountain'],['NB','Hallway_Downstairs','Vacuum Light Traffic'],
    ['NB','Hallway_Upstairs','Disinfect Water Fountain'],['NB','Hallway_Upstairs','Vacuum Light Traffic'],
    ['NB','Kitchen','Empty Trash Large'],['NB','Kitchen','Mop'],['NB','Kitchen','Scrub Sinks'],
    ['NB','Kitchen','Stock Kitchen Supplies'],['NB','Kitchen','Sweep'],['NB','Kitchen','Wash Towels'],
    ['NB','Lobby','Wipe down Stair Rails'],
    ['NB','Nursery_Lobby','Empty Diaper Genie'],['NB','Nursery_Lobby','Empty Trash Small'],
    ['NB','Nursery_Lobby','Vacuum Light Traffic'],['NB','Nursery_Lobby','Wipe down Stair Rails'],
    ['NB','Nursery_Main','Vacuum Light Traffic'],['NB','Nursery_Main','Wipe down Counters'],
    ['NB','Prayer_Rooms','Empty Trash Small'],['NB','Prayer_Rooms','Vacuum Light Traffic'],
    ['NB','Prayer_Rooms','Wipe down Tables'],
    ['NB','Preschool_Lobby','Empty Trash Small'],['NB','Preschool_Lobby','Vacuum Light Traffic'],
    ['NB','Preschool_Lobby','Wipe down Counters'],
    ['NB','Preschool_Main','Vacuum Light Traffic'],
    ['NB','RR_Men_Downstairs_North','Empty Trash Bathroom'],['NB','RR_Men_Downstairs_North','Mop'],
    ['NB','RR_Men_Downstairs_North','Scrub Sinks'],['NB','RR_Men_Downstairs_North','Scrub Toilets'],
    ['NB','RR_Men_Downstairs_North','Scrub Urinals'],['NB','RR_Men_Downstairs_North','Spot Clean Mirrors'],
    ['NB','RR_Men_Downstairs_North','Stock Bathroom Supplies'],['NB','RR_Men_Downstairs_North','Sweep'],
    ['NB','RR_Men_Downstairs_North','Wipe down Counters'],['NB','RR_Men_Downstairs_North','Wipe down Sink'],
    ['NB','RR_Men_Downstairs_North','Wipe down Toilets'],['NB','RR_Men_Downstairs_North','Wipe down Urinals'],
    ['NB','RR_Men_Downstairs_South','Empty Trash Bathroom'],['NB','RR_Men_Downstairs_South','Mop'],
    ['NB','RR_Men_Downstairs_South','Scrub Sinks'],['NB','RR_Men_Downstairs_South','Scrub Toilets'],
    ['NB','RR_Men_Downstairs_South','Scrub Urinals'],['NB','RR_Men_Downstairs_South','Spot Clean Mirrors'],
    ['NB','RR_Men_Downstairs_South','Stock Bathroom Supplies'],['NB','RR_Men_Downstairs_South','Sweep'],
    ['NB','RR_Men_Downstairs_South','Wipe down Counters'],['NB','RR_Men_Downstairs_South','Wipe down Sink'],
    ['NB','RR_Men_Downstairs_South','Wipe down Toilets'],['NB','RR_Men_Downstairs_South','Wipe down Urinals'],
    ['NB','RR_Men_Upstairs','Disinfect Changing Table'],['NB','RR_Men_Upstairs','Empty Diaper Genie'],
    ['NB','RR_Men_Upstairs','Empty Trash Bathroom'],['NB','RR_Men_Upstairs','Mop'],
    ['NB','RR_Men_Upstairs','Scrub Sinks'],['NB','RR_Men_Upstairs','Scrub Toilets'],
    ['NB','RR_Men_Upstairs','Scrub Urinals'],['NB','RR_Men_Upstairs','Spot Clean Mirrors'],
    ['NB','RR_Men_Upstairs','Stock Bathroom Supplies'],['NB','RR_Men_Upstairs','Sweep'],
    ['NB','RR_Men_Upstairs','Wipe down Counters'],['NB','RR_Men_Upstairs','Wipe down Sink'],
    ['NB','RR_Men_Upstairs','Wipe down Toilets'],['NB','RR_Men_Upstairs','Wipe down Urinals'],
    ['NB','RR_Nursery','Empty Trash Small'],['NB','RR_Nursery','Mop'],
    ['NB','RR_Nursery','Scrub Sinks'],['NB','RR_Nursery','Scrub Toilets'],
    ['NB','RR_Nursery','Spot Clean Mirrors'],['NB','RR_Nursery','Stock Bathroom Supplies'],
    ['NB','RR_Nursery','Sweep'],['NB','RR_Nursery','Wipe down Counters'],
    ['NB','RR_Nursery','Wipe down Sink'],['NB','RR_Nursery','Wipe down Toilets'],
    ['NB','RR_Preschool','Empty Trash Small'],['NB','RR_Preschool','Mop'],
    ['NB','RR_Preschool','Scrub Sinks'],['NB','RR_Preschool','Scrub Toilets'],
    ['NB','RR_Preschool','Spot Clean Mirrors'],['NB','RR_Preschool','Stock Bathroom Supplies'],
    ['NB','RR_Preschool','Sweep'],['NB','RR_Preschool','Wipe down Counters'],
    ['NB','RR_Preschool','Wipe down Sink'],['NB','RR_Preschool','Wipe down Toilets'],
    ['NB','RR_Women_Downstairs_North','Empty Trash Bathroom'],['NB','RR_Women_Downstairs_North','Mop'],
    ['NB','RR_Women_Downstairs_North','Scrub Sinks'],['NB','RR_Women_Downstairs_North','Scrub Toilets'],
    ['NB','RR_Women_Downstairs_North','Spot Clean Mirrors'],
    ['NB','RR_Women_Downstairs_North','Stock Bathroom Supplies'],['NB','RR_Women_Downstairs_North','Sweep'],
    ['NB','RR_Women_Downstairs_North','Wipe down Counters'],['NB','RR_Women_Downstairs_North','Wipe down Sink'],
    ['NB','RR_Women_Downstairs_North','Wipe down Toilets'],
    ['NB','RR_Women_Downstairs_South','Empty Trash Bathroom'],['NB','RR_Women_Downstairs_South','Mop'],
    ['NB','RR_Women_Downstairs_South','Scrub Sinks'],['NB','RR_Women_Downstairs_South','Scrub Toilets'],
    ['NB','RR_Women_Downstairs_South','Spot Clean Mirrors'],
    ['NB','RR_Women_Downstairs_South','Stock Bathroom Supplies'],['NB','RR_Women_Downstairs_South','Sweep'],
    ['NB','RR_Women_Downstairs_South','Wipe down Counters'],['NB','RR_Women_Downstairs_South','Wipe down Sink'],
    ['NB','RR_Women_Downstairs_South','Wipe down Toilets'],
    ['NB','RR_Women_Upstairs','Disinfect Changing Table'],
    ['NB','RR_Women_Upstairs','Empty Trash Bathroom'],['NB','RR_Women_Upstairs','Mop'],
    ['NB','RR_Women_Upstairs','Scrub Sinks'],['NB','RR_Women_Upstairs','Scrub Toilets'],
    ['NB','RR_Women_Upstairs','Spot Clean Mirrors'],
    ['NB','RR_Women_Upstairs','Stock Bathroom Supplies'],['NB','RR_Women_Upstairs','Sweep'],
    ['NB','RR_Women_Upstairs','Wipe down Counters'],['NB','RR_Women_Upstairs','Wipe down Sink'],
    ['NB','RR_Women_Upstairs','Wipe down Toilets'],
    ['NB','Security_Room','Empty Trash Small'],['NB','Security_Room','Vacuum Light Traffic'],
    ['NB','Showers_Men','Mop'],['NB','Showers_Men','Scrub Showers'],['NB','Showers_Men','Sweep'],
    ['NB','Showers_Women','Mop'],['NB','Showers_Women','Scrub Showers'],['NB','Showers_Women','Sweep'],
    ['NB','Staff_Break_Room','Empty Trash Large'],['NB','Staff_Break_Room','Vacuum Light Traffic'],
    ['NB','Staff_Break_Room','Wipe down Counters'],['NB','Staff_Break_Room','Wipe down Tables'],
    ["NB","Women's_Lounge_Downstairs",'Empty Trash Small'],["NB","Women's_Lounge_Downstairs",'Vacuum Light Traffic'],
    ["NB","Women's_Lounge_Upstairs",'Empty Trash Small'],["NB","Women's_Lounge_Upstairs",'Vacuum Light Traffic'],
    ['SB','Chapel','Empty Trash Large'],['SB','Chapel','Vacuum Light Traffic'],
    ['SB','CR_100','Empty Trash Small'],['SB','CR_100','Vacuum Light Traffic'],['SB','CR_100','Wipe down Tables'],
    ['SB','CR_101','Empty Trash Small'],['SB','CR_101','Vacuum Light Traffic'],
    ['SB','CR_101','Wipe down Counters'],['SB','CR_101','Wipe down Tables'],
    ['SB','CR_102','Empty Trash Small'],['SB','CR_102','Vacuum Light Traffic'],
    ['SB','CR_102','Wipe down Counters'],['SB','CR_102','Wipe down Sink'],['SB','CR_102','Wipe down Tables'],
    ['SB','CR_103','Empty Trash Small'],['SB','CR_103','Vacuum Light Traffic'],
    ['SB','CR_103','Wipe down Counters'],['SB','CR_103','Wipe down Sink'],['SB','CR_103','Wipe down Tables'],
    ['SB','CR_201','Empty Trash Small'],['SB','CR_201','Vacuum Light Traffic'],['SB','CR_201','Wipe down Tables'],
    ['SB','CR_202','Empty Trash Small'],['SB','CR_202','Vacuum Light Traffic'],['SB','CR_202','Wipe down Tables'],
    ['SB','CR_203','Empty Trash Small'],['SB','CR_203','Vacuum Light Traffic'],['SB','CR_203','Wipe down Tables'],
    ['SB','CR_204','Empty Trash Small'],['SB','CR_204','Vacuum Light Traffic'],['SB','CR_204','Wipe down Tables'],
    ['SB','CR_205','Empty Trash Small'],['SB','CR_205','Vacuum Light Traffic'],
    ['SB','CR_205','Wipe down Counters'],['SB','CR_205','Wipe down Tables'],
    ['SB','Fellowship','Empty Trash Large'],['SB','Fellowship','Vacuum Light Traffic'],
    ['SB','Kitchen','Empty Trash Large'],['SB','Kitchen','Mop'],['SB','Kitchen','Stock Kitchen Supplies'],
    ['SB','Kitchen','Sweep'],['SB','Kitchen','Wash Towels'],
    ['SB','Kitchen','Wipe down Counters'],['SB','Kitchen','Wipe down Sink'],
    ['SB','Lobby','Empty Trash Large'],['SB','Lobby','Vacuum Light Traffic'],
    ['SB','RR_Men_East','Mop'],['SB','RR_Men_East','Scrub Toilets'],
    ['SB','RR_Men_East','Scrub Urinals'],['SB','RR_Men_East','Spot Clean Mirrors'],
    ['SB','RR_Men_West','Mop'],['SB','RR_Men_West','Scrub Toilets'],
    ['SB','RR_Men_West','Scrub Urinals'],['SB','RR_Men_West','Spot Clean Mirrors'],
    ['SB','RR_Women_East','Mop'],['SB','RR_Women_East','Scrub Toilets'],
    ['SB','RR_Women_East','Spot Clean Mirrors'],
    ['SB','RR_Women_West','Mop'],['SB','RR_Women_West','Scrub Toilets'],
    ['SB','RR_Women_West','Spot Clean Mirrors'],
    ['SB','Stage','Vacuum Light Traffic'],
];

// ═══════════════════════════════════════════════════════════════
// EXECUTION
// ═══════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $results = [];
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');

    // Step 1: Add columns to supplies table for detailed product info
    $alter_sqls = [
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS supplier VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS part_number VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS rack INT DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS shelf INT DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS slot INT DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS unit VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS low_stock INT DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS high_stock INT DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS order_qty INT DEFAULT NULL",
        "ALTER TABLE supplies ADD COLUMN IF NOT EXISTS order_unit VARCHAR(50) DEFAULT NULL",
    ];
    foreach ($alter_sqls as $sql) {
        try { $db->exec($sql); } catch (Exception $e) { /* column may already exist */ }
    }
    $results[] = ['step' => 'Add supply detail columns', 'status' => 'ok', 'detail' => '10 columns added/verified'];

    // Step 2: Clear all existing data
    $clear_tables = [
        'janitor_task_checklist','janitor_task_assignments',
        'cleaning_schedule_tasks','cleaning_schedule_task_groups','cleaning_schedule_rooms','cleaning_schedules',
        'task_tools','task_supplies','task_materials','task_equipment',
        'task_preferred_workers','task_rooms','task_group_preferred_workers','room_default_task_groups',
        'task_group_tasks','task_groups','tasks','task_types',
        'room_equipment','equipment_catalog','supplies','tools','materials',
    ];
    $cleared = 0;
    foreach ($clear_tables as $t) {
        try {
            $db->query("SHOW TABLES LIKE '$t'")->rowCount() > 0 && $db->exec("DELETE FROM `$t`") !== false;
            $db->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
            $cleared++;
        } catch (Exception $e) { /* table may not exist */ }
    }
    $results[] = ['step' => 'Clear existing data', 'status' => 'ok', 'detail' => "$cleared tables cleared"];

    // Step 3: Insert task type
    $db->prepare("INSERT INTO task_types (name, priority_order) VALUES (?, ?)")->execute([$task_type, 1]);
    $typeId = (int)$db->lastInsertId();
    $results[] = ['step' => 'Insert task type', 'status' => 'ok', 'detail' => "Cleaning (id=$typeId)"];

    // Step 4: Insert tasks
    $taskIds = [];
    $stTask = $db->prepare("INSERT INTO tasks (name, description, task_type_id, estimated_minutes, reusable) VALUES (?,?,?,5,1)");
    foreach ($tasks as $name => $desc) {
        $stTask->execute([$name, $desc, $typeId]);
        $taskIds[$name] = (int)$db->lastInsertId();
    }
    $results[] = ['step' => 'Insert tasks', 'status' => 'ok', 'detail' => count($taskIds) . ' tasks'];

    // Step 5: Insert supplies with full product details
    $supplyIds = [];
    $stSup = $db->prepare("INSERT INTO supplies (name, quantity, supplier, part_number, rack, shelf, slot, unit, low_stock, high_stock, order_qty, order_unit) VALUES (?,1,?,?,?,?,?,?,?,?,?,?)");
    foreach ($supplies_data as $s) {
        $stSup->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10]]);
        $supplyIds[$s[0]] = (int)$db->lastInsertId();
    }
    $results[] = ['step' => 'Insert supplies', 'status' => 'ok', 'detail' => count($supplyIds) . ' supplies with product details'];

    // Step 6: Insert tools
    $toolIds = [];
    $stTool = $db->prepare("INSERT INTO tools (name, quantity) VALUES (?,1)");
    foreach ($tools_data as $tname) {
        $stTool->execute([$tname]);
        $toolIds[$tname] = (int)$db->lastInsertId();
    }
    $results[] = ['step' => 'Insert tools', 'status' => 'ok', 'detail' => count($toolIds) . ' tools'];

    // Step 7: Link tasks to supplies and tools
    $stTS = $db->prepare("INSERT IGNORE INTO task_supplies (task_id, supply_id) VALUES (?,?)");
    $stTT = $db->prepare("INSERT IGNORE INTO task_tools (task_id, tool_id) VALUES (?,?)");
    $supLinks = 0; $toolLinks = 0;
    foreach ($task_supply_links as $taskName => $links) {
        $tid = $taskIds[$taskName] ?? null;
        if (!$tid) continue;
        foreach ($links['s'] as $genName) {
            $realName = $supply_name_map[$genName] ?? null;
            if ($realName && isset($supplyIds[$realName])) {
                $stTS->execute([$tid, $supplyIds[$realName]]);
                $supLinks++;
            }
        }
        foreach ($links['t'] as $toolName) {
            if (isset($toolIds[$toolName])) {
                $stTT->execute([$tid, $toolIds[$toolName]]);
                $toolLinks++;
            }
        }
    }
    $results[] = ['step' => 'Link tasks to supplies/tools', 'status' => 'ok', 'detail' => "$supLinks supply links, $toolLinks tool links"];

    // Step 8: Create task_rooms from weekly assignments
    $stTR = $db->prepare("INSERT IGNORE INTO task_rooms (task_id, room_id) VALUES (?,?)");
    $roomLinks = 0; $skipped = 0;
    $seen = [];
    foreach ($weekly_assignments as $a) {
        $key = $a[0] . '|' . $a[1];
        $tid = $taskIds[$a[2]] ?? null;
        $roomIds = $room_map[$key] ?? null;
        if (!$tid || !$roomIds) { $skipped++; continue; }
        foreach ($roomIds as $rid) {
            $pairKey = "$tid|$rid";
            if (isset($seen[$pairKey])) continue;
            $seen[$pairKey] = true;
            $stTR->execute([$tid, $rid]);
            $roomLinks++;
        }
    }
    $results[] = ['step' => 'Create task-room assignments', 'status' => 'ok', 'detail' => "$roomLinks room links ($skipped skipped)"];

    $db->exec('SET FOREIGN_KEY_CHECKS = 1');

    // Show results
    ?>
    <!DOCTYPE html><html><head><title>Import Results</title>
    <script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-gray-50 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm p-6 border">
        <h1 class="text-xl font-bold mb-4 text-green-700">Import Complete</h1>
        <table class="w-full text-sm">
            <tr class="border-b"><th class="text-left py-2">Step</th><th class="text-left py-2">Status</th><th class="text-left py-2">Details</th></tr>
            <?php foreach ($results as $r): ?>
            <tr class="border-b">
                <td class="py-2 font-medium"><?= htmlspecialchars($r['step']) ?></td>
                <td class="py-2"><span class="text-green-600 font-bold">&#10003;</span></td>
                <td class="py-2 text-gray-500 text-xs"><?= htmlspecialchars($r['detail']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="mt-6 flex gap-4">
            <a href="/pages/tasks.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition">View Tasks</a>
            <a href="/pages/catalog.php" class="border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition">View Supplies</a>
        </div>
    </div>
    </body></html>
    <?php
    exit;
}
?>
<!DOCTYPE html><html><head><title>Import Janitorial Data</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-8">
<div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm p-6 border">
    <h1 class="text-xl font-bold mb-2">Import Janitorial Data</h1>
    <p class="text-sm text-gray-500 mb-4">This will clear all existing task/resource data and import from your Janitorial spreadsheet.</p>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 text-sm">
        <p class="font-bold text-blue-800 mb-2">What will be imported:</p>
        <ul class="text-blue-700 space-y-1">
            <li>&#8226; <strong>33 tasks</strong> with instructions (Cleaning type)</li>
            <li>&#8226; <strong>31 supplies</strong> with full product details (supplier, part#, rack/shelf/slot, stock levels)</li>
            <li>&#8226; <strong>9 tools</strong></li>
            <li>&#8226; Task-supply and task-tool links</li>
            <li>&#8226; Task-room assignments from weekly schedule (~300 links)</li>
        </ul>
    </div>

    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
        <p class="text-red-700 font-bold text-sm">&#9888; This will clear ALL existing task, supply, tool, equipment, and schedule data first!</p>
    </div>

    <form method="POST">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-2 text-sm transition">
            Import Data
        </button>
        <a href="/dashboard.php" class="ml-4 text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
    </form>
</div>
</body></html>
