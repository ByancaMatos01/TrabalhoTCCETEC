<?php 
// Include Google calendar api handler class 
include_once 'GoogleCalendarApi.class.php'; 
     
// Include database configuration file 
require_once 'dbConfig.php'; 
 
$statusMsg = ''; 
$status = 'danger'; 
if(isset($_GET['code'])){ 
    // Initialize Google Calendar API class 
    $GoogleCalendarApi = new GoogleCalendarApi(); 
     
    // Get event ID from session 
    $event_id = $_SESSION['last_event_id']; 
 
    if(!empty($event_id)){ 
         
        // Fetch event details from database 
        $sqlQ = "SELECT * FROM events WHERE id = ?"; 
        $stmt = $db->prepare($sqlQ);  
        $stmt->bind_param("i", $db_event_id); 
        $db_event_id = $event_id; 
        $stmt->execute(); 
        $result = $stmt->get_result(); 
        $eventData = $result->fetch_assoc(); 
         
        if(!empty($eventData)){ 
            $calendar_event = array( 
                'summary' => $eventData['title'], 
                'location' => $eventData['location'], 
                'description' => $eventData['description'] 
            ); 
             
            $event_datetime = array( 
                'event_date' => $eventData['date'], 
                'start_time' => $eventData['time_from'], 
                'end_time' => $eventData['time_to'] 
            ); 
             
            // Get the access token 
            $access_token_sess = $_SESSION['google_access_token']; 
            if(!empty($access_token_sess)){ 
                $access_token = $access_token_sess; 
            }else{ 
                $data = $GoogleCalendarApi->GetAccessToken(GOOGLE_CLIENT_ID, REDIRECT_URI, GOOGLE_CLIENT_SECRET, $_GET['code']); 
                $access_token = $data['access_token']; 
                $_SESSION['google_access_token'] = $access_token; 
            } 
             
            if(!empty($access_token)){ 
                try { 
                    // Get the user's calendar timezone 
                    $user_timezone = $GoogleCalendarApi->GetUserCalendarTimezone($access_token); 
                 
                    // Create an event on the primary calendar 
                    $google_event_id = $GoogleCalendarApi->CreateCalendarEvent($access_token, 'primary', $calendar_event, 0, $event_datetime, $user_timezone); 
                     
                    //echo json_encode([ 'event_id' => $event_id ]); 
                     
                    if($google_event_id){ 
                        // Update google event reference in the database 
                        $sqlQ = "UPDATE events SET google_calendar_event_id=? WHERE id=?"; 
                        $stmt = $db->prepare($sqlQ); 
                        $stmt->bind_param("si", $db_google_event_id, $db_event_id); 
                        $db_google_event_id = $google_event_id; 
                        $db_event_id = $event_id; 
                        $update = $stmt->execute(); 
                         
                        unset($_SESSION['last_event_id']); 
                        unset($_SESSION['google_access_token']); 
                         
                        $status = 'success'; 
                        $statusMsg = '<p>Event #'.$event_id.' has been added to Google Calendar successfully!</p>'; 
                        $statusMsg .= '<p><a href="https://calendar.google.com/calendar/" target="_blank">Open Calendar</a>'; 
                    } 
                } catch(Exception $e) { 
                    //header('Bad Request', true, 400); 
                    //echo json_encode(array( 'error' => 1, 'message' => $e->getMessage() )); 
                    $statusMsg = $e->getMessage(); 
                } 
            }else{ 
                $statusMsg = 'Failed to fetch access token!'; 
            } 
        }else{ 
            $statusMsg = 'Event data not found!'; 
        } 
    }else{ 
        $statusMsg = 'Event reference not found!'; 
    } 
     
    $_SESSION['status_response'] = array('status' => $status, 'status_msg' => $statusMsg); 
     
    header("Location: calen.html"); 
    exit(); 
} 
?>