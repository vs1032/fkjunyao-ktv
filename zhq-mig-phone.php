<?php
// 一次性：salesmen.phone 归一化(空串→NULL) + 加唯一约束
header('Content-Type: application/json; charset=utf-8');
$cfg = require __DIR__ . '/config.php';
$db = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$out = [];
try {
    $out['before_total'] = (int)$db->query('SELECT COUNT(*) FROM salesmen')->fetchColumn();
    $out['empty_to_null'] = $db->exec("UPDATE salesmen SET phone=NULL WHERE phone='' OR TRIM(phone)=''");
    $dups = $db->query('SELECT phone,COUNT(*) c FROM salesmen WHERE phone IS NOT NULL GROUP BY phone HAVING c>1')->fetchAll(PDO::FETCH_ASSOC);
    $out['dups'] = $dups;
    if ($dups) {
        $out['error'] = '仍有重复手机号，未加约束';
    } else {
        $has = $db->query("SHOW INDEX FROM salesmen WHERE Key_name='uk_salesmen_phone'")->fetchAll();
        if (!$has) {
            $db->exec('ALTER TABLE salesmen ADD UNIQUE KEY uk_salesmen_phone (phone)');
            $out['added'] = true;
        } else {
            $out['added'] = 'already';
        }
        $out['indexes'] = $db->query("SHOW INDEX FROM salesmen WHERE Column_name='phone'")->fetchAll(PDO::FETCH_ASSOC);
        $out['null_phones'] = (int)$db->query('SELECT COUNT(*) FROM salesmen WHERE phone IS NULL')->fetchColumn();
    }
} catch (Throwable $e) {
    $out['error'] = $e->getMessage();
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
@unlink(__FILE__);
