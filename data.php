<?php
/**
 * Created by PhpStorm.
 * User: N1
 * Date: 10/4/14
 * Time: 6:00 PM
 */

$data = "";

if (isset($_POST['data']))
    $data = $_POST['data'];

//print_r("Test");

function readSessions()
{
    //11111223344 = 11 bytes

    $shm_id = shmop_open(0xff3, "c", 644, 2048);
    $sessions = Array();

    $fIndex = 0;

    print_r(shmop_read($shm_id, $fIndex, 100) . "\n");

    while (1) {
        //if(shmop_read($id, $fIndex, 1) != " ") {

        $session = shmop_read($shm_id, $fIndex, 5);
        $date = shmop_read($shm_id, $fIndex + 5, 6);

        //print_r(strlen($session));

        //print_r($session."\n");

        if (shmop_read($shm_id, $fIndex, 2) != '\0' || shmop_read($shm_id, $fIndex, 1) == " ") {

            $sessionValue = substr($date, 0, 2) . "-" . substr($date, 2, 2) . "-" . substr($date, 4, 2);


            $sessions[$session] = $sessionValue;
        }

        //print_r("SESSION: " . $session . "\n");
        //print_r("DATE: " . $date . "\n");

        if ($session == '\0' || $session == null || strlen($session == 0) || $session == " ") {
            break;
        }

        $fIndex += 11;
        //}
    }

    shmop_close($shm_id);

    return $sessions;
}

function endofstring($string)
{
    return $string . '\0';
}

function writeMessage($id, $message)
{
    $shm_id = shmop_open($id, "c", 0644, 2048);

    if ($shm_id != 0) {

        $cIndex = 0;
        while (1) {
            if($cIndex < 2024)
            {
                if (shmop_read($shm_id, $cIndex, 2) != '\0' && shmop_read($shm_id, $cIndex, 2)*-1 != 0) {
                    $length = shmop_read($shm_id, $cIndex, 2);
                    $cIndex += intval($length) + 5 + 2;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        // NN-SSSSS-MMMMMMMMM

        $length = strlen($message['Message']);

        if ($length < 10)
            $length = '0' . $length;

        $message = $length . $message['Session'] . $message['Message'];

        shmop_write($shm_id, endofstring($message), $cIndex);


        shmop_close($shm_id);
    }
}

function removeSession($id)
{
    $fIndex = 0;
    $sIndex = 0;

    $shm_id = shmop_open(0xff3, "c", 0644, 2048);

    while (1) {
        if (shmop_read($shm_id, $fIndex, 2) != '\0' && $fIndex < 2024) {
            $currentSession = shmop_read($shm_id, $fIndex, 5);

            if (strval($currentSession) == strval($id)) {
                $sIndex = $fIndex;


                // ---------------------
                //Cleaning messages
                $shm_id2 = shmop_open(intval($id), "c", 644, 2048);

                shmop_delete($shm_id2);
                shmop_close($shm_id2);
            }

            if ($sIndex != 0 && strval($currentSession != strval($id))) {
                shmop_write($shm_id, endofstring($currentSession), $sIndex);

                for ($i = $fIndex; $i < $fIndex + 11; $i++) {
                    shmop_write($shm_id, " ", $i);

                }

                $sIndex += 11;
            }

            $fIndex += 11;
        } else {
            for($i = $fIndex; $i <= $fIndex+11; $i++);
            {
                if($i == $fIndex)
                {
                    shmop_write($shm_id, '\0', $i);
                } elseif ($i > $fIndex + 1) {
                    shmop_write($shm_id, " ", $i);
                }
            }
            break;
        }
    }

    //print_r("Sessions: " . shmop_read($shm_id, 0, 60));


    shmop_close($shm_id);
}

if ($data != "") {

    $dataJson = json_decode($data);

    /*$dataArray = array('data' => 'test', 'data2' => 'test2');

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($dataArray),
        ),
    );
    $context  = stream_context_create($options);
    $result = file_get_contents("session.php", false, $context);*/

    //print_r("Success");

    /*$bytes = shmop_write($shm_id, $dataJson->Message, 0);

    if($bytes != strlen($dataJson->Message))
    {
        print_r("Data wasn't writen.");
    }*/

    //print_r(" ". shmop_read($shm_id, 0, 20) . " \n");

    //print_r(shmop_size($shm_id));

    /*$shm_id = shmop_open(0xff3, "c", 644, 100);

    for($i = 0; $i < shmop_size($shm_id); $i++)
    {
        shmop_write($shm_id, " ", $i);
    }

    print_r("Cleaned sessions? ". shmop_read($shm_id, 0, 100) . " \n");

    shmop_delete($shm_id);*/


    //print_r($shm_id);

    $sessions = Array();
    $sessions = readSessions();

    /*$sessions = Array(
        '123' => Array(
            Array(
                'Message' => 'Value',
                'State' => 'Unread'
            ),
            Array(
                'Message' => 'Value',
                'State' => 'Unread'
            )
        ),
        '124' => Array(
            Array(
                'Message' => 'Value',
                'State' => 'Unread'
            ),
            Array(
                'Message' => 'Value',
                'State' => 'Unread'
            )
        ),
    );*/


    $message = Array(
        'Message' => $dataJson->Message,
        'Session' => $dataJson->Session
    );

    foreach ($sessions as $session => $key) {
        //print_r($session);
        $date = str_replace("-", "", $sessions[$session]);
        $sess_hours = substr($date, 0, 2);
        $sess_minutes = substr($date, 2, 2);

        if (intval(date("H")) * 60 + intval(date("i")) - 10 > (intval($sess_hours) * 60 + intval($sess_minutes))) {
            removeSession(intval($session));
        } else {

            writeMessage(intval($session), $message);

        }
    }

    //var_dump($sessions);

    //shmop_close($shm_key);


} else {
    print_r("Failure");
}