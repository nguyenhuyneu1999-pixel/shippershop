<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();$pdo=$d->getConnection();
$results = [];

// Check user_xp
try{$r=$d->fetchAll("SELECT * FROM user_xp WHERE user_id=3 LIMIT 3");$results['user_xp']='OK ('.count($r).' rows)';}
catch(\Throwable $e){$results['user_xp']='ERR: '.$e->getMessage();}

// Check user_streaks
try{$r=$d->fetchAll("SHOW COLUMNS FROM user_streaks");$results['user_streaks_cols']=array_column($r,'Field');}
catch(\Throwable $e){$results['user_streaks']='ERR: '.$e->getMessage();}

// Check user_badges
try{$r=$d->fetchAll("SHOW COLUMNS FROM user_badges");$results['user_badges_cols']=array_column($r,'Field');}
catch(\Throwable $e){$results['user_badges']='ERR: '.$e->getMessage();}

// Check user_streaks data
try{$r=$d->fetchOne("SELECT * FROM user_streaks WHERE user_id=3");$results['streak_data']=$r;}
catch(\Throwable $e){$results['streak_query']='ERR: '.$e->getMessage();}

echo json_encode($results, JSON_PRETTY_PRINT);
