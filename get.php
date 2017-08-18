<?php
/**
 * Created by PhpStorm.
 * User: N1
 * Date: 10/4/14
 * Time: 8:47 PM
 */

$sessionID = $_POST['Session'];



$response = Array(
    'Message' => '',
    'Session' => ''
);

$sessions = Array();

$message = "";
$session = "";

function readMessage($id)
{
    $shm_id = shmop_open($id, "c", 644, 50);

    //print_r("Reading message from: " . $id . ". With memory address of " . $shm_id . "\n");

    $messages = Array();

    $fIndex = 0;


    while(1)
    {
        if(shmop_read($shm_id, 0, 2) == '\0')
        {
            break;
        }

        $length = shmop_read($shm_id, $fIndex, 2);
        $session = shmop_read($shm_id, $fIndex + 2, 5);
        $message = shmop_read($shm_id, $fIndex + 7, intval($length));

        $response['Message'] = $message;
        $response['Session'] = $session;


        if($session*-1 != 0)
        {
            $messages[] = $response;
        }

        if (shmop_read($shm_id, $fIndex, 2) == '\0') {
            break;
        }
        $fIndex += intval($length) + 5 + 2;
            //print_r("Message: " . $length . " " . $session . " " . $message);
    }

    //$message = "";
    if(sizeof($messages) > 0)
    {
        $message = sizeof($messages);
        for($m = 0; $m < sizeof($messages); $m++)
        {
            $length = strlen($messages[$m]['Message']);
            if($length < 10)
            {
                $length = '0'.$length;
            }

            $message = $message.$length.strval($messages[$m]['Session']).strval($messages[$m]['Message']);
        }
        print_r($message);
    }

    for ($i = 0; $i < shmop_size($shm_id); $i++) {
        if($i == 0)
        {
            shmop_write($shm_id, '\0', 0);
        } else if($i > 1) {
            shmop_write($shm_id, " ", $i);
        }
    }

    shmop_close($shm_id);
}

function readSessions()
{
    //11111223344 = 11 bytes

    $shm_id = shmop_open(0xff3, "c", 644, 2048);

    $sessions = Array();

    $fIndex = 0;

    while (1) {
        if(shmop_read($shm_id, $fIndex+1, 1) != " ") {

            $session = shmop_read($shm_id, $fIndex, 5);
            $date = shmop_read($shm_id, $fIndex + 5, 6);

            if ($session != '     ') {


                $sessionValue = substr($date, 0, 2) . "-" . substr($date, 2, 2) . "-" . substr($date, 4, 2);


                $sessions[$session] = $sessionValue;
            }

            if ($session == '' || $session == null || strlen($session == 0) || $session == '\0') {
                break;
            }

            $fIndex += 11;
        } else {
            break;
        }
    }

    shmop_close($shm_id);


    return $sessions;
}

function updateSession($id)
{
    $shm_id = shmop_open(0xff3, "c", 644, 2048);

    $sessions = Array();

    $fIndex = 0;

    while (1) {
        if(shmop_read($shm_id, $fIndex, 2) != '\0') {

            $session = shmop_read($shm_id, $fIndex, 5);

            if ($session == $id) {

                $date = date("His");


                $sessions[$session] = $date;

                shmop_write($shm_id, $session.$date, $fIndex);
                //print_r("Updated!");
                break;
            }

            $fIndex += 11;
        } else {
            break;
        }
    }

    shmop_close($shm_id);
}

$sessions = Array();
$sessions = readSessions();

foreach ($sessions as $ssn => $key) {
    //print_r($ssn);
    if ($ssn == $sessionID) {
        updateSession(intval($sessionID));
        readMessage(intval($sessionID));
    }
}

if($response['Message'] != "")
{

}