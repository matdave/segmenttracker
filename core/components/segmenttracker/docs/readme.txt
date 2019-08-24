# Segment Tracker for MODX

This package contains options for tracking MODX user interactions in Segment. To signup for Segment visit https://segment.com/. 

This package assumes you have the Segment javascript tracking code enabled on the frontend of your site. 

## Snippets

### SegmentTrack

Add this snippet to a page or call via runSnippet to add segment tracking to interactions.
 
#### Properties:
    
| Property | Description |
| ----------- | ----------- |
| event (string) | A specified event to track (required) | 
| properties (mixed) | Track additional field value pairs either as a passed array array('property1'=>'value1'), a comma-separated string `property1==value1`, or json `{"property1":"value1"}`. | 
| identity (mixed) | Add identity field value pairs either as a passed array array('id'=>'modx_1','name'=>'Full Name'), a comma-separated string `id==modx_1,name==Full Name`, or json `{"id":"modx_1","name":"Full Name"}`. | 
### Segment.FormIt.Hook
 
  Add this hook to a FormIt call to track interactions
 
#### Properties:

| Property | Description |
| ----------- | ----------- |
| segmentDebug (bool) | By default, tracking failure allows the form to continue
| segmentTrackEvent (string) | Can be a specified event or a formit variable to attribute to the event (required)
| segmentTrackFields (string) | Limit what is tracked to just the specified comma-separated fields. _Optionally translate fields to event properties using ==, e.g. `contact_name==name,contact_email==email`_
| segmentIdentifyFields (string) | Add identity fields from your form to a user in Segment. Works similarly to segmentTrackFields.

## Plugin

### SegmentTrackPlugin

Tracks a users login, logout, and profile save events

## System Settings

| Key | Description | Default |
| ----------- | ----------- | ----------- | 
| write_key | The API write key specified to your Segment source. | _null_ |
| use_modx_id | Track user using the MODX User ID if logged in and no tracking ID is specified in segmenttracker cookie. | true |
| prefix_modx_id | If tracking using the MODX User ID you can add a prefix to prevent conflicts with other systems. | _null_ |