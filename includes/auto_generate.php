<?php
/**
 * Auto-generate task assignments on every page load (if enabled).
 *
 * This file is included from nav.php. It checks the auto_generate_assignments
 * setting and, if enabled, creates any missing assignments within the
 * configured generation window. Safe to call repeatedly — existing
 * assignments are skipped.
 *
 * Requires: $db (PDO) must NOT be available yet — we get our own connection
 * to keep nav.php clean. auth.php must already be loaded.
 */

// Bail fast if not logged in (nav.php calls requireLogin() before this)
if (!function_exists('isLoggedIn') || !isLoggedIn()) return;

// Get DB connection
require_once __DIR__ . '/../config/database.php';
$_agDb = getDB();

// ── Check if auto-generate is enabled ────────────────────────
$_agSetting = $_agDb->query("SELECT setting_value FROM settings WHERE setting_key = 'auto_generate_assignments'")->fetch();
if (!$_agSetting || $_agSetting['setting_value'] !== '1') return;

// ── Get generation window ────────────────────────────────────
$_agDays = 14;
$_agS = $_agDb->query("SELECT setting_value FROM settings WHERE setting_key = 'schedule_generation_days'")->fetch();
if ($_agS) $_agDays = max(1, (int)$_agS['setting_value']);

$_agDefaultDeadline = '08:00';
$_agS2 = $_agDb->query("SELECT setting_value FROM settings WHERE setting_key = 'default_deadline_time'")->fetch();
if ($_agS2) $_agDefaultDeadline = $_agS2['setting_value'];

// ── Get active schedules ─────────────────────────────────────
$_agSchedules = $_agDb->query("SELECT * FROM cleaning_schedules WHERE is_active = 1")->fetchAll();
if (empty($_agSchedules)) return;

// ── Helper: recursively collect leaf task IDs ────────────────
function _agCollectLeafTasks($db, $groupId, &$visited = []) {
    if (in_array($groupId, $visited)) return [];
    $visited[] = $groupId;
    $ids = [];

    $st = $db->prepare("SELECT task_id FROM task_group_tasks WHERE task_group_id = ?");
    $st->execute([$groupId]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $t) $ids[$t] = $t;

    $ch = $db->prepare("SELECT id FROM task_groups WHERE parent_id = ?");
    $ch->execute([$groupId]);
    foreach ($ch->fetchAll(PDO::FETCH_COLUMN) as $cid) {
        foreach (_agCollectLeafTasks($db, $cid, $visited) as $t) $ids[$t] = $t;
    }
    return array_values($ids);
}

// ── Generate assignments ─────────────────────────────────────
$_agToday   = new DateTime();
$_agEndDate = (clone $_agToday)->modify("+{$_agDays} days");

