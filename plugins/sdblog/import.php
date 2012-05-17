<?php
chdir(dirname(__FILE__));

define('SDB_IMPORTER', true);
require_once( dirname(__FILE__).'/../../../includes/load-yourls.php' );
require_once( dirname(__FILE__).'/plugin.php' );

global $ydb;
$sdb = sdblog_get_database();
$select = "SELECT * FROM `" . SDB_DOMAIN . "`";
$next_token = null;
$count = 0;
$id_holder = array();
$currId = 0;

// Insert all log message - we're assuming input filtering happened earlier
$query = array();
$clicks = array();

do {
    if($next_token) {
        $response = $sdb->select($select, array('NextToken' => $next_token));
    } else {
        $response = $sdb->select($select);
    }
    foreach($response->body->SelectResult->Item as $item) {
        $count++;
        $id_holder[$currId][(string)$item->Name] = null;
        if($count % 25 == 0) {
            $currId++;
            $id_holder[$currId] = array();
        }
        $value = array();
        foreach($item->Attribute as $attrib) {
            $value[(string)$attrib->Name] = (string)$attrib->Value;
        }

        $query[] = "(FROM_UNIXTIME(" . $value['time'] . "), '" . 
    		$value['keyword'] . "', '" . 
    		$value['referer'] . "', '" . 
    		$value['ua'] . "', '" . 
    		$value['ip'] . "', '" . 
    		yourls_geo_ip_to_countrycode($value['ip']) . "')";

		if(!isset($clicks[$value['keyword']])) {
			$clicks[$value['keyword']] = 0;
		}
		$clicks[$value['keyword']]++;
    }
    
    $next_token = isset($response->body->SelectResult->NextToken) ? 
                    (string) $response->body->SelectResult->NextToken :
                    null;
} while($next_token);

$q = "INSERT INTO `" . YOURLS_DB_TABLE_LOG . "` 
			(click_time, shorturl, referrer, user_agent, ip_address, country_code)
			VALUES " . implode(",", $query);
$ydb->query($q);
		

foreach($clicks as $keyword => $click_count) {
	$ydb->query('UPDATE ' . YOURLS_DB_TABLE_URL . ' SET clicks = clicks + ' . $click_count . ' WHERE keyword = \'' . $keyword . '\'');
}
		
if($count == 0 ) {
    die("No clicks\n");
}	
			
echo "Inserted $count Clicks\n";

$deleted = 0;
foreach($id_holder as $ids) {
    if(count($ids) > 0 ) {
        $deleted += count($ids);
        $response = $sdb->batch_delete_attributes(SDB_DOMAIN, $ids);
    }
}

echo "Deleted $deleted Clicks\n";
