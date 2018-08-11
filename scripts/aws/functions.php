<?php

function aws_client($profile = "default")
{
	$_profile = escapeshellarg($profile);
	return "/usr/local/bin/aws --profile $_profile";
}


# https://docs.aws.amazon.com/Route53/latest/APIReference/API_ResourceRecordSet.html
function aws_record_change($action, $type, $host, $value)
{
	$set = array(
		"Action" => $action,
		"ResourceRecordSet" => array(
			"Name" => $host,
			"Type" => $type,
			"TTL" => 300,
			"ResourceRecords" => array(),
		),
	);

	if (strpos($value, "\n") === false) {
		$set["ResourceRecordSet"]["ResourceRecords"][] = array("Value" => $value);
	} else {
		$values = explode("\n", $value);
		foreach ($values as $subvalue)
			$set["ResourceRecordSet"]["ResourceRecords"][] = array("Value" => $subvalue);
	}

	return $set;
}
