<?php

// RUN within side a components view for instance. 

//error_reporting(E_ALL);

// output headers so that the file is downloaded rather than displayed
header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="jomestate-export.csv"');

// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');

// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');

// Set DB Connections
$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select('prop.*, hr.value_string AS homereport, br.value_string AS brochure, ds.value_string AS digitalschedule ');
$query->from($db->quoteName('#__cddir_jomestate', 'prop'));
// Fields with the id of 7 = HomeReport in out scenario - Comment out if you do not want this
$query->join('INNER', $db->quoteName('#__cddir_content_has_fields', 'hr') . ' ON (' . $db->quoteName('hr.content_id') . ' = ' . $db->quoteName('prop.id') . ' AND ' . $db->quoteName('hr.fields_id') . ' = 6)');
$query->join('INNER', $db->quoteName('#__cddir_content_has_fields', 'br') . ' ON (' . $db->quoteName('br.content_id') . ' = ' . $db->quoteName('prop.id') . ' AND ' . $db->quoteName('br.fields_id') . ' = 7)');
$query->join('INNER', $db->quoteName('#__cddir_content_has_fields', 'ds') . ' ON (' . $db->quoteName('ds.content_id') . ' = ' . $db->quoteName('prop.id') . ' AND ' . $db->quoteName('ds.fields_id') . ' = 8)');
$query->order('prop.id ASC');

// Reset the query using our newly populated query object.
$db->setQuery($query);

// Load the results as a list of stdClass objects (see later for more options on retrieving data).
$results = $db->loadObjectList();

//echo '<pre>';

// Fields to exclude, to save time with the export and trials enter the column headers into this array to exclude them from the CSV
// Note, these were removed as part of the OS Property Import, so I had less to do, client had a particular way of storing properties.

$excludeColumns = array('maps_zoom', 'language', 'date_modified', 'date_created', 'nr_reference', 'agent_id', 'asset_id', 'company_id', 'id');

$headers = array();
$r = 0;

$categories = getCategories();

foreach ($results AS $pkey => $prop) {
	$rowContent = array();

	foreach ($prop as $rkey => $row) {
		if (in_array($rkey, $excludeColumns))
		{
			continue;
		}
		if ($r === 0) {
			$headers[] = $rkey;
		}

		if ($rkey === 'categories_id') {
			$rowContent[] = $categories[$row]['title'];
		} else if ($rkey === 'categories_type_id') {
			$rowContent[] = $categories[$row]['title'];
		} else if ($rkey === 'categories_address_id') {
			$rowContent[] = $categories[$row]['title'];
		}else if ($rkey === 'homereport' || $rkey === 'brochure' || $rkey === 'digitalschedule') {

			// Method to get just the URL from a string.
			$link = '';
			if (strlen($row) > 5)
			{
				$url  = preg_match('/href=["\']?([^"\'>]+)["\']?/', $row, $match);
				$link = $match[1];
			}

			$rowContent[] = $link;

		} else {
			$rowContent[] = $row;
		}
	}

	// Get Images
	$gotImages  = getImages($pkey);
	$gotImagescount = count($gotImages);
	$images = '';
	$rr = 0;
	foreach( getImages($pkey) as $image) {
		$images .= 'https://www.website.co.uk/components/com_jomcomdev/images/' . $image['path'] . '/' . $image['name'];

		if ($gotImagescount !== $rr) {
			$images .= '|';
		}
		$rr++;
	}

	$rowContent[] = getPrice($pkey)->price_netto;
	$rowContent[] = $images;
	$rowContent[] = '';

	if ($r === 0) {
		$headers[] = 'price';
		$headers[] = 'photos';
		$headers[] = 'files';
		fputcsv($file, $headers);
	}
	fputcsv($file, $rowContent);
	$r++;
}

function getPrice($propId) {
	// Set DB Connections
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select('price_netto');
	$query->from($db->quoteName('#__cddir_prices'));
	$query->where($db->quoteName('content_id') . ' = ' . $propId);

	// Reset the query using our newly populated query object.
	$db->setQuery($query);

	// Load the results as a list of stdClass objects (see later for more options on retrieving data).
	return $db->loadObject();
}

function getCategories() {
	// Set DB Connections
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select('*');
	$query->from($db->quoteName('#__cddir_categories'));
	$query->order('id ASC');

	// Reset the query using our newly populated query object.
	$db->setQuery($query);

	// Load the results as a list of stdClass objects (see later for more options on retrieving data).
	return $db->loadAssocList('id');

}

function getImages($propId) {

	// Set DB Connections
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select('*');
	$query->from($db->quoteName('#__cddir_images'));
	$query->where($db->quoteName('content_id') . ' = ' . $propId);
	$query->order('id ASC');

	// Reset the query using our newly populated query object.
	$db->setQuery($query);

	// Load the results as a list of stdClass objects (see later for more options on retrieving data).
	return $db->loadAssocList();
}

function getFiles($propId) {

	// Set DB Connections
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select('');
	$query->from($db->quoteName('#__cddir_images'));
	$query->where($db->quoteName('content_id') . ' = ' . $propId);
	$query->order('id ASC');

	// Reset the query using our newly populated query object.
	$db->setQuery($query);

	// Load the results as a list of stdClass objects (see later for more options on retrieving data).
	return $db->loadObjectList('id');
}

exit();
