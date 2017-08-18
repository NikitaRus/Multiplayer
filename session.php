<?php
/**
 * Created by PhpStorm.
 * User: N1
 * Date: 10/4/14
 * Time: 8:12 PM
 */

//$data = $_POST['']

$data = "";

if(isset($_POST['Session']))
{
    $data = $_POST['Session'];

    $sessions = Array();
    $sessions = readSessions();
    //var_dump($sessions);


    $found = false;
    foreach($sessions as $ssn => $key)
    {
        writeMessage(intval($ssn), "Connected", intval($data));
        //print_r($ssn . "...");
        if(strval($ssn) == strval($data))
        {
            $found = true;
        }
    }

    if(!$found)
    {
        $sessionValue = date('H-i-s');

        $sessions[$data] = $sessionValue;

        writeSession(intval($data));
        //shmop_write($shm_id, endofstring($dataJson->Session . str_replace("-", "", $sessionValue)), 0);
        //print_r($dataJson->Session . str_replace("-", "", $sessionValue));
    }


}

function readSessions()
{
    //11111223344 = 11 bytes

    $shm_id = shmop_open(0xff3, "c", 644, 2048);
    $sessions = Array();

    $fIndex = 0;

    //print_r(shmop_read($shm_id, $fIndex, 25) . "\n");

    while (1) {
        //if(shmop_read($id, $fIndex, 1) != " ") {

        $session = shmop_read($shm_id, $fIndex, 5);
        $date = shmop_read($shm_id, $fIndex + 5, 6);

        //print_r(strlen($session));

        //print_r($session."\n");

        if (shmop_read($shm_id, $fIndex, 2) != '\0') {

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

function writeMessage($id, $message, $session)
{
    $shm_id = shmop_open($id, "c", 0644, 2048);

    $cIndex = 0;
    //print_r("Messages: " . shmop_read($shm_id, 0, 100) . "\n");
    while (1) {
        if (shmop_read($shm_id, $cIndex, 2) == '\0' || shmop_read($shm_id, $cIndex, 2) * -1 == 0) {
            break;
        }

        $length = shmop_read($shm_id, $cIndex, 2);
        $cIndex += intval($length) + 5 + 2;

    }

    // NN-SSSSS-MMMMMMMMM

    $length = strlen($message);

    if ($length < 10)
        $length = '0' . $length;

    $message = $length . $session . $message;

    shmop_write($shm_id, endofstring($message), $cIndex);

    shmop_close($shm_id);
}

function writeSession($id)
{
    $sessions = Array();

    $fIndex = 0;

    $shm_id2 = shmop_open($id, "c", 0644, 2048);
    shmop_write($shm_id2, '\0', 0);
    shmop_close($shm_id2);

    $shm_id = shmop_open(0xff3, "c", 0644, 2048);


    while(1) {
        //print_r($fIndex . " " . shmop_read($shm_id, $fIndex, 11) . "     ");
        if($fIndex < 2024)
        {
            if (shmop_read($shm_id, $fIndex, 2) == '\0' || shmop_read($shm_id, $fIndex, 1)*-1 == 0)
            {
                shmop_write($shm_id, endofstring($id . strval(date("His"))), $fIndex);
                print_r("Session writen to: " . $fIndex . " with value: " . $id . date("His"));
                writeMessage(intval($id), "Connected.", intval($id));
                break;
            }
        } else {
            break;
        }

        $fIndex += 11;
        /*$session = shmop_read($shm_id, $fIndex, 5);
        $date = shmop_read($shm_id, $fIndex+5, 6);

        print_r(shmop_read($shm_id, $fIndex, 11));

        $sessionValue = substr($date, 0, 2) . "-" . substr($date, 2, 2) . "-" . substr($date, 4, 2);

        $sessions[$session] = $sessionValue;
        $fIndex+=11;
    } else {
        break;
    }
}

$found = false;
foreach($sessions as $ssn => $key)
{
    if($ssn == $id)
    {
        $found = true;
    }
}

if(!$found)
{
    shmop_write($shm_id, endofstring($id . date("His")), $fIndex);
}*/
    }

    shmop_close($shm_id);

}

function removeSession($id)
{
    $sessions = Array();

    $fIndex = 0;
    $sIndex = 0;

    $shm_id = shmop_open(0xff3, "c", 0644, 2048);

    $previousSession = "";
    $currentSession = "";

    while(1)
    {
        if(shmop_read($shm_id, $fIndex, 2) != '\0' && $fIndex < 2024)
        {
            $currentSession = shmop_read($shm_id, $fIndex, 5);

            if(strval($currentSession) == strval($id))
            {
                $sIndex = $fIndex;


                $shm_id2 = shmop_open(intval($id), "c", 644, 2048);

                shmop_delete($shm_id2);
                shmop_close($shm_id2);

                for($i = $fIndex; $i < $fIndex+11; $i++)
                {
                    shmop_write($shm_id, " ", $i);

                }
            }

            if($sIndex != 0 && strval($currentSession != strval($id)))
            {
                shmop_write($shm_id, $currentSession, $sIndex);
                for($i = $sIndex; $i <= $sIndex + 11; $i++)
                {
                    if($i == 0)
                    {
                        shmop_write($shm_id, '\0', $i);
                    } else if($i > 1)
                    {
                        shmop_write($shm_id, " ", $i);
                    }
                }
                $sIndex += 11;
            }

            //$previousSession = $currentSession;

            $fIndex+=11;
        } else {
            break;
        }
    }

    //print_r("Sessions: " . shmop_read($shm_id, 0, 60));


    shmop_close($shm_id);
}