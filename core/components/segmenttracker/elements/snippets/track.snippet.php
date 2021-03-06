<?php
/**
 * SegmentTrack
 *
 * Add this snippet to a page or call via runSnippet to add segment tracking to interactions.
 *
 * Properties:
 *   event (string) - A specified event to track (required)
 *   properties (mixed) - Track additional field value pairs either as a passed array array('property1'=>'value1'), a comma-separated string `property1==value1`, or json `{"property1":"value1"}`. 
 *   identity (mixed) - Add identity field value pairs either as a passed array array('id'=>'modx_1','name'=>'Full Name'), a comma-separated string `id==modx_1,name==Full Name`, or json `{"id":"modx_1","name":"Full Name"}`.
  */
$corePath = $modx->getOption('segmenttracker.core_path', null, $modx->getOption('core_path') . 'components/segmenttracker/');
$segment = $modx->getService(
    'segmenttracker',
    'segmentTracker',
    $corePath . 'model/segmenttracker/',
    array('core_path' => $corePath)
);

if (!($segment instanceof segmentTracker)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, '[SegmentTrack] Could not load segment class.');
    return;
}

$event = $modx->getOption('event', $scriptProperties, null);
$properties = $modx->getOption('properties', $scriptProperties, array());
$identity = $modx->getOption('identity', $scriptProperties, array());

if (!$event) {
    return;
}

if (!is_array($properties)) {
    $properties_json = json_decode($properties, true);
    if (!empty($properties_json)) {
        $properties = $properties_json;
    } else {
        $properties = explode(',', $properties);
        if (!empty($properties)) {
            $properties_array = array();
            foreach ($properties as $field) {
                $property = explode('==', $field);
                $properties_array[$field[0]] = ($field[1]) ? $field[1] : $field[0];
            }
            $properties = $properties_array;
        } else {
            $properties = array();
        }
    }
}

if (!$segment->track($event, $properties)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, '[SegmentTrack] Could not track event '.$event.'. '.json_encode($properties));
    return;
}

if (!is_array($identity)) {
    $identity_json = json_decode($identity, true);
    if (!empty($identity_json)) {
        $identity = $identity_json;
    } else {
        $identity = explode(',', $identity);
        if (!empty($identity)) {
            $identity_array = array();
            foreach ($identity as $field) {
                $property = explode('==', $field);
                $identity_array[$field[0]] = ($field[1]) ? $field[1] : $field[0];
            }
            $identity = $identity_array;
        }
    }
}

if (is_array($identity) && !empty($identity) && !$segment->identify($identity)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, '[SegmentTrack] Unable to identify user: '.json_encode($identity));
}