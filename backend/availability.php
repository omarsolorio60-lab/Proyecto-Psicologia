<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/../config/database_connection.php';
$date=$_GET['fecha']??'';
if (!is_string($date)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!($d=DateTimeImmutable::createFromFormat('!Y-m-d',$date))||$d->format('Y-m-d')!==$date) {http_response_code(422);echo json_encode(['ok'=>false,'error'=>'Fecha inválida']);exit;}
try {
 $total=(int)$pdo->query('SELECT COUNT(*) FROM psychologists WHERE active=1')->fetchColumn();
 $s=$pdo->prepare("SELECT DATE_FORMAT(requested_date,'%H:%i') hour, COUNT(*) occupied FROM appointments WHERE DATE(requested_date)=? AND status IN ('Solicitada','Confirmada') GROUP BY requested_date");
 $s->execute([$date]);$slots=[];foreach($s as $r)$slots[$r['hour']]=max(0,$total-(int)$r['occupied']);
 echo json_encode(['ok'=>true,'capacity'=>$total,'available'=>$slots]);
} catch(Throwable $e){error_log($e->getMessage());http_response_code(500);echo json_encode(['ok'=>false,'error'=>'No se pudo consultar la disponibilidad.']);}
