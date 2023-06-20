<?php 

class GoogleCalendarApi { 
    const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token'; 
    const CALENDAR_TIMEZONE_URI = 'https://www.googleapis.com/calendar/v3/users/me/settings/timezone'; 
    const CALENDAR_LIST = 'https://www.googleapis.com/calendar/v3/users/me/calendarList'; 
    const CALENDAR_EVENT = 'https://www.googleapis.com/calendar/v3/calendars/'; 
     
    function __construct($params = array()) { 
        if (count($params) > 0){ 
            $this->initialize($params);         
        } 
    } 
     
    function initialize($params = array()) { 
        if (count($params) > 0){ 
            foreach ($params as $key => $val){ 
                if (isset($this->$key)){ 
                    $this->$key = $val; 
                } 
            }         
        } 
    } 
     
    public function GetAccessToken($client_id, $redirect_uri, $client_secret, $code) { 
        $curlPost = 'client_id=' . $client_id . '&redirect_uri=' . $redirect_uri . '&client_secret=' . $client_secret . '&code='. $code . '&grant_type=authorization_code'; 
        $ch = curl_init();         
        curl_setopt($ch, CURLOPT_URL, self::OAUTH2_TOKEN_URI);         
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);         
        curl_setopt($ch, CURLOPT_POST, 1);         
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);     
        $data = json_decode(curl_exec($ch), true); 
        $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE); 
         
        if ($http_code != 200) { 
            $error_msg = 'Failed to receieve access token'; 
            if (curl_errno($ch)) { 
                $error_msg = curl_error($ch); 
            } 
            throw new Exception('Error '.$http_code.': '.$error_msg); 
        } 
             
        return $data; 
    } 
 
    public function GetUserCalendarTimezone($access_token) { 
        $ch = curl_init();         
        curl_setopt($ch, CURLOPT_URL, self::CALENDAR_TIMEZONE_URI);         
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);     
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));     
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);     
        $data = json_decode(curl_exec($ch), true); 
        $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE); 
         
        if ($http_code != 200) { 
            $error_msg = 'Failed to fetch timezone'; 
            if (curl_errno($ch)) { 
                $error_msg = curl_error($ch); 
            } 
            throw new Exception('Error '.$http_code.': '.$error_msg); 
        } 
 
        return $data['value']; 
    } 
 
    public function GetCalendarsList($access_token) { 
        $url_parameters = array(); 
 
        $url_parameters['fields'] = 'items(id,summary,timeZone)'; 
        $url_parameters['minAccessRole'] = 'owner'; 
 
        $url_calendars = self::CALENDAR_LIST.'?'. http_build_query($url_parameters); 
         
        $ch = curl_init();         
        curl_setopt($ch, CURLOPT_URL, $url_calendars);         
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);     
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));     
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);     
        $data = json_decode(curl_exec($ch), true); 
        $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE); 
         
        if ($http_code != 200) { 
            $error_msg = 'Failed to get calendars list'; 
            if (curl_errno($ch)) { 
                $error_msg = curl_error($ch); 
            } 
            throw new Exception('Error '.$http_code.': '.$error_msg); 
        } 
 
        return $data['items']; 
    } 
 
    public function CreateCalendarEvent($access_token, $calendar_id, $event_data, $all_day, $event_datetime, $event_timezone) { 
        $apiURL = self::CALENDAR_EVENT . $calendar_id . '/events'; 
         
        $curlPost = array(); 
         
        if(!empty($event_data['summary'])){ 
            $curlPost['summary'] = $event_data['summary']; 
        } 
         
        if(!empty($event_data['location'])){ 
            $curlPost['location'] = $event_data['location']; 
        } 
         
        if(!empty($event_data['description'])){ 
            $curlPost['description'] = $event_data['description']; 
        } 
         
        $event_date = !empty($event_datetime['event_date'])?$event_datetime['event_date']:date("Y-m-d"); 
        $start_time = !empty($event_datetime['start_time'])?$event_datetime['start_time']:date("H:i:s"); 
        $end_time = !empty($event_datetime['end_time'])?$event_datetime['end_time']:date("H:i:s"); 
 
        if($all_day == 1){ 
            $curlPost['start'] = array('date' => $event_date); 
            $curlPost['end'] = array('date' => $event_date); 
        }else{ 
            $timezone_offset = $this->getTimezoneOffset($event_timezone); 
            $timezone_offset = !empty($timezone_offset)?$timezone_offset:'07:00'; 
            $dateTime_start = $event_date.'T'.$start_time.$timezone_offset; 
            $dateTime_end = $event_date.'T'.$end_time.$timezone_offset; 
             
            $curlPost['start'] = array('dateTime' => $dateTime_start, 'timeZone' => $event_timezone); 
            $curlPost['end'] = array('dateTime' => $dateTime_end, 'timeZone' => $event_timezone); 
        } 
        $ch = curl_init();         
        curl_setopt($ch, CURLOPT_URL, $apiURL);         
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);         
        curl_setopt($ch, CURLOPT_POST, 1);         
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token, 'Content-Type: application/json'));     
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));     
        $data = json_decode(curl_exec($ch), true); 
        $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);         
         
        if ($http_code != 200) { 
            $error_msg = 'Failed to create event'; 
            if (curl_errno($ch)) { 
                $error_msg = curl_error($ch); 
            } 
            throw new Exception('Error '.$http_code.': '.$error_msg); 
        } 
 
        return $data['id']; 
    } 
     
    private function getTimezoneOffset($timezone = 'America/Los_Angeles'){ 
        $current   = timezone_open($timezone); 
        $utcTime  = new \DateTime('now', new \DateTimeZone('UTC')); 
        $offsetInSecs =  timezone_offset_get($current, $utcTime); 
        $hoursAndSec = gmdate('H:i', abs($offsetInSecs)); 
        return stripos($offsetInSecs, '-') === false ? "+{$hoursAndSec}" : "-{$hoursAndSec}"; 
    } 
} 
?>