foreach ($_agSchedules as $_agSched) {
    $_agFreqConfig = $_agSched['frequency_config'] ? json_decode($_agSched['frequency_config'], true) : [];

    // Rooms
    $_agR = $_agDb->prepare("SELECT room_id FROM cleaning_schedule_rooms WHERE schedule_id = ?");
    $_agR->execute([$_agSched['id']]);
    $_agRoomIds = $_agR->fetchAll(PDO::FETCH_COLUMN);
    if (empty($_agRoomIds)) continue;

    // Task groups
    $_agTG = $_agDb->prepare("SELECT task_group_id FROM cleaning_schedule_task_groups WHERE schedule_id = ?");
    $_agTG->execute([$_agSched['id']]);
    $_agTGIds = $_agTG->fetchAll(PDO::FETCH_COLUMN);

    // Individual tasks
    $_agT = $_agDb->prepare("SELECT task_id FROM cleaning_schedule_tasks WHERE schedule_id = ?");
    $_agT->execute([$_agSched['id']]);
    $_agTIds = $_agT->fetchAll(PDO::FETCH_COLUMN);

    if (empty($_agTGIds) && empty($_agTIds)) continue;

    // Workers
    $_agWorkerIds = [];
    if ($_agSched['assign_to_type'] === 'user' && $_agSched['assign_to_user_id']) {
        $_agWorkerIds = [$_agSched['assign_to_user_id']];
    } elseif ($_agSched['assign_to_type'] === 'role' && $_agSched['assign_to_role']) {
        $_agW = $_agDb->prepare("SELECT id FROM users WHERE role = ? AND is_active = 1");
        $_agW->execute([$_agSched['assign_to_role']]);
        $_agWorkerIds = $_agW->fetchAll(PDO::FETCH_COLUMN);
    }
    if (empty($_agWorkerIds)) continue;

    $_agDeadline = $_agSched['deadline_time'] ?: $_agDefaultDeadline;

    // Walk each date in the window
    $_agCur = clone $_agToday;
    while ($_agCur <= $_agEndDate) {
        $_agDateStr = $_agCur->format('Y-m-d');
        $_agDow     = (int)$_agCur->format('N'); // 1=Mon … 7=Sun

        $_agShouldRun = false;
        switch ($_agSched['frequency']) {
            case 'daily':         $_agShouldRun = true; break;
            case 'weekdays':      $_agShouldRun = ($_agDow >= 1 && $_agDow <= 5); break;
            case 'specific_days': $_agShouldRun = in_array($_agDow, $_agFreqConfig['days'] ?? []); break;
            case 'weekly':        $_agShouldRun = ($_agDow == ($_agFreqConfig['day_of_week'] ?? 1)); break;
            case 'biweekly':
                $dw = $_agFreqConfig['day_of_week'] ?? 1;
                $sd = $_agSched['created_at'] ? new DateTime($_agSched['created_at']) : $_agToday;
                $_agShouldRun = ($_agDow == $dw && (int)floor($sd->diff($_agCur)->days / 7) % 2 === 0);
                break;
            case 'monthly':       $_agShouldRun = ((int)$_agCur->format('j') === ($_agFreqConfig['day_of_month'] ?? 1)); break;
            case 'yearly':
                $_agShouldRun = ((int)$_agCur->format('n') === ($_agFreqConfig['month'] ?? 1)
                              && (int)$_agCur->format('j') === ($_agFreqConfig['day'] ?? 1));
                break;
        }

        if ($_agShouldRun) {
            foreach ($_agRoomIds as $_agRid) {
                // Task groups
                foreach ($_agTGIds as $_agTgId) {
                    foreach ($_agWorkerIds as $_agWid) {
                        $_agChk = $_agDb->prepare("SELECT id FROM janitor_task_assignments WHERE room_id=? AND task_group_id=? AND assigned_to=? AND assigned_date=?");
                        $_agChk->execute([$_agRid, $_agTgId, $_agWid, $_agDateStr]);
                        if ($_agChk->fetch()) continue;

                        $_agDb->prepare("INSERT INTO janitor_task_assignments (schedule_id,assigned_date,assigned_to,task_group_id,room_id,deadline,status) VALUES (?,?,?,?,?,?,'pending')")
                            ->execute([$_agSched['id'], $_agDateStr, $_agWid, $_agTgId, $_agRid, $_agDateStr.' '.$_agDeadline]);
                        $_agAid = (int)$_agDb->lastInsertId();

                        $_agLeafTasks = _agCollectLeafTasks($_agDb, $_agTgId);
                        $_agInsChk = $_agDb->prepare("INSERT INTO janitor_task_checklist (assignment_id,task_id,completed) VALUES (?,?,0)");
                        foreach ($_agLeafTasks as $_agTid) $_agInsChk->execute([$_agAid, $_agTid]);
                    }
                }
                // Individual tasks
                foreach ($_agTIds as $_agTaskId) {
                    foreach ($_agWorkerIds as $_agWid) {
                        $_agChk = $_agDb->prepare("SELECT id FROM janitor_task_assignments WHERE room_id=? AND task_id=? AND assigned_to=? AND assigned_date=? AND task_group_id IS NULL");
                        $_agChk->execute([$_agRid, $_agTaskId, $_agWid, $_agDateStr]);
                        if ($_agChk->fetch()) continue;

                        $_agDb->prepare("INSERT INTO janitor_task_assignments (schedule_id,assigned_date,assigned_to,task_id,task_group_id,room_id,deadline,status) VALUES (?,?,?,?,NULL,?,?,'pending')")
                            ->execute([$_agSched['id'], $_agDateStr, $_agWid, $_agTaskId, $_agRid, $_agDateStr.' '.$_agDeadline]);
                        $_agAid = (int)$_agDb->lastInsertId();
                        $_agDb->prepare("INSERT INTO janitor_task_checklist (assignment_id,task_id,completed) VALUES (?,?,0)")->execute([$_agAid, $_agTaskId]);
                    }
                }
            }
        }
        $_agCur->modify('+1 day');
    }
}

// Clean up variables to avoid polluting the global scope
unset($_agDb, $_agSetting, $_agDays, $_agS, $_agS2, $_agDefaultDeadline, $_agSchedules,
      $_agSched, $_agFreqConfig, $_agR, $_agRoomIds, $_agTG, $_agTGIds, $_agT, $_agTIds,
      $_agWorkerIds, $_agW, $_agDeadline, $_agToday, $_agEndDate, $_agCur, $_agDateStr,
      $_agDow, $_agShouldRun, $_agRid, $_agTgId, $_agWid, $_agChk, $_agAid, $_agLeafTasks,
      $_agInsChk, $_agTid, $_agTaskId);
