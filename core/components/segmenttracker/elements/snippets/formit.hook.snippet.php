<?php
/**
 * Segment.FormIt.Hook
 *
 * Add this hook to a FormIt call to track interactions
 *
 * Properties:
 *   segmentDebug (bool) - By default, tracking failure allows the form to continue
 *   segmentTrackEvent (string) - Can be a specified event or a formit variable to attribute to the event (required)
 *   segmentTrackFields (string) - Limit what is tracked to just the specified comma-separated fields.
 *       Optionally translate fields to event properties using ==, e.g. `field1==property1,field2==property2`
 *   segmentIdentifyFields (string) - Add identity fields from your form to a user in Segment. Works similarly to segmentTrackFields.
  */
$debug = $modx->getOption('segmentDebug', $hook->formit->config, false);
$corePath = $modx->getOption('segmenttracker.core_path', null, $modx->getOption('core_path') . 'components/segmenttracker/');
$segment = $modx->getService(
    'segmenttracker',
    'segmentTracker',
    $corePath . 'model/segmenttracker/',
    array('core_path' => $corePath)
);

if (!($segment instanceof segmentTracker)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, '[Segment.FormIt.Hook] Could not load segment class.');
    if ($debug) {
        $hook->addError('segment', 'Could not load Segment class.');
        return false;
    } else {
        return true;
    }
}

$values = $hook->getValues();

$event = $hook->formit->config['segmentTrackEvent'];

//Process if event is a dynamic field
$event = str_replace('[[+', '', $event);
    $event = str_replace('[[!+', '', $event);
    $event = str_replace(']]', '', $event);
    $event = ($values[$event]) ? $values[$event] : $event;

if (empty($event)) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, '[Segment.FormIt.Hook] No tracking event specified.');
    if ($debug) {
        $hook->addError('segment', 'No tracking event specified.');
        return false;
    } else {
        return true;
    }
}

//Process properties
$properties = array();
if ($hook->formit->config['segmentTrackFields']) {
    $properties = $segment->getProperties($hook->formit->config['segmentTrackFields'], $values);
}

//Identify user if fields specified
if ($hook->formit->config['segmentIdentifyFields']) {
    $user = $segment->getProperties($hook->formit->config['segmentIdentifyFields'], $values);
    if (!empty($user) && !$segment->identify($user)) {
        $modx->log(xPDO::LOG_LEVEL_ERROR, '[Segment.FormIt.Hook] Unable to identify user: '.json_encode($user));
    }
}

if ($segment->track($event, $properties)) {
    return true;
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR, '[Segment.FormIt.Hook] Unable to track event.');
    if ($debug) {
        $hook->addError('segment', 'Unable to track event.');
        return false;
    } else {
        return true;
    }
}
