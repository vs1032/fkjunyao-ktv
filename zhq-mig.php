<?php
// 一次性：清理 exam_id 已不存在的孤儿题（先备份到同目录 json）
header('Content-Type: application/json; charset=utf-8');
$cfg = require __DIR__ . '/config.php';
$db = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$out = [];
try {
    $before = (int)$db->query('SELECT COUNT(*) FROM questions')->fetchColumn();
    $orphans = $db->query('SELECT q.* FROM questions q LEFT JOIN exams e ON e.id=q.exam_id WHERE e.id IS NULL')->fetchAll(PDO::FETCH_ASSOC);
    $out['orphans'] = count($orphans);
    $out['ids'] = array_map(function ($r) { return (int)$r['id']; }, $orphans);
    $out['exam_ids'] = array_values(array_unique(array_map(function ($r) { return (int)$r['exam_id']; }, $orphans)));
    file_put_contents(__DIR__ . '/__orphan_backup_1019.json', json_encode($orphans, JSON_UNESCAPED_UNICODE));
    $del = $db->exec('DELETE q FROM questions q LEFT JOIN exams e ON e.id=q.exam_id WHERE e.id IS NULL');
    $out['deleted'] = $del;
    $out['before'] = $before;
    $out['after'] = (int)$db->query('SELECT COUNT(*) FROM questions')->fetchColumn();
    $out['per_exam'] = $db->query('SELECT exam_id,COUNT(*) c FROM questions GROUP BY exam_id ORDER BY exam_id')->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Throwable $e) {
    $out['error'] = $e->getMessage();
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
@unlink(__FILE__);
