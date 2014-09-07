<?php

header("Connection: Close");
flush();
//copy(
//    'compress.zlib://php://input',
//    __DIR__ . '/data/' . time() . '.json'
//);
$url = 'compress.zlib://php://input';
//$url = 'data/1387720180.json';

$data = json_decode(file_get_contents($url), true);

$db = new PDO('sqlite:data/test.sqlite3');
// Set errormode to exceptions
$db->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);
$db->beginTransaction();
$db->prepare("
    INSERT INTO
        requests(request_host, request_uri, request_timestamp)
    VALUES (
        :host,
        :uri,
        :timestamp
    );
")->execute(array(
    ':host' => $_GET['host'],
    ':uri' => $_GET['uri'],
    ':timestamp' => $_GET['timestamp']
));
$requestId = $db->lastInsertId();


$sth1 = $db->prepare("
    INSERT INTO
        calls (
             request_id,
             ct,
             wt,
             cpu,
             mu,
             pmu,
             caller,
             callee
         )
    VALUES (
        :request_id,
        :ct,
        :wt,
        :cpu,
        :mu,
        :pmu,
        :caller,
        :callee
    );
");
$trim = function ($name) {
    return $name;
};
foreach ($data as $call => $row) {
    $call = explode('==>', $call);
    if (count($call) === 1) {
        $caller = null;
        $callee = $trim($call[0]);
    } else {
        $caller = $trim($call[0]);
        $callee = $trim($call[1]);
    }

    $sth1->execute(array(
        ':request_id' => $requestId,
        ':ct' => $row['ct'],
        ':wt' => $row['wt'],
        ':cpu' => $row['cpu'],
        ':mu' => $row['mu'],
        ':pmu' => $row['pmu'],
        ':caller' => $caller,
        ':callee' => $callee,
    ));
}
$db->commit();

/*

SELECT
  c.callee,
  (c.wt - total(cc.wt) + 0.0) / 1000.0 AS time_main,
  (c.cpu - total(cc.cpu) + 0.0) / 1000.0 AS cpu_main
FROM "calls" AS c
  LEFT JOIN "calls" AS cc
    ON (cc.request_id = c.request_id
        AND c.callee = cc.caller
  )
WHERE
  c.request_id = 5
GROUP BY
  c.callee
ORDER BY time_main DESC ;


 */