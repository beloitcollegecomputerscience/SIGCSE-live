<!-- Licensed under the BSD. See License.txt for full text.  -->


<?php
require_once('../../global/include.php');


 if($_POST["action"]== '1'){
    $new_activity_type = $_POST["other_activity_type"];
    $result = $db->query("SELECT * from activity where activity_type='$new_activity_type';");
    if(mysqli_num_rows($result)!=0){echo "duplicatetype";}

}

if($_POST["action"]== '2'){
$new_activity_location = $_POST["other_activity_location"];
$result = $db->query("SELECT * from rooms where room_location='$new_activity_location';");
if(mysqli_num_rows($result)!=0){echo "duplicateroom";}

}

 if($_POST["action"]== '5'){
    $potential_activity_name = $_POST["activity_name"];
    $result = $db->query("SELECT activity_name from activity where activity_name='$potential_activity_name';");
    if(mysqli_num_rows($result)!=0){echo "duplicateactivityname";}

}
  if($_POST["action"]== '3'){
    $new_activity_organizer_first = $_POST["other_activity_organizer_first"];
    $new_activity_organizer_last = $_POST["other_activity_organizer_last"];
    $result = $db->query("SELECT * from organizer where first_name='$new_activity_organizer_first' and last_name='$new_activity_organizer_last';");
    if(mysqli_num_rows($result)!=0){echo "duplicateorganizer";}

}



//this $response, which is echoed later goes to the js as msg.
if($_POST["action"]=='4'){
$response="failed";
$prev;
$activity_start_time = $_POST["activity_start_time"];
$activity_end_time =$_POST["activity_end_time"];
$nu_start=preg_replace("/[^0-9]/", "", $activity_start_time);
$nu_end=preg_replace("/[^0-9]/", "", $activity_end_time);
$slot_result = $db->query("select * from time_slots where start_time = '$activity_start_time' and end_time='$activity_end_time';");

//if this time slot does not exist.
if(mysqli_num_rows($slot_result)!=0){
    $row = $slot_result->fetch_assoc();
    $activity_slot_id= $row['slot_id'];
}
else{
    $slot_result= $db->query("insert into time_slots(start_time, end_time) values ('$activity_start_time', '$activity_end_time');");
    $slot_result= $db->query("select * from time_slots where start_time = '$activity_start_time' and end_time='$activity_end_time';");
    $row=$slot_result->fetch_assoc();
    $activity_slot_id=$row['slot_id'];
}
$potential_covering_start= $db->query("select MAX(start_time) as max from time_slots where student_available ='t' and start_time <= ".$nu_start.";");
    $potential_covering_end= $db->query("select MIN(end_time) as min from time_slots where student_available ='t' and end_time >= ".$nu_end.";");

//get the values posted by ajax in the js file and put them in variables.
$activity_name = mysqli_real_escape_string($db->getDB(), $_POST["activity_name"]);

if ($_POST["activity_type"]=="Other"){$activity_type = mysqli_real_escape_string($db->getDB(), $_POST["other_activity_type"]);}
else{$activity_type = mysqli_real_escape_string($db->getDB(), $_POST["activity_type"]);}

if ($_POST["activity_location"]=="Other"){
$new_activity_location = $_POST["other_activity_location"];
$query="INSERT INTO rooms(room_location)
VALUES ('$new_activity_location');";
$db->query($query);
$query="select room_id from rooms where room_location = '$new_activity_location';";
$result=$db->query($query);
$row = $result->fetch_assoc();
$activity_location_id = $row['room_id'];
}
else{$activity_location_id = $_POST["activity_location"];
}
 if ($_POST["activity_organizer"]=="Other"){
    $new_activity_organizer_first = $_POST["other_activity_organizer_first"];
    $new_activity_organizer_last = $_POST["other_activity_organizer_last"];
    $query="INSERT INTO organizer(first_name,last_name)
    VALUES ('$new_activity_organizer_first','$new_activity_organizer_last');";
    $db->query($query);
    $query="select organizer_id from organizer where first_name = '$new_activity_organizer_first' and last_name='$new_activity_organizer_last';";
    $result=$db->query($query);
    $row = $result->fetch_assoc();
    $activity_organizer_id = $row['organizer_id'];
}
else{$activity_organizer_id = $_POST["activity_organizer"];
}



$activity_start_time = $_POST["activity_start_time"];
$activity_end_time =mysqli_real_escape_string($db->getDB(), $_POST["activity_end_time"]);
$min_num =$_POST["min_num"];
$desired_num =$_POST["desired_num"];
$max_num =$_POST["max_num"];
$activity_notes =mysqli_real_escape_string($db->getDB(), $_POST["activity_notes"]);


//query the database and insert a new record of activity with these input values.
$query = "INSERT INTO activity(room_id, slot_id, activity_name, activity_type,
 min_workers,desired_workers,max_workers,activity_notes) VALUES
('$activity_location_id','$activity_slot_id', '$activity_name', '$activity_type',
'$min_num','$desired_num','$max_num','$activity_notes')";
$db->query($query);
//echo $response which will be alerted by the js file. here it is just success. may have different one after adding other error checks.
 $query="select activity_id from activity where activity_name = '$activity_name';";
$result=$db->query($query);
$row = $result->fetch_assoc();
$activity_id = $row['activity_id'];
 $query="INSERT INTO organizer_activity(activity_id,organizer_id)
VALUES ('$activity_id','$activity_organizer_id');";
$db->query($query);

$query="select instruction_id
from instructions
where instruction_type like '%$activity_type%'";
$db->query($query);
$result=$db->query($query);
if(mysqli_num_rows($result)!=0){
    $row = $result->fetch_assoc();
    $instruction_id = $row['instruction_id'];
    $query="insert into activity_instructions (activity_id, instruction_id)
values ('$activity_id','$instruction_id');";
$db->query($query);}

$covering_start_row=$potential_covering_start->fetch_assoc();
    $covering_start=$covering_start_row["max"];
    $covering_end_row=$potential_covering_end->fetch_assoc();
    $covering_end=$covering_end_row["min"];
if($covering_start!=null && $covering_end!=null){


    $nu_covering_start=preg_replace("/[^0-9]/", "", $covering_start);
    $nu_covering_end=preg_replace("/[^0-9]/", "", $covering_end);
    $prev=$covering_start;
    $covering_slots_result=$db->query("select slot_id, start_time, end_time from time_slots where student_available ='t' and start_time >= ".$nu_covering_start." and end_time <= ".$nu_covering_end.";");
$covering_slots_affected_rows = mysqli_num_rows($covering_slots_result);


                    for ($i = 0; $i < $covering_slots_affected_rows; $i++) {
                                 $covering_slots_row=$covering_slots_result->fetch_assoc();
                                $covering_slot=$covering_slots_row["slot_id"];

                                if($covering_slots_row["start_time"]!=$prev){
                                    echo"nocoveringslot";
                                    break;
                                }
                                else{$add_covering_slot= $db->query("insert into covering_time_slots(activity_id, slot_id) values ('$activity_id', '$covering_slot');");
                                    $prev=$covering_slots_row["end_time"];
                            }



                         } }
                         else{
                             echo "nocoveringslot";
                         }
}

?>
