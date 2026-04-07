<?php
/**
 * Migration: Import Cleaning Schedules from Todoist Export
 *
 * Creates cleaning_schedules linked to rooms and tasks.
 * All schedules assigned to the 'custodial' role.
 * Uses existing tasks (no new tasks created).
 * Does NOT set any room defaults.
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { echo 'Admin access required.'; exit; }
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// ─── Room Mapping: Todoist shorthand → room ID(s) ───────────────
$roomMap = [
    // South Building - Hallways & Stairs
    'SB_Hallway_Downstairs'       => [189],  // Corridor A, SB 1st
    'SB_Hallway_Upstairs'         => [210],  // Corridor B, SB 2nd
    'SB_Stairs'                   => [187],  // Stair 1, SB

    // South Building - Bathrooms
    'SB_RR_Men_East'              => [180],  // Men's East, SB
    'SB_RR_Men_West'              => [192],  // Men's West, SB
    'SB_RR_Women_East'            => [181],  // Women's East, SB
    'SB_RR_Women_West'            => [191],  // Women's West, SB

    // South Building - Rooms
    'SB_Chapel'                   => [170],  // Chapel, SB
    'SB_Fellowship'               => [177],  // Fellowship, SB
    'SB_Kitchen'                  => [182],  // Kitchen, SB
    'SB_Lobby'                    => [176],  // Lounge, SB
    'SB_Stage'                    => [217],  // Stage, SB

    // South Building - Classrooms 1st Floor
    'SB_CR_100'                   => [179],  // 100, SB 1st
    'SB_CR_101'                   => [185],  // 101, SB 1st
    'SB_CR_102'                   => [193],  // 102, SB 1st
    'SB_CR_103'                   => [194],  // 103, SB 1st
    'SB_CR_104'                   => [204],  // 205, SB 2nd (renumbered)

    // South Building - Classrooms 2nd Floor
    'SB_CR_201'                   => [211],  // 201, SB 2nd
    'SB_CR_202'                   => [205],  // 202, SB 2nd
    'SB_CR_203'                   => [203],  // 203, SB 2nd
    'SB_CR_204'                   => [202],  // 204, SB 2nd
    'SB_CR_205'                   => [204],  // 205, SB 2nd

    // North Building - Main Spaces
    'NB_Backstage'                => [93],   // Green Room
    'NB_Gym'                      => [2],    // Gymnasium
    'NB_Sanctuary'                => [66],   // Sanctuary
    'NB_Stage'                    => [168],  // Stage, NB
    'NB_Lobby'                    => [110],  // Lobby, NB 1st

    // North Building - Hallways & Corridors
    'NB_Hallway_Downstairs'       => [90],   // Corridor A, NB 1st
    'NB_Hallway_Upstairs'         => [215],  // Corridor D, NB 2nd
    'NB_Office_Downstairs'        => [103],  // Office Corridor Down
    'NB_Office_Upstairs'          => [118],  // Office Corridor U

    // North Building - Kitchen & Cafe
    'NB_Cafe'                     => [122],  // Fireside Cafe, NB 2nd
    'NB_Coffee_Prep'              => [62],   // Coffee, NB 1st
    'NB_Kitchen'                  => [61],   // Kitchen, NB 1st

    // North Building - Children's Areas
    'NB_Childrens_Lobby'          => [88],   // Children's Lobby, NB 1st
    'NB_Nursery'                  => [67],   // Nursery Main (Nursery Group)
    'NB_Family_Room'              => [74],   // Family Room
    'NB_Preschool_Lobby'          => [79],   // Preschool Checkin
    'NB_Preschool_Main'           => [85],   // Preschool Main
    'NB_CR_Preschool_East'        => [84],   // Preschool East
    'NB_CR_Preschool_West'        => [83],   // Preschool West

    // North Building - Classrooms 2nd Floor
    'NB_CR_201'                   => [131],  // 201, NB 2nd
    'NB_CR_202'                   => [132],  // 202, NB 2nd
    'NB_CR_203'                   => [125],  // 203, NB 2nd
    'NB_CR_204'                   => [123],  // 204, NB 2nd

    // North Building - Other Rooms
    'NB_Counseling_Room'          => [56],   // Counseling Room
    'NB_Elevator'                 => [77],   // Elevator, NB 1st
    'NB_Fireside_Room'            => [119],  // Fireside Room, NB 2nd
    'NB_Prayer_Rooms'             => [82, 81], // Prayer Room East + West
    'NB_Security_Room'            => [58],   // CommOpps
    'NB_Staff_Break_Room'         => [57],   // Break Room
    'NB_Womens_Lounge_Down'       => [68],   // Women's Lounge Down
    'NB_Womens_Lounge_Up'         => [136],  // Women's Lounge Up

    // North Building - Bathrooms Downstairs
    'NB_RR_Men_Down_North'        => [72],   // Men's North, NB 1st
    'NB_RR_Men_Down_South'        => [59],   // Men's South, NB 1st
    'NB_RR_Women_Down_North'      => [71],   // Women's North, NB 1st
    'NB_RR_Women_Down_South'      => [60],   // Women's South, NB 1st

    // North Building - Bathrooms Upstairs
    'NB_RR_Men_Up'                => [128],  // Men's Up, NB 2nd
    'NB_RR_Women_Up'              => [135],  // Women's Up, NB 2nd
    'NB_RR_Nursery'               => [73],   // Nursery Restroom
    'NB_RR_Preschool'             => [80],   // Preschool Restroom

    // North Building - Showers
    'NB_Showers_Men'              => [126, 127], // Men's Shower 1 + 2
    'NB_Showers_Women'            => [133, 134], // Women's Shower 1 + 2
];

// ─── Task Mapping: task name → task ID ───────────────────────────
$taskMap = [
    'Breakdown Sunday'          => 1,
    'Clean Carpets'             => 2,
    'Disinfect Changing Table'  => 3,
    'Disinfect Water Fountain'  => 4,
    'Empty Diaper Genie'        => 5,
    'Empty Trash Bathroom'      => 6,
    'Empty Trash Large'         => 7,
    'Empty Trash Small'         => 8,
    'Mop'                       => 9,
    'Pickup Trash'              => 10,
    'Scrub Showers'             => 11,
    'Scrub Sinks'               => 12,
    'Scrub Toilets'             => 13,
    'Scrub Urinals'             => 14,
    'Spot Clean Door Glass'     => 15,
    'Spot Clean Glass'          => 16,
    'Spot Clean Mirrors'        => 17,
    'Stock Bathroom Supplies'   => 18,
    'Stock Kitchen Supplies'    => 19,
    'Straighten Chairs'         => 20,
    'Sweep'                     => 21,
    'Sweep Stairs'              => 22,
    'Vacuum Light Traffic'      => 23,
    'Vacuum High Traffic'       => 24,
    'Vacuum Rugs'               => 25,
    'Vacuum Stairs'             => 26,
    'Wash Towels'               => 27,
    'Wipe down Counters'        => 28,
    'Wipe down Sink'            => 29,
    'Wipe down Stair Rails'     => 30,
    'Wipe down Tables'          => 31,
    'Wipe down Toilets'         => 32,
    'Wipe down Urinals'         => 33,
];

// Day constants (ISO: 1=Mon … 5=Fri)
define('MON', 1); define('TUE', 2); define('WED', 3); define('THU', 4); define('FRI', 5);
$WEEKDAYS = [MON, TUE, WED, THU, FRI];

// ─── Raw Assignments: [room_key, task_name, [days]] ──────────────
// Each row = one line from the Todoist export
$assignments = [
    // ════════ SOUTH BUILDING - Garrett's weekday tasks ════════

    // SB Hallways & Stairs (M-F)
    ['SB_Hallway_Downstairs', 'Vacuum Light Traffic', $WEEKDAYS],
    ['SB_Hallway_Upstairs',   'Vacuum Light Traffic', $WEEKDAYS],
    ['SB_Stairs',             'Vacuum Stairs',        $WEEKDAYS],

    // SB Men's East (M-F)
    ['SB_RR_Men_East', 'Empty Trash Bathroom',    $WEEKDAYS],
    ['SB_RR_Men_East', 'Stock Bathroom Supplies',  $WEEKDAYS],
    ['SB_RR_Men_East', 'Sweep',                    $WEEKDAYS],
    ['SB_RR_Men_East', 'Wipe down Sink',           $WEEKDAYS],
    ['SB_RR_Men_East', 'Wipe down Toilets',        $WEEKDAYS],
    ['SB_RR_Men_East', 'Wipe down Urinals',        $WEEKDAYS],

    // SB Men's West (M-F)
    ['SB_RR_Men_West', 'Empty Trash Bathroom',    $WEEKDAYS],
    ['SB_RR_Men_West', 'Stock Bathroom Supplies',  $WEEKDAYS],
    ['SB_RR_Men_West', 'Sweep',                    $WEEKDAYS],
    ['SB_RR_Men_West', 'Wipe down Sink',           $WEEKDAYS],
    ['SB_RR_Men_West', 'Wipe down Toilets',        $WEEKDAYS],
    ['SB_RR_Men_West', 'Wipe down Urinals',        $WEEKDAYS],

    // SB Women's East (M-F)
    ['SB_RR_Women_East', 'Empty Trash Bathroom',    $WEEKDAYS],
    ['SB_RR_Women_East', 'Stock Bathroom Supplies',  $WEEKDAYS],
    ['SB_RR_Women_East', 'Sweep',                    $WEEKDAYS],
    ['SB_RR_Women_East', 'Wipe down Sink',           $WEEKDAYS],
    ['SB_RR_Women_East', 'Wipe down Toilets',        $WEEKDAYS],

    // SB Women's West (M-F)
    ['SB_RR_Women_West', 'Empty Trash Bathroom',    $WEEKDAYS],
    ['SB_RR_Women_West', 'Stock Bathroom Supplies',  $WEEKDAYS],
    ['SB_RR_Women_West', 'Sweep',                    $WEEKDAYS],
    ['SB_RR_Women_West', 'Wipe down Sink',           $WEEKDAYS],
    ['SB_RR_Women_West', 'Wipe down Toilets',        $WEEKDAYS],

    // ════════ SOUTH BUILDING - Josh's deep clean + other rooms ════════

    // SB Bathrooms deep clean (Fri)
    ['SB_RR_Men_East',   'Mop',                [FRI]],
    ['SB_RR_Men_East',   'Scrub Toilets',      [FRI]],
    ['SB_RR_Men_East',   'Scrub Urinals',      [FRI]],
    ['SB_RR_Men_East',   'Spot Clean Mirrors',  [FRI]],
    ['SB_RR_Men_West',   'Mop',                [FRI]],
    ['SB_RR_Men_West',   'Scrub Toilets',      [FRI]],
    ['SB_RR_Men_West',   'Scrub Urinals',      [FRI]],
    ['SB_RR_Men_West',   'Spot Clean Mirrors',  [FRI]],
    ['SB_RR_Women_East', 'Mop',                [FRI]],
    ['SB_RR_Women_East', 'Scrub Toilets',      [FRI]],
    ['SB_RR_Women_East', 'Spot Clean Mirrors',  [FRI]],
    ['SB_RR_Women_West', 'Mop',                [FRI]],
    ['SB_RR_Women_West', 'Scrub Toilets',      [FRI]],
    ['SB_RR_Women_West', 'Spot Clean Mirrors',  [FRI]],

    // SB Chapel (Fri)
    ['SB_Chapel', 'Empty Trash Large',     [FRI]],
    ['SB_Chapel', 'Vacuum Light Traffic',  [FRI]],

    // SB Fellowship
    ['SB_Fellowship', 'Empty Trash Large',     [THU]],
    ['SB_Fellowship', 'Vacuum Light Traffic',  [FRI]],

    // SB Kitchen (Wed)
    ['SB_Kitchen', 'Empty Trash Large',      [WED]],
    ['SB_Kitchen', 'Mop',                    [WED]],
    ['SB_Kitchen', 'Stock Kitchen Supplies',  [WED]],
    ['SB_Kitchen', 'Sweep',                  [WED]],
    ['SB_Kitchen', 'Wash Towels',            [WED]],
    ['SB_Kitchen', 'Wipe down Counters',     [WED]],
    ['SB_Kitchen', 'Wipe down Sink',         [WED]],

    // SB Lobby (Wed)
    ['SB_Lobby', 'Empty Trash Large',     [WED]],
    ['SB_Lobby', 'Vacuum Light Traffic',  [WED]],

    // SB Stage (Wed)
    ['SB_Stage', 'Vacuum Light Traffic', [WED]],

    // SB Classrooms 1st Floor
    ['SB_CR_100', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_100', 'Wipe down Tables',    [TUE]],
    ['SB_CR_100', 'Empty Trash Small',   [THU]],

    ['SB_CR_101', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_101', 'Empty Trash Small',   [TUE]],
    ['SB_CR_101', 'Wipe down Counters',  [TUE]],
    ['SB_CR_101', 'Wipe down Tables',    [TUE]],

    ['SB_CR_102', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_102', 'Wipe down Counters',  [TUE]],
    ['SB_CR_102', 'Wipe down Sink',      [TUE]],
    ['SB_CR_102', 'Wipe down Tables',    [TUE]],
    ['SB_CR_102', 'Empty Trash Small',   [THU]],

    ['SB_CR_103', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_103', 'Wipe down Counters',  [TUE]],
    ['SB_CR_103', 'Wipe down Sink',      [TUE]],
    ['SB_CR_103', 'Wipe down Tables',    [TUE]],
    ['SB_CR_103', 'Empty Trash Small',   [THU]],

    ['SB_CR_104', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_104', 'Wipe down Tables',    [TUE]],
    ['SB_CR_104', 'Empty Trash Small',   [THU]],

    // SB Classrooms 2nd Floor
    ['SB_CR_201', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_201', 'Wipe down Tables',    [WED]],
    ['SB_CR_201', 'Empty Trash Small',   [THU]],

    ['SB_CR_202', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_202', 'Wipe down Tables',    [WED]],
    ['SB_CR_202', 'Empty Trash Small',   [THU]],

    ['SB_CR_203', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_203', 'Wipe down Tables',    [WED]],
    ['SB_CR_203', 'Empty Trash Small',   [THU]],

    ['SB_CR_204', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_204', 'Wipe down Tables',    [WED]],
    ['SB_CR_204', 'Empty Trash Small',   [THU]],

    ['SB_CR_205', 'Vacuum Light Traffic', [MON]],
    ['SB_CR_205', 'Wipe down Counters',  [WED]],
    ['SB_CR_205', 'Wipe down Tables',    [WED]],
    ['SB_CR_205', 'Empty Trash Small',   [THU]],

    // ════════ NORTH BUILDING - Garrett's tasks ════════

    // NB Sanctuary
    ['NB_Sanctuary', 'Clean Carpets',          [MON]],
    ['NB_Sanctuary', 'Pickup Trash',           [MON]],
    ['NB_Sanctuary', 'Spot Clean Glass',       [MON]],
    ['NB_Sanctuary', 'Straighten Chairs',      [MON]],
    ['NB_Sanctuary', 'Vacuum Light Traffic',   [MON]],
    ['NB_Sanctuary', 'Vacuum Stairs',          [MON]],
    ['NB_Sanctuary', 'Wipe down Stair Rails',  [FRI]],

    // NB Gym (Garrett)
    ['NB_Gym', 'Breakdown Sunday',    [MON]],
    ['NB_Gym', 'Empty Trash Large',   [MON, FRI]],
    ['NB_Gym', 'Sweep',               [MON, FRI]],
    ['NB_Gym', 'Mop',                 [FRI]],
    ['NB_Gym', 'Vacuum Rugs',         [FRI]],
    ['NB_Gym', 'Vacuum Stairs',       [FRI]],

    // NB Hallway Downstairs (Garrett)
    ['NB_Hallway_Downstairs', 'Clean Carpets', [MON]],

    // NB Hallway Upstairs (Garrett)
    ['NB_Hallway_Upstairs', 'Clean Carpets',    [FRI]],
    ['NB_Hallway_Upstairs', 'Spot Clean Glass',  [FRI]],

    // NB Lobby (Garrett)
    ['NB_Lobby', 'Spot Clean Door Glass',  [FRI]],
    ['NB_Lobby', 'Sweep Stairs',           [MON]],
    ['NB_Lobby', 'Vacuum High Traffic',    [MON, FRI]],

    // NB Children's Lobby (Garrett)
    ['NB_Childrens_Lobby', 'Vacuum Light Traffic', [FRI]],
    ['NB_Childrens_Lobby', 'Vacuum Stairs',        [FRI]],

    // NB Fireside Room (Garrett)
    ['NB_Fireside_Room', 'Spot Clean Glass', [FRI]],

    // NB Office areas (Garrett)
    ['NB_Office_Downstairs', 'Empty Trash Small',    [FRI]],
    ['NB_Office_Downstairs', 'Vacuum Light Traffic',  [FRI]],
    ['NB_Office_Upstairs',   'Empty Trash Small',    [FRI]],
    ['NB_Office_Upstairs',   'Vacuum Light Traffic',  [FRI]],

    // NB Stage (Garrett)
    ['NB_Stage', 'Vacuum Light Traffic', [FRI]],

    // ════════ NORTH BUILDING - Josh's tasks ════════

    // NB Backstage / Green Room (Mon)
    ['NB_Backstage', 'Empty Trash Large',     [MON]],
    ['NB_Backstage', 'Vacuum Light Traffic',  [MON]],
    ['NB_Backstage', 'Wipe down Tables',      [MON]],

    // NB Cafe / Fireside Cafe (Mon)
    ['NB_Cafe', 'Empty Trash Large',      [MON]],
    ['NB_Cafe', 'Mop',                    [MON]],
    ['NB_Cafe', 'Scrub Sinks',            [MON]],
    ['NB_Cafe', 'Stock Kitchen Supplies',  [MON]],
    ['NB_Cafe', 'Sweep',                  [MON]],
    ['NB_Cafe', 'Wipe down Counters',     [MON]],
    ['NB_Cafe', 'Wipe down Sink',         [MON]],
    ['NB_Cafe', 'Wipe down Tables',       [MON]],

    // NB Children's Lobby (Josh - Wed)
    ['NB_Childrens_Lobby', 'Empty Trash Small',      [WED]],
    ['NB_Childrens_Lobby', 'Wipe down Stair Rails',  [WED]],
    ['NB_Childrens_Lobby', 'Wipe down Tables',        [WED]],

    // NB Coffee Prep
    ['NB_Coffee_Prep', 'Empty Trash Large', [MON]],
    ['NB_Coffee_Prep', 'Mop',              [TUE]],
    ['NB_Coffee_Prep', 'Sweep',            [TUE]],

    // NB Counseling Room (Tue)
    ['NB_Counseling_Room', 'Empty Trash Small',    [TUE]],
    ['NB_Counseling_Room', 'Vacuum Light Traffic',  [TUE]],
    ['NB_Counseling_Room', 'Wipe down Tables',      [TUE]],

    // NB Classrooms 2nd Floor (Tue)
    ['NB_CR_201', 'Empty Trash Small',    [TUE]],
    ['NB_CR_201', 'Vacuum Light Traffic',  [TUE]],
    ['NB_CR_201', 'Wipe down Tables',      [TUE]],

    ['NB_CR_202', 'Empty Trash Small',    [TUE]],
    ['NB_CR_202', 'Vacuum Light Traffic',  [TUE]],
    ['NB_CR_202', 'Wipe down Tables',      [TUE]],

    ['NB_CR_203', 'Empty Trash Small',    [TUE]],
    ['NB_CR_203', 'Vacuum Light Traffic',  [TUE]],
    ['NB_CR_203', 'Wipe down Tables',      [TUE]],

    ['NB_CR_204', 'Empty Trash Small',    [TUE]],
    ['NB_CR_204', 'Vacuum Light Traffic',  [TUE]],
    ['NB_CR_204', 'Wipe down Tables',      [TUE]],

    // NB Preschool Rooms (Wed)
    ['NB_CR_Preschool_East', 'Empty Trash Small',    [WED]],
    ['NB_CR_Preschool_East', 'Vacuum Light Traffic',  [WED]],
    ['NB_CR_Preschool_East', 'Wipe down Counters',    [WED]],

    ['NB_CR_Preschool_West', 'Empty Trash Small',    [WED]],
    ['NB_CR_Preschool_West', 'Vacuum Light Traffic',  [WED]],
    ['NB_CR_Preschool_West', 'Wipe down Counters',    [WED]],

    // NB Elevator (Thu)
    ['NB_Elevator', 'Vacuum Light Traffic', [THU]],

    // NB Family Room
    ['NB_Family_Room', 'Empty Diaper Genie',         [MON]],
    ['NB_Family_Room', 'Disinfect Changing Table',    [WED]],
    ['NB_Family_Room', 'Empty Trash Small',           [WED]],
    ['NB_Family_Room', 'Vacuum Light Traffic',        [WED]],

    // NB Fireside Room (Josh - Mon)
    ['NB_Fireside_Room', 'Empty Trash Large',     [MON]],
    ['NB_Fireside_Room', 'Vacuum Light Traffic',  [MON]],
    ['NB_Fireside_Room', 'Wipe down Tables',      [MON]],

    // NB Gym (Josh - Thu)
    ['NB_Gym', 'Disinfect Water Fountain',  [THU]],
    ['NB_Gym', 'Wipe down Stair Rails',     [THU]],

    // NB Hallway Downstairs (Josh - Thu)
    ['NB_Hallway_Downstairs', 'Disinfect Water Fountain',  [THU]],
    ['NB_Hallway_Downstairs', 'Vacuum Light Traffic',      [THU]],

    // NB Hallway Upstairs (Josh)
    ['NB_Hallway_Upstairs', 'Vacuum Light Traffic',       [THU]],
    ['NB_Hallway_Upstairs', 'Disinfect Water Fountain',   [FRI]],

    // NB Kitchen (Tue + Wed)
    ['NB_Kitchen', 'Empty Trash Large',      [TUE]],
    ['NB_Kitchen', 'Mop',                    [TUE]],
    ['NB_Kitchen', 'Scrub Sinks',            [TUE]],
    ['NB_Kitchen', 'Stock Kitchen Supplies',  [TUE]],
    ['NB_Kitchen', 'Sweep',                  [TUE]],
    ['NB_Kitchen', 'Wash Towels',            [WED]],

    // NB Lobby (Josh - Fri)
    ['NB_Lobby', 'Wipe down Stair Rails', [FRI]],

    // NB Nursery (combined Lobby + Main → room 67)
    ['NB_Nursery', 'Empty Diaper Genie',     [MON]],
    ['NB_Nursery', 'Empty Trash Small',      [MON, WED]],
    ['NB_Nursery', 'Vacuum Light Traffic',   [MON, WED]],
    ['NB_Nursery', 'Wipe down Counters',     [MON, WED]],
    ['NB_Nursery', 'Wipe down Stair Rails',  [WED]],

    // NB Nursery Lobby (Josh - separate stair rails entry already combined above)
    // NB Nursery Main (Josh - vacuum + counters already combined above)

    // NB Prayer Rooms (Tue)
    ['NB_Prayer_Rooms', 'Empty Trash Small',    [TUE]],
    ['NB_Prayer_Rooms', 'Vacuum Light Traffic',  [TUE]],
    ['NB_Prayer_Rooms', 'Wipe down Tables',      [TUE]],

    // NB Preschool Lobby (Mon+Wed)
    ['NB_Preschool_Lobby', 'Empty Trash Small',    [MON, WED]],
    ['NB_Preschool_Lobby', 'Vacuum Light Traffic',  [MON, WED]],
    ['NB_Preschool_Lobby', 'Wipe down Counters',    [MON, WED]],

    // NB Preschool Main (Mon+Wed)
    ['NB_Preschool_Main', 'Vacuum Light Traffic', [MON, WED]],

    // ──── NB Bathrooms - Downstairs ────

    // Men's North (Josh)
    ['NB_RR_Men_Down_North', 'Empty Trash Bathroom',    [MON, THU]],
    ['NB_RR_Men_Down_North', 'Stock Bathroom Supplies',  [MON, THU]],
    ['NB_RR_Men_Down_North', 'Sweep',                    [MON, THU]],
    ['NB_RR_Men_Down_North', 'Wipe down Counters',       [MON, THU]],
    ['NB_RR_Men_Down_North', 'Wipe down Sink',           [MON, THU]],
    ['NB_RR_Men_Down_North', 'Wipe down Toilets',        [MON, THU]],
    ['NB_RR_Men_Down_North', 'Wipe down Urinals',        [MON, THU]],
    ['NB_RR_Men_Down_North', 'Mop',                      [THU]],
    ['NB_RR_Men_Down_North', 'Scrub Sinks',              [THU]],
    ['NB_RR_Men_Down_North', 'Scrub Toilets',            [THU]],
    ['NB_RR_Men_Down_North', 'Scrub Urinals',            [THU]],
    ['NB_RR_Men_Down_North', 'Spot Clean Mirrors',       [THU]],

    // Men's South (Josh)
    ['NB_RR_Men_Down_South', 'Empty Trash Bathroom',    [MON, WED, THU]],
    ['NB_RR_Men_Down_South', 'Stock Bathroom Supplies',  [MON, WED, THU]],
    ['NB_RR_Men_Down_South', 'Sweep',                    [MON, WED, THU]],
    ['NB_RR_Men_Down_South', 'Wipe down Counters',       [MON, WED, THU]],
    ['NB_RR_Men_Down_South', 'Wipe down Sink',           [MON, WED, THU]],
    ['NB_RR_Men_Down_South', 'Wipe down Toilets',        [MON, WED, THU]],
    ['NB_RR_Men_Down_South', 'Wipe down Urinals',        [MON, WED, THU]],
    ['NB_RR_Men_Down_South', 'Mop',                      [THU]],
    ['NB_RR_Men_Down_South', 'Scrub Sinks',              [THU]],
    ['NB_RR_Men_Down_South', 'Scrub Toilets',            [THU]],
    ['NB_RR_Men_Down_South', 'Scrub Urinals',            [THU]],
    ['NB_RR_Men_Down_South', 'Spot Clean Mirrors',       [THU]],

    // Women's North (Josh)
    ['NB_RR_Women_Down_North', 'Empty Trash Bathroom',    [MON, THU]],
    ['NB_RR_Women_Down_North', 'Stock Bathroom Supplies',  [MON, THU]],
    ['NB_RR_Women_Down_North', 'Sweep',                    [MON, THU]],
    ['NB_RR_Women_Down_North', 'Wipe down Counters',       [MON, THU]],
    ['NB_RR_Women_Down_North', 'Wipe down Sink',           [MON, THU]],
    ['NB_RR_Women_Down_North', 'Wipe down Toilets',        [MON, THU]],
    ['NB_RR_Women_Down_North', 'Mop',                      [THU]],
    ['NB_RR_Women_Down_North', 'Scrub Sinks',              [THU]],
    ['NB_RR_Women_Down_North', 'Scrub Toilets',            [THU]],
    ['NB_RR_Women_Down_North', 'Spot Clean Mirrors',       [THU]],

    // Women's South (Josh)
    ['NB_RR_Women_Down_South', 'Empty Trash Bathroom',    [MON, WED, THU]],
    ['NB_RR_Women_Down_South', 'Stock Bathroom Supplies',  [MON, WED, THU]],
    ['NB_RR_Women_Down_South', 'Sweep',                    [MON, WED, THU]],
    ['NB_RR_Women_Down_South', 'Wipe down Counters',       [MON, WED, THU]],
    ['NB_RR_Women_Down_South', 'Wipe down Sink',           [MON, WED, THU]],
    ['NB_RR_Women_Down_South', 'Wipe down Toilets',        [MON, WED, THU, FRI]],
    ['NB_RR_Women_Down_South', 'Mop',                      [THU]],
    ['NB_RR_Women_Down_South', 'Scrub Sinks',              [THU]],
    ['NB_RR_Women_Down_South', 'Scrub Toilets',            [THU]],
    ['NB_RR_Women_Down_South', 'Spot Clean Mirrors',       [THU]],

    // ──── NB Bathrooms - Upstairs ────

    // Men's Up (Josh)
    ['NB_RR_Men_Up', 'Disinfect Changing Table',  [MON, WED, FRI]],
    ['NB_RR_Men_Up', 'Empty Diaper Genie',        [MON]],
    ['NB_RR_Men_Up', 'Empty Trash Bathroom',       [MON, WED, FRI]],
    ['NB_RR_Men_Up', 'Stock Bathroom Supplies',    [MON, WED, FRI]],
    ['NB_RR_Men_Up', 'Sweep',                      [MON, WED, FRI]],
    ['NB_RR_Men_Up', 'Wipe down Counters',         [MON, WED, FRI]],
    ['NB_RR_Men_Up', 'Wipe down Sink',             [MON, WED, FRI]],
    ['NB_RR_Men_Up', 'Wipe down Toilets',          [MON, WED, FRI]],
    ['NB_RR_Men_Up', 'Wipe down Urinals',          [MON, WED, FRI]],
    ['NB_RR_Men_Up', 'Mop',                        [FRI]],
    ['NB_RR_Men_Up', 'Scrub Sinks',                [FRI]],
    ['NB_RR_Men_Up', 'Scrub Toilets',              [FRI]],
    ['NB_RR_Men_Up', 'Scrub Urinals',              [FRI]],
    ['NB_RR_Men_Up', 'Spot Clean Mirrors',         [FRI]],

    // Women's Up (Josh)
    ['NB_RR_Women_Up', 'Disinfect Changing Table',  [MON, WED, FRI]],
    ['NB_RR_Women_Up', 'Empty Trash Bathroom',       [MON, WED, FRI]],
    ['NB_RR_Women_Up', 'Stock Bathroom Supplies',    [MON, WED, FRI]],
    ['NB_RR_Women_Up', 'Sweep',                      [MON, WED, FRI]],
    ['NB_RR_Women_Up', 'Wipe down Counters',         [MON, WED, FRI]],
    ['NB_RR_Women_Up', 'Wipe down Sink',             [MON, WED, FRI]],
    ['NB_RR_Women_Up', 'Wipe down Toilets',          [MON, WED, FRI]],
    ['NB_RR_Women_Up', 'Mop',                        [FRI]],
    ['NB_RR_Women_Up', 'Scrub Sinks',                [FRI]],
    ['NB_RR_Women_Up', 'Scrub Toilets',              [FRI]],
    ['NB_RR_Women_Up', 'Spot Clean Mirrors',         [FRI]],

    // Nursery Restroom (Fri)
    ['NB_RR_Nursery', 'Empty Trash Small',        [FRI]],
    ['NB_RR_Nursery', 'Mop',                      [FRI]],
    ['NB_RR_Nursery', 'Scrub Sinks',              [FRI]],
    ['NB_RR_Nursery', 'Scrub Toilets',            [FRI]],
    ['NB_RR_Nursery', 'Spot Clean Mirrors',       [FRI]],
    ['NB_RR_Nursery', 'Stock Bathroom Supplies',   [FRI]],
    ['NB_RR_Nursery', 'Sweep',                    [FRI]],
    ['NB_RR_Nursery', 'Wipe down Counters',       [FRI]],
    ['NB_RR_Nursery', 'Wipe down Sink',           [FRI]],
    ['NB_RR_Nursery', 'Wipe down Toilets',        [FRI]],

    // Preschool Restroom (Fri)
    ['NB_RR_Preschool', 'Empty Trash Small',        [FRI]],
    ['NB_RR_Preschool', 'Mop',                      [FRI]],
    ['NB_RR_Preschool', 'Scrub Sinks',              [FRI]],
    ['NB_RR_Preschool', 'Scrub Toilets',            [FRI]],
    ['NB_RR_Preschool', 'Spot Clean Mirrors',       [FRI]],
    ['NB_RR_Preschool', 'Stock Bathroom Supplies',   [FRI]],
    ['NB_RR_Preschool', 'Sweep',                    [FRI]],
    ['NB_RR_Preschool', 'Wipe down Counters',       [FRI]],
    ['NB_RR_Preschool', 'Wipe down Sink',           [FRI]],
    ['NB_RR_Preschool', 'Wipe down Toilets',        [FRI]],

    // ──── NB Other Rooms ────

    // Security / CommOpps (Tue)
    ['NB_Security_Room', 'Empty Trash Small',    [TUE]],
    ['NB_Security_Room', 'Vacuum Light Traffic',  [TUE]],

    // Showers Men (Wed)
    ['NB_Showers_Men', 'Mop',            [WED]],
    ['NB_Showers_Men', 'Scrub Showers',  [WED]],
    ['NB_Showers_Men', 'Sweep',          [WED]],

    // Showers Women (Wed)
    ['NB_Showers_Women', 'Mop',            [WED]],
    ['NB_Showers_Women', 'Scrub Showers',  [WED]],
    ['NB_Showers_Women', 'Sweep',          [WED]],

    // Staff Break Room (Tue)
    ['NB_Staff_Break_Room', 'Empty Trash Large',     [TUE]],
    ['NB_Staff_Break_Room', 'Vacuum Light Traffic',  [TUE]],
    ['NB_Staff_Break_Room', 'Wipe down Counters',    [TUE]],
    ['NB_Staff_Break_Room', 'Wipe down Tables',      [TUE]],

    // Women's Lounges
    ['NB_Womens_Lounge_Down', 'Empty Trash Small',    [WED]],
    ['NB_Womens_Lounge_Down', 'Vacuum Light Traffic',  [WED]],

    ['NB_Womens_Lounge_Up', 'Empty Trash Small',    [TUE]],
    ['NB_Womens_Lounge_Up', 'Vacuum Light Traffic',  [TUE]],
];

// ─── Friendly room names for schedule labels ─────────────────────
$roomLabels = [
    'SB_Hallway_Downstairs'  => 'SB Corridor A',
    'SB_Hallway_Upstairs'    => 'SB Corridor B',
    'SB_Stairs'              => 'SB Stair 1',
    'SB_RR_Men_East'         => 'SB Men\'s East',
    'SB_RR_Men_West'         => 'SB Men\'s West',
    'SB_RR_Women_East'       => 'SB Women\'s East',
    'SB_RR_Women_West'       => 'SB Women\'s West',
    'SB_Chapel'              => 'SB Chapel',
    'SB_Fellowship'          => 'SB Fellowship',
    'SB_Kitchen'             => 'SB Kitchen',
    'SB_Lobby'               => 'SB Lobby',
    'SB_Stage'               => 'SB Stage',
    'SB_CR_100'              => 'SB 100',
    'SB_CR_101'              => 'SB 101',
    'SB_CR_102'              => 'SB 102',
    'SB_CR_103'              => 'SB 103',
    'SB_CR_104'              => 'SB 104/205',
    'SB_CR_201'              => 'SB 201',
    'SB_CR_202'              => 'SB 202',
    'SB_CR_203'              => 'SB 203',
    'SB_CR_204'              => 'SB 204',
    'SB_CR_205'              => 'SB 205',
    'NB_Backstage'           => 'NB Green Room',
    'NB_Gym'                 => 'NB Gymnasium',
    'NB_Sanctuary'           => 'NB Sanctuary',
    'NB_Stage'               => 'NB Stage',
    'NB_Lobby'               => 'NB Lobby',
    'NB_Hallway_Downstairs'  => 'NB Corridor A',
    'NB_Hallway_Upstairs'    => 'NB Corridor D',
    'NB_Office_Downstairs'   => 'NB Office Corridor Down',
    'NB_Office_Upstairs'     => 'NB Office Corridor Up',
    'NB_Cafe'                => 'NB Fireside Cafe',
    'NB_Coffee_Prep'         => 'NB Coffee Prep',
    'NB_Kitchen'             => 'NB Kitchen',
    'NB_Childrens_Lobby'     => 'NB Children\'s Lobby',
    'NB_Nursery'             => 'NB Nursery',
    'NB_Family_Room'         => 'NB Family Room',
    'NB_Preschool_Lobby'     => 'NB Preschool Checkin',
    'NB_Preschool_Main'      => 'NB Preschool Main',
    'NB_CR_Preschool_East'   => 'NB Preschool East',
    'NB_CR_Preschool_West'   => 'NB Preschool West',
    'NB_CR_201'              => 'NB 201',
    'NB_CR_202'              => 'NB 202',
    'NB_CR_203'              => 'NB 203',
    'NB_CR_204'              => 'NB 204',
    'NB_Counseling_Room'     => 'NB Counseling Room',
    'NB_Elevator'            => 'NB Elevator',
    'NB_Fireside_Room'       => 'NB Fireside Room',
    'NB_Prayer_Rooms'        => 'NB Prayer Rooms',
    'NB_Security_Room'       => 'NB CommOpps',
    'NB_Staff_Break_Room'    => 'NB Break Room',
    'NB_Womens_Lounge_Down'  => 'NB Women\'s Lounge Down',
    'NB_Womens_Lounge_Up'    => 'NB Women\'s Lounge Up',
    'NB_RR_Men_Down_North'   => 'NB Men\'s North',
    'NB_RR_Men_Down_South'   => 'NB Men\'s South',
    'NB_RR_Women_Down_North' => 'NB Women\'s North',
    'NB_RR_Women_Down_South' => 'NB Women\'s South',
    'NB_RR_Men_Up'           => 'NB Men\'s Up',
    'NB_RR_Women_Up'         => 'NB Women\'s Up',
    'NB_RR_Nursery'          => 'NB Nursery Restroom',
    'NB_RR_Preschool'        => 'NB Preschool Restroom',
    'NB_Showers_Men'         => 'NB Men\'s Showers',
    'NB_Showers_Women'       => 'NB Women\'s Showers',
];

$dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

// ═══════════════════════════════════════════════════════════════════
// CONFIRMATION / EXECUTION
// ═══════════════════════════════════════════════════════════════════

// Step 1: Group assignments by room + day-pattern
$grouped = []; // key = "roomKey|day1,day2,..." => ['room' => ..., 'days' => [...], 'tasks' => [...]]
foreach ($assignments as [$roomKey, $taskName, $days]) {
    sort($days);
    $dayKey = implode(',', $days);
    $groupKey = $roomKey . '|' . $dayKey;

    if (!isset($grouped[$groupKey])) {
        $grouped[$groupKey] = [
            'room_key' => $roomKey,
            'days'     => $days,
            'tasks'    => [],
        ];
    }
    $taskId = $taskMap[$taskName] ?? null;
    if ($taskId && !in_array($taskId, $grouped[$groupKey]['tasks'])) {
        $grouped[$groupKey]['tasks'][] = $taskId;
    }
}

$totalSchedules = count($grouped);
$totalLinks = 0;
foreach ($grouped as $g) {
    $roomIds = $roomMap[$g['room_key']] ?? [];
    $totalLinks += count($roomIds) + count($g['tasks']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    // ── EXECUTE MIGRATION ──
    $db->beginTransaction();
    try {
        // Clear existing cleaning schedule data
        $db->exec("DELETE FROM cleaning_schedule_tasks");
        $db->exec("DELETE FROM cleaning_schedule_task_groups");
        $db->exec("DELETE FROM cleaning_schedule_rooms");
        $db->exec("DELETE FROM cleaning_schedules");

        $insertSchedule = $db->prepare("
            INSERT INTO cleaning_schedules (name, frequency, frequency_config, assign_to_type, assign_to_role, is_active, created_by)
            VALUES (?, ?, ?, 'role', 'custodial', 1, ?)
        ");
        $insertRoom = $db->prepare("INSERT INTO cleaning_schedule_rooms (schedule_id, room_id) VALUES (?, ?)");
        $insertTask = $db->prepare("INSERT INTO cleaning_schedule_tasks (schedule_id, task_id) VALUES (?, ?)");

        $user = getCurrentUser();
        $createdBy = $user['id'];
        $created = 0;
        $roomLinks = 0;
        $taskLinks = 0;

        foreach ($grouped as $g) {
            $roomKey = $g['room_key'];
            $days    = $g['days'];
            $taskIds = $g['tasks'];
            $roomIds = $roomMap[$roomKey] ?? [];
            $label   = $roomLabels[$roomKey] ?? $roomKey;

            if (empty($roomIds) || empty($taskIds)) continue;

            // Determine frequency
            if ($days == [1,2,3,4,5]) {
                $frequency = 'weekdays';
                $freqConfig = null;
            } else {
                $frequency = 'specific_days';
                $freqConfig = json_encode(['days' => $days]);
            }

            // Build schedule name
            $dayLabel = implode('/', array_map(function($d) use ($dayNames) { return $dayNames[$d]; }, $days));
            $name = "$label - $dayLabel";

            $insertSchedule->execute([$name, $frequency, $freqConfig, $createdBy]);
            $scheduleId = (int)$db->lastInsertId();
            $created++;

            foreach ($roomIds as $rid) {
                $insertRoom->execute([$scheduleId, $rid]);
                $roomLinks++;
            }
            foreach ($taskIds as $tid) {
                $insertTask->execute([$scheduleId, $tid]);
                $taskLinks++;
            }
        }

        $db->commit();

        echo "<!DOCTYPE html><html><head><title>Migration Complete</title>
        <script src='https://cdn.tailwindcss.com'></script></head>
        <body class='bg-gray-50 p-8'>
        <div class='max-w-2xl mx-auto bg-white rounded-2xl shadow-sm p-8 border'>
            <h1 class='text-2xl font-bold text-green-700 mb-4'>✅ Migration Complete</h1>
            <div class='space-y-2 text-sm text-gray-700'>
                <p><strong>Schedules created:</strong> $created</p>
                <p><strong>Room links:</strong> $roomLinks</p>
                <p><strong>Task links:</strong> $taskLinks</p>
            </div>
            <div class='mt-6'>
                <a href='/pages/scheduling.php' class='bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition'>
                    View Schedules →
                </a>
            </div>
        </div></body></html>";
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo "<!DOCTYPE html><html><head><title>Migration Failed</title>
        <script src='https://cdn.tailwindcss.com'></script></head>
        <body class='bg-gray-50 p-8'>
        <div class='max-w-2xl mx-auto bg-white rounded-2xl shadow-sm p-8 border border-red-200'>
            <h1 class='text-2xl font-bold text-red-700 mb-4'>❌ Migration Failed</h1>
            <p class='text-sm text-gray-700'>" . htmlspecialchars($e->getMessage()) . "</p>
        </div></body></html>";
        exit;
    }
}

// ── CONFIRMATION SCREEN ──
?>
<!DOCTYPE html>
<html><head><title>Import Cleaning Schedules</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-50 p-8">
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm p-8 border mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Import Cleaning Schedules</h1>
        <p class="text-sm text-gray-500 mb-6">This migration imports all cleaning schedules from the Todoist export. It will clear any existing schedules first.</p>

        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-700"><?= $totalSchedules ?></div>
                <div class="text-xs text-blue-600 mt-1">Schedules to Create</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-700"><?= count($roomMap) ?></div>
                <div class="text-xs text-green-600 mt-1">Rooms Covered</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-700"><?= count($taskMap) ?></div>
                <div class="text-xs text-purple-600 mt-1">Tasks Referenced</div>
            </div>
        </div>

        <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Schedule Preview</h2>
        <div class="border rounded-lg overflow-hidden mb-6">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Schedule Name</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Frequency</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Rooms</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-600">Tasks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                <?php foreach ($grouped as $g):
                    $roomKey = $g['room_key'];
                    $days = $g['days'];
                    $tasks = $g['tasks'];
                    $roomIds = $roomMap[$roomKey] ?? [];
                    $label = $roomLabels[$roomKey] ?? $roomKey;
                    $dayLabel = ($days == [1,2,3,4,5]) ? 'Weekdays' : implode('/', array_map(function($d) use ($dayNames) { return $dayNames[$d]; }, $days));
                    $taskNames = [];
                    foreach ($tasks as $tid) {
                        $taskNames[] = array_search($tid, $taskMap) ?: "ID:$tid";
                    }
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium"><?= htmlspecialchars("$label - $dayLabel") ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($dayLabel) ?></td>
                        <td class="px-3 py-2 text-gray-500"><?= count($roomIds) ?> room<?= count($roomIds) > 1 ? 's' : '' ?></td>
                        <td class="px-3 py-2 text-gray-500 text-xs"><?= htmlspecialchars(implode(', ', $taskNames)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-yellow-800"><strong>⚠️ Warning:</strong> This will delete all existing cleaning schedules and replace them with the imported data. All schedules will be assigned to the <strong>custodial</strong> role.</p>
        </div>

        <form method="POST">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-6 py-3 text-sm transition">
                Run Migration
            </button>
            <a href="/pages/scheduling.php" class="ml-4 text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </form>
    </div>
</div>
</body></html>
