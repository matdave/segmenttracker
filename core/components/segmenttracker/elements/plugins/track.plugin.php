<?php
$corePath = $modx->getOption('segmenttracker.core_path', null, $modx->getOption('core_path') . 'components/segmenttracker/');
$segment = $modx->getService(
    'segmenttracker', 
    'segmentTracker', 
    $corePath . 'model/segmenttracker/', 
    array('core_path' => $corePath)
);

if ( !($segment instanceof segmentTracker) ) {
    $modx->log(xPDO::LOG_LEVEL_ERROR, '[SegmentTrack Plugin] Could not load segment class.');
    return;
}
$prefix = $modx->getOption('segmenttracker.prefix_modx_id', $scriptProperties, null);

switch ($modx->event->name) {
    case 'OnWebLogin':
        if( !empty($user) ) {
            if( !$segment->trackUser('Signed In', $user->get('username'), $prefix.$user->id) ) {
                $modx->log(xPDO::LOG_LEVEL_ERROR, '[SegmentTrack Plugin] Could not track login.');
                return;
            }
        }
        break;

    case 'OnWebLogout':
        if( !empty($user) ) {
            if( !$segment->trackUser('Signed Out', $user->get('username'), $prefix.$user->id) ) {
                $modx->log(xPDO::LOG_LEVEL_ERROR, '[SegmentTrack Plugin] Could not track logout.');
                return;
            }
        }
        break;

    case 'OnUserBeforeSave':
        if( !empty($user) ) {
            $profile = $user->getOne('Profile');
            if( empty($profile) ) return;
            $trackUser = array(
                'id' => $prefix.$user->id,
                'name' => $profile->get('fullname'),
                'email' => $profile->get('email'),
                'logins' => $profile->get('logincount')
            );
            if( !$segment->identify($trackUser) ) {
                $modx->log(xPDO::LOG_LEVEL_ERROR, '[SegmentTrack Plugin] Could not track profile update.');
                return;
            }
        }
        break;
}

return;