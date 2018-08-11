<?php

# http://www.zytrax.com/books/dns/ch8/a.html
function bind_entry($name, $type, $value)
{
	if (strpos($value, "\n") === false)
		return sprintf("%-50s%-10s%s\n", $name, $type, $value);

	$values = explode("\n", $value);
	$first = array_shift($values);
	$data = sprintf("%-50s%-10s%s\n", $name, $type, $first);

	foreach ($values as $subvalue)
		$data .= sprintf("%-50s%-10s%s\n", "", $type, $subvalue);

	return $data;
}


# http://www.zytrax.com/books/dns/ch8/txt.html
function bind_txt_entry($name, $type, $value)
{
	if (strpos($value, "\n") === false)
		return sprintf("%-50s%-10s%s\n", $name, $type, $value);

	$values = explode("\n", $value);
	$first = array_shift($values);
	$last = array_pop($values);
	$data = sprintf("%-50s%-10s(%s\n", $name, $type, $first);

	foreach ($values as $subvalue)
		$data .= sprintf("%-60s%s\n", "", $subvalue);

	$data .= sprintf("%-60s%s)\n", "", $last);
	return $data;
}